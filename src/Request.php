<?php

namespace IMEdge\JsonRpc;

use stdClass;

class Request extends Notification
{
    public string|int|float|bool|null $id;

    /**
     * @param stdClass|array<int|string, mixed>|null $params
     */
    public function __construct(string $method, string|int|float|bool|null $id = null, mixed $params = null)
    {
        parent::__construct($method, $params);

        $this->id = $id;
    }

    /**
     * @throws JsonRpcProtocolError
     */
    public function jsonSerialize(): stdClass
    {
        if ($this->id === null) {
            throw new JsonRpcProtocolError('A request without an ID is not valid');
        }

        $plain = parent::jsonSerialize();
        $plain->id = $this->id;

        return $plain;
    }
}
