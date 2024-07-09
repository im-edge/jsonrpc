<?php

namespace IMEdge\JsonRpc;

use stdClass;

use function array_key_exists;
use function is_array;
use function is_object;
use function property_exists;

class Notification extends Packet
{
    /**
     * @param stdClass|array<int|string, mixed>|null $params
     */
    public function __construct(
        public readonly string $method,
        public readonly stdClass|array|null $params,
    ) {
    }

    public function getParam(string $name, mixed $default = null): mixed
    {
        if (is_object($this->params) && property_exists($this->params, $name)) {
            return $this->params->$name;
        } elseif (is_array($this->params) && array_key_exists($name, $this->params)) {
            return $this->params[$name];
        }

        return $default;
    }

    public function jsonSerialize(): stdClass
    {
        $plain = [
            Protocol::PROPERTY_VERSION => Protocol::VERSION_2_0,
            Protocol::PROPERTY_METHOD  => $this->method,
            Protocol::PROPERTY_PARAMS  => $this->params,
        ];

        if ($this->hasExtraProperties()) {
            $plain += (array) $this->getExtraProperties();
        }

        return (object) $plain;
    }
}
