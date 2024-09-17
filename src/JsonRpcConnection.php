<?php

namespace IMEdge\JsonRpc;

use Amp\ByteStream\ReadableStream;
use Amp\ByteStream\WritableStream;
use Amp\DeferredFuture;
use Exception;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Revolt\EventLoop;
use RuntimeException;
use stdClass;
use Throwable;

use function Amp\Future\await;

class JsonRpcConnection
{
    public const MAX_ALLOWED_PARSE_ERRORS = 3;

    /** @var array<scalar, DeferredFuture<Response>> */
    protected array $pending = [];
    /** @var array<scalar, string> */
    protected array $scheduledTimeouts = [];
    protected int $unknownErrorCount = 0;

    public function __construct(
        protected readonly ReadableStream $in,
        protected readonly WritableStream $out,
        public ?RequestHandler $requestHandler = null,
        public ?LoggerInterface $logger = null,
    ) {
        EventLoop::queue($this->keepReading(...));
    }

    protected function keepReading(): void
    {
        try {
            if (! method_exists($this->in, 'packets')) {
                throw new RuntimeException('API will change. Currently we accept only NetSting');
            }
            // TODO: either fix read in the NetString package, or... whatever
            // while (null !== ($data = $this->in->read())) {
            foreach ($this->in->packets() as $data) {
                try {
                    $this->processReceivedData($data);
                } catch (Throwable $error) {
                    $this->logger?->error($error->getMessage());
                    $this->unknownErrorCount++;
                    if ($this->unknownErrorCount === self::MAX_ALLOWED_PARSE_ERRORS) {
                        $this->logger?->error('Too many decoding errors, shutting down the JSON-RPC connection');
                        $this->close();
                    } else {
                        $this->logger?->warning('JSON-RPC Decoding error, ignoring packet');
                    }
                }
            }
        } catch (Throwable $e) {
            $this->logger?->error('Reading from NetString reader failed: ' . $e->getMessage());
        }
        $this->close();
        // $this->logger?->notice('JSON-RPC connection closed');
    }

    protected function processReceivedData(string $data): void
    {
        $packet = Packet::decode($data);
        if ($packet instanceof Response) {
            $this->handleResponse($packet);
        } elseif ($packet instanceof Request) {
            $this->handleRequest($packet);
        } elseif ($packet instanceof Notification) {
            $this->handleNotification($packet);
        } else {
            throw new RuntimeException('Got unknown JSON-RPC Packet implementation: ' . get_class($packet));
        }
    }

    protected function handleNotification(Notification $notification): void
    {
        if ($this->requestHandler) {
            try {
                $this->requestHandler->handleNotification($notification);
            } catch (Throwable $e) {
                $this->logger?->error('JSON-RPC Notification handler failed: ' . $e->getMessage());
            }
        } else {
            $this->logger?->error('Got a JSON-RPC Notification, but have no related handler');
        }
    }

    protected function handleRequest(Request $request): void
    {
        if ($this->requestHandler) {
            try {
                $response = $this->requestHandler->handleRequest($request);
            } catch (Throwable $e) {
                $response = new Response($request->id, null, Error::forThrowable($e));
            }
            $this->sendPacket($response);
        } else {
            $this->sendPacket(new Response($request->id, null, new Error(ErrorCode::METHOD_NOT_FOUND)));
        }
    }

    protected function handleResponse(Response $response): void
    {
        $id = $response->id;
        if ($id === null) {
            throw new RuntimeException("Got a JSON-RPC response w/o id - this shouldn't happen");
        }

        if (isset($this->pending[$id])) {
            $deferred = $this->pending[$id];
            $this->forget($id);
            try {
                $deferred->complete($response);
            } catch (Throwable $e) {
                $this->logger?->error('JSON-RPC response handling failed: ' . $e->getMessage());
            }
        } else {
            $this->handleUnmatchedResponse($response);
        }
    }

    protected function handleUnmatchedResponse(Response $response): void
    {
        $this->logger?->error('Unmatched Response: ' . $response->toString());
    }

    /**
     * @param stdClass|array<int|string, mixed>|null $params
     * @throws JsonRpcProtocolError|JsonRpcRequestError
     */
    public function request(
        string $method,
        stdClass|array|null $params = null,
        ?stdClass $extraProperties = null
    ): mixed {
        $request = new Request($method, $this->getNextRequestId(), $params);
        if ($extraProperties) {
            $request->setExtraProperties($extraProperties);
        }
        $result = $this->sendRequest($request);
        if ($result->error) {
            throw JsonRpcRequestError::fromError($result->error);
        }

        return $result->result;
    }

    /**
     * @param stdClass|array<int|string, mixed>|null $params
     */
    public function notification(string $method, stdClass|array|null $params = null): void
    {
        $this->sendPacket(new Notification($method, $params));
    }

    protected function scheduleRequestTimeout(string|int|float|bool $requestId): void
    {
        $this->scheduledTimeouts[$requestId] = EventLoop::delay(300, function () use ($requestId) {
            unset($this->scheduledTimeouts[$requestId]);
            $request = $this->pending[$requestId];
            unset($this->pending[$requestId]);
            $request->error(new JsonRpcRequestError('Request timed out'));
        });
    }

    protected function forget(string|int|float|bool $requestId): void
    {
        if (isset($this->scheduledTimeouts[$requestId])) {
            EventLoop::cancel($this->scheduledTimeouts[$requestId]);
            unset($this->scheduledTimeouts[$requestId]);
        }
        unset($this->pending[$requestId]);
    }

    public function sendRequest(Request $request): Response
    {
        $id = $request->id ??= $this->getNextRequestId();
        if (isset($this->pending[$id])) {
            throw new InvalidArgumentException(
                "A request with id '$id' is already pending"
            );
        }
        $this->sendPacket($request);
        $deferred = new DeferredFuture();
        $this->pending[$id] = $deferred;
        $this->scheduleRequestTimeout($id);

        return await([$deferred->getFuture()])[0];
    }

    protected function getNextRequestId(): int
    {
        for ($i = 0; $i < 100; $i++) {
            $id = mt_rand(1, 1000000000);
            if (!isset($this->pending[$id])) {
                return $id;
            }
        }

        throw new RuntimeException('Unable to generate a free random request ID, gave up after 100 attempts');
    }

    protected function rejectAllPendingRequests(string $message): void
    {
        if (empty($this->pending)) {
            return;
        }

        $exception = new Exception($message);
        foreach ($this->pending as $pending) {
            $pending->error($exception);
        }
        $this->pending = [];
        foreach ($this->scheduledTimeouts as $timer) {
            EventLoop::cancel($timer);
        }
        $this->scheduledTimeouts = [];
    }

    public function sendPacket(Packet $packet): void
    {
        $this->out->write($packet->toString());
    }

    public function close(): void
    {
        $this->rejectAllPendingRequests('Connection closed');
        $this->in->close();
        $this->out->end();
    }
}
