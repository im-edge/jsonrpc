<?php

namespace IMEdge\Tests\JsonRpc;

use IMEdge\JsonRpc\Error;
use IMEdge\JsonRpc\Response;

class ErrorTest extends TestCase
{
    protected array $examples = [
        '{"jsonrpc":"2.0","id":1,"error":'
        . '{"code":-32600,"message":"Expected valid JSON-RPC, got no \'result\' property"}}',
    ];

    public function testParsesSimpleResponseWithPositionalParams(): void
    {
        $packet = $this->parseExample(0);
        $this->assertInstanceOf(Response::class, $packet);
        $this->assertInstanceOf(Error::class, $packet->error);
        $this->assertEquals(-32600, $packet->error->code);
        $this->assertEquals("Expected valid JSON-RPC, got no 'result' property", $packet->error->message);
    }
}
