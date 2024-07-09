<?php

namespace IMEdge\JsonRpc;

use Exception;

class JsonRpcRequestError extends Exception
{
    public static function fromError(Error $error): JsonRpcRequestError
    {
        return new JsonRpcRequestError($error->message, $error->code);
    }
}
