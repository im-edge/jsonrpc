<?php

namespace IMEdge\Tests\JsonRpc;

use IMEdge\JsonRpc\JsonRpcProtocolError;
use IMEdge\JsonRpc\Packet;
use PHPUnit\Framework\TestCase as PhpUnitTestCase;

class TestCase extends PhpUnitTestCase
{
    /** @var string[] */
    protected array $examples = [];

    /**
     * @throws JsonRpcProtocolError
     */
    protected function parseExample(int $key): Packet
    {
        return Packet::decode($this->examples[$key]);
    }
}
