<?php

namespace IMEdge\Tests\JsonRpc;

use IMEdge\JsonRpc\Notification;

class NotificationTest extends TestCase
{
    protected array $examples = [
        '{"jsonrpc":"2.0","method":"update","params":[1,2,3,4,5]}',
    ];

    public function testParsesNotificationWithPositionalParams(): void
    {
        $packet = $this->parseExample(0);
        $this->assertInstanceOf(Notification::class, $packet);
        $this->assertEquals('update', $packet->method);
        $this->assertEquals([1, 2, 3, 4, 5], $packet->params);
    }

    public function testRendersNotificationWithPositionalParams(): void
    {
        $this->assertEquals(
            $this->examples[0],
            $this->parseExample(0)->toString()
        );
    }
}
