<?php

namespace IMEdge\JsonRpc;

use Exception;

class JsonRpcRequestException extends Exception
{
    public function __construct(
        public readonly Error $error
    ) {
        parent::__construct($this->error->message, $this->error->code);
    }
}
