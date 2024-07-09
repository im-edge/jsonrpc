<?php

namespace IMEdge\Tests\JsonRpc;

use IMEdge\JsonRpc\Error;
use IMEdge\JsonRpc\ErrorCode;
use IMEdge\JsonRpc\Notification;
use IMEdge\JsonRpc\Packet;
use IMEdge\JsonRpc\Request;
use IMEdge\JsonRpc\RequestHandler;
use IMEdge\JsonRpc\Response;

class TestingRequestHandler implements RequestHandler
{
    /** @var Packet[] */
    public array $packets = [];

    public function handleRequest(Request $request): Response
    {
        $this->packets[] = $request;
        return new Response($request->id, null, new Error(ErrorCode::METHOD_NOT_FOUND));
    }

    public function handleNotification(Notification $notification): void
    {
        $this->packets[] = $notification;
    }
}
