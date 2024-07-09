<?php

namespace IMEdge\JsonRpc;

use stdClass;

use function property_exists;

class ObjectHelper
{
    /**
     * @throws JsonRpcProtocolError
     */
    public static function assertPropertyExists(stdClass $object, string $property): void
    {
        if (! property_exists($object, $property)) {
            throw new JsonRpcProtocolError(
                "Expected valid JSON-RPC, got no '$property' property",
                ErrorCode::INVALID_REQUEST->value
            );
        }
    }

    public static function stripOptionalProperty(stdClass $object, string $property): mixed
    {
        if (property_exists($object, $property)) {
            $value = $object->$property;
            unset($object->$property);

            return $value;
        }

        return null;
    }

    public static function stripOptionalStdClass(stdClass $object, string $property): ?stdClass
    {
        $value = self::stripOptionalProperty($object, $property);
        if ($value === null) {
            return null;
        }
        if (! $value instanceof stdClass) {
            throw new JsonRpcProtocolError(
                "Expected '$property' to be an object, got " . get_debug_type($value),
                ErrorCode::INVALID_REQUEST->value
            );
        }

        return $value;
    }

    /**
     * @return stdClass|array<int|string, mixed>|null
     * @throws JsonRpcProtocolError
     */
    public static function stripOptionalStdClassOrArray(stdClass $object, string $property): stdClass|array|null
    {
        $value = self::stripOptionalProperty($object, $property);
        if ($value === null) {
            return null;
        }
        if (! $value instanceof stdClass && ! is_array($value)) {
            throw new JsonRpcProtocolError(
                "Expected '$property' to be an optional array or object, got " . get_debug_type($value),
                ErrorCode::INVALID_REQUEST->value
            );
        }

        return $value;
    }

    public static function stripOptionalString(stdClass $object, string $property): ?string
    {
        $value = self::stripOptionalProperty($object, $property);
        if ($value === null) {
            return null;
        }
        if (! is_string($value)) {
            throw new JsonRpcProtocolError(
                "Expected '$property' to be an string, got " . get_debug_type($value),
                ErrorCode::INVALID_REQUEST->value
            );
        }

        return $value;
    }

    public static function stripRequiredInt(stdClass $object, string $property): int
    {
        $value = self::stripRequiredProperty($object, $property);
        if (! is_int($value)) {
            throw new JsonRpcProtocolError(
                "Expected '$property' to be an integer, got " . get_debug_type($value),
                ErrorCode::INVALID_REQUEST->value
            );
        }

        return $value;
    }

    /**
     * @throws JsonRpcProtocolError
     */
    public static function stripRequiredProperty(stdClass $object, string $property): mixed
    {
        if (! property_exists($object, $property)) {
            throw new JsonRpcProtocolError(
                "Expected valid JSON-RPC, got no '$property' property",
                ErrorCode::INVALID_REQUEST->value
            );
        }

        $value = $object->$property;
        unset($object->$property);

        return $value;
    }

    /**
     * @throws JsonRpcProtocolError
     */
    public static function stripRequiredString(stdClass $object, string $property): string
    {
        $value = self::stripOptionalProperty($object, $property);
        if (! is_string($value)) {
            throw new JsonRpcProtocolError(
                "'$property' needs to be a string, got " . get_debug_type($value),
                ErrorCode::INVALID_REQUEST->value
            );
        }

        return $value;
    }
}
