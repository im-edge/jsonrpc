<?php

namespace IMEdge\JsonRpc;

interface RequestHandler
{
    public function handleRequest(Request $request): Response;
    public function handleNotification(Notification $notification): void;
}
