<?php

namespace IMEdge\JsonRpc;

use stdClass;

class Response extends Packet
{
    public function __construct(
        public string|int|float|bool|null $id = null,
        public mixed $result = null,
        public ?Error $error = null,
    ) {
    }

    public function jsonSerialize(): stdClass
    {
        $plain = [
            Protocol::PROPERTY_VERSION => Protocol::VERSION_2_0,
        ];
        if ($this->hasExtraProperties()) {
            $plain += (array) $this->getExtraProperties();
        }

        if ($this->id !== null) {
            $plain[Protocol::PROPERTY_ID] = $this->id;
        }

        if ($this->error === null) {
            $plain[Protocol::PROPERTY_RESULT] = $this->result;
        } else {
            if (! isset($plain[Protocol::PROPERTY_ID])) {
                $plain[Protocol::PROPERTY_ID] = null;
            }
            $plain[Protocol::PROPERTY_ERROR] = $this->error;
        }

        return (object) $plain;
    }
}
