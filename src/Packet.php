<?php

namespace IMEdge\JsonRpc;

use IMEdge\Json\JsonSerialization;
use IMEdge\Json\JsonString;
use JsonException;
use stdClass;

use function property_exists;

abstract class Packet implements JsonSerialization
{
    protected ?stdClass $extraProperties = null;

    /**
     * @throws JsonException
     */
    public function toString(): string
    {
        return JsonString::encode($this->jsonSerialize());
    }

    public function hasExtraProperties(): bool
    {
        return $this->extraProperties !== null;
    }

    public function getExtraProperties(): ?stdClass
    {
        return $this->extraProperties;
    }

    /**
     * @param stdClass|null $extraProperties
     * @return $this
     * @throws JsonRpcProtocolError
     */
    public function setExtraProperties(?stdClass $extraProperties): static
    {
        if ($extraProperties) {
            foreach (Protocol::WELL_KNOWN_PROPERTIES as $key) {
                if (property_exists($extraProperties, $key)) {
                    throw new JsonRpcProtocolError("Cannot accept '$key' as an extra property");
                }
            }
        }
        $this->extraProperties = $extraProperties;

        return $this;
    }

    public function getExtraProperty(string $name, mixed $default = null): mixed
    {
        return $this->extraProperties->$name ?? $default;
    }

    public function setExtraProperty(string $name, mixed $value): void
    {
        if ($this->extraProperties === null) {
            $this->extraProperties = (object) [$name => $value];
        } else {
            $this->extraProperties->$name = $value;
        }
    }

    /**
     * @throws JsonRpcProtocolError
     */
    public static function decode(string $string): Notification|Response|Request
    {
        try {
            return self::fromSerialization(JsonString::decode($string));
        } catch (JsonException $e) {
            throw new JsonRpcProtocolError(sprintf(
                'JSON decode failed: %s',
                $e->getMessage()
            ), ErrorCode::PARSE_ERROR->value);
        }
    }

    /**
     * @throws JsonRpcProtocolError
     */
    public static function fromSerialization($any): Notification|Response|Request
    {
        if (! $any instanceof stdClass) {
            throw new JsonRpcProtocolError(
                'stdClass expected, got ' . get_debug_type($any),
                ErrorCode::INVALID_REQUEST->value
            );
        }
        $version = ObjectHelper::stripRequiredString($any, Protocol::PROPERTY_VERSION);
        if ($version !== Protocol::VERSION_2_0) {
            throw new JsonRpcProtocolError(
                sprintf("Only JSON-RPC %s is supported, got %s", Protocol::VERSION_2_0, $version),
                ErrorCode::INVALID_REQUEST->value
            );
        }

        // Hint: we MUST use property_exists here, as a NULL id is allowed
        // in error response in case it wasn't possible to determine a
        // request id
        $hasId = property_exists($any, Protocol::PROPERTY_ID);
        $id = ObjectHelper::stripOptionalProperty($any, Protocol::PROPERTY_ID);
        if ($id !== null && ! is_scalar($id)) {
            throw new JsonRpcProtocolError('ID must be scalar, got ' . get_debug_type($id));
        }
        $error = ObjectHelper::stripOptionalStdClass($any, Protocol::PROPERTY_ERROR);
        if (property_exists($any, Protocol::PROPERTY_METHOD)) {
            $method = ObjectHelper::stripRequiredString($any, Protocol::PROPERTY_METHOD);
            $params = ObjectHelper::stripOptionalStdClassOrArray($any, Protocol::PROPERTY_PARAMS);
            $packet = $id === null ? new Notification($method, $params) : new Request($method, $id, $params);
        } elseif (! $hasId) {
            throw new JsonRpcProtocolError(
                sprintf('Given string is not a valid JSON-RPC %s response: id is missing', Protocol::VERSION_2_0),
                ErrorCode::INVALID_REQUEST->value
            );
        } else {
            $packet = new Response($id);
            if ($error) {
                $packet->error = new Error(
                    ObjectHelper::stripRequiredInt($error, Protocol::PROPERTY_CODE), // TODO: Really?
                    ObjectHelper::stripOptionalString($error, 'message'),
                    ObjectHelper::stripOptionalProperty($error, 'data')
                );
            } else {
                $result = ObjectHelper::stripRequiredProperty($any, Protocol::PROPERTY_RESULT);
                $packet->result = $result;
            }
        }
        if (count((array) $any) > 0) {
            $packet->setExtraProperties($any);
        }

        return $packet;
    }
}
