<?php

namespace IMEdge\Tests\JsonRpc;

use IMEdge\JsonRpc\Request;

class RequestTest extends TestCase
{
    protected array $examples = [
        '{"jsonrpc":"2.0","method":"subtract","params":[42,23],"id":1}',
        '{"jsonrpc":"2.0","method":"subtract","params":{"subtrahend":23,"minuend":42},"id":3}',
    ];

    public function testParsesSimpleRequestsWithPositionalParams(): void
    {
        $packet = $this->parseExample(0);
        $this->assertInstanceOf(Request::class, $packet);
        $this->assertEquals('subtract', $packet->method);
        $this->assertEquals([42, 23], $packet->params);
        $this->assertEquals(1, $packet->id);
    }

    public function testParsesSimpleRequestsWithNamedParams(): void
    {
        $packet = $this->parseExample(1);
        $this->assertInstanceOf(Request::class, $packet);
        $this->assertEquals('subtract', $packet->method);
        $this->assertEquals((object) [
            'subtrahend' => 23,
            'minuend'    => 42
        ], $packet->params);
        $this->assertEquals(3, $packet->id);
    }

    public function testRendersRequestWithPositionalParams(): void
    {
        $this->assertEquals(
            $this->examples[0],
            $this->parseExample(0)->toString()
        );
    }

    public function testRendersRequestWithNamedParams(): void
    {
        $this->assertEquals(
            $this->examples[1],
            $this->parseExample(1)->toString()
        );
    }
}
