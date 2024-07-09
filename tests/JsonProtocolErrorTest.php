<?php

namespace IMEdge\Tests\JsonRpc;

use IMEdge\JsonRpc\JsonRpcProtocolError;

class JsonProtocolErrorTest extends TestCase
{
    public function testCanBeThrown(): void
    {
        $this->expectException(JsonRpcProtocolError::class);
        throw new JsonRpcProtocolError();
    }
}
