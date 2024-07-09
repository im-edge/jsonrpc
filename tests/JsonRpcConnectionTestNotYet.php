<?php

namespace IMEdge\Tests\JsonRpc;

use Amp\ByteStream\ReadableIterableStream;
use Amp\ByteStream\WritableBuffer;
use IMEdge\JsonRpc\Error;
use IMEdge\JsonRpc\ErrorCode;
use IMEdge\JsonRpc\JsonRpcConnection;
use IMEdge\JsonRpc\Request;
use IMEdge\JsonRpc\Response;

use function Amp\ByteStream\pipe;
use function Amp\delay;

class JsonRpcConnectionTestNotYet extends TestCase
{
    protected array $examples = [
        '{"jsonrpc":"2.0", "method": "subtract", "params": [42,23], "id": 1}',
        '{"jsonrpc":"2.0","id":1,',
        '{"jsonrpc":"2.0", "method": "subtract", "params": {"subtrahend": 23,"minuend": 42}, "id": 3}',
    ];

    /**
     * TODO: This fails
     */
    public function xxtestSimpleRequestHandling(): void
    {
        $in = new ReadableIterableStream((function (): \Generator {
            delay(0.2);
            yield $this->examples[0];
            delay(0.01);
            yield $this->examples[1];
            delay(0.01);
            yield $this->examples[2];
        })());
        $out = new WritableBuffer();
        $handler = new TestingRequestHandler();
        $left = new JsonRpcConnection($in, $out, $handler);
        delay(0.5);
        $packets = &$handler->packets;
        $this->assertInstanceOf(Request::class, $packets[0]);
        assert($packets[0] instanceof Request);
        $this->assertEquals('subtract', $packets[0]->method);
        var_dump($packets);
        var_dump(get_class($packets[1]));
        $this->assertInstanceOf(Response::class, $packets[1]);
        assert($packets[1] instanceof Response);
        $this->assertInstanceOf(Error::class, $packets[1]->error);
        $this->assertEquals(ErrorCode::PARSE_ERROR->value, $packets[1]->error->code);
        $this->assertInstanceOf(Request::class, $packets[2]);
    }
}
