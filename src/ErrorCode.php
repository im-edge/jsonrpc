<?php

namespace IMEdge\JsonRpc;

enum ErrorCode: int
{
    case PARSE_ERROR = -32700;
    case INVALID_REQUEST = -32600;
    case METHOD_NOT_FOUND = -32601;
    case INVALID_PARAMS = 32602;
    case INTERNAL_ERROR = 32603;

    // Reserved for implementation-defined server-errors:
    public const MIN_CUSTOM_ERROR = -32000;
    public const MAX_CUSTOM_ERROR = -32099;

    public function getMessage(): string
    {
        return match ($this) {
            self::PARSE_ERROR      => 'Invalid JSON was received by the server',
            self::INVALID_REQUEST  => 'The JSON sent is not a valid Request object',
            self::METHOD_NOT_FOUND => 'The method does not exist / is not available',
            self::INVALID_PARAMS   => 'Invalid method parameter(s)',
            self::INTERNAL_ERROR   => 'Internal JSON-RPC error',
        };
    }

    public static function isCustom(int $code): bool
    {
        return $code >= self::MAX_CUSTOM_ERROR && $code <= self::MIN_CUSTOM_ERROR;
    }

    public static function isWellKnown(int $code): bool
    {
        return ErrorCode::tryFrom($code) !== null;
    }

    public function jsonSerialize(): int
    {
        return $this->value;
    }
}
