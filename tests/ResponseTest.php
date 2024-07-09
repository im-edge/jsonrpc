<?php

namespace IMEdge\Tests\JsonRpc;

use IMEdge\JsonRpc\Response;

class ResponseTest extends TestCase
{
    protected array $examples = [
        '{"jsonrpc":"2.0","id":1,"result":19}',
    ];

    public function testParsesSimpleResponseWithPositionalParams(): void
    {
        $packet = $this->parseExample(0);
        $this->assertInstanceOf(Response::class, $packet);
        $this->assertEquals(19, $packet->result);
        $this->assertEquals(1, $packet->id);
    }

    public function testRendersResponseWithNamedParams(): void
    {
        $this->assertEquals(
            $this->examples[0],
            $this->parseExample(0)->toString()
        );
    }
}
