<?php

namespace IMEdge\JsonRpc;

final class Protocol
{
    public const VERSION_2_0 = '2.0';

    public const PROPERTY_VERSION = 'jsonrpc';
    public const PROPERTY_METHOD  = 'method';
    public const PROPERTY_ID      = 'id';
    public const PROPERTY_PARAMS  = 'params';
    public const PROPERTY_RESULT  = 'result';
    public const PROPERTY_CODE    = 'code';
    public const PROPERTY_ERROR   = 'error';

    public const WELL_KNOWN_PROPERTIES = [
        self::PROPERTY_VERSION,
        self::PROPERTY_METHOD,
        self::PROPERTY_ID,
        self::PROPERTY_PARAMS,
        self::PROPERTY_RESULT,
        self::PROPERTY_CODE,
        self::PROPERTY_ERROR,
    ];
}
