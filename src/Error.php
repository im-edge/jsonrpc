<?php

namespace IMEdge\JsonRpc;

use JsonSerializable;
use Throwable;

class Error implements JsonSerializable
{
    protected const DEFAULT_CUSTOM_MESSAGE = 'Server error. Reserved for implementation-defined server-errors.';

    public readonly int $code;
    public readonly string $message;
    public readonly bool $isWellKnown;
    public readonly bool $isCustom;

    public function __construct(
        int|ErrorCode $code,
        ?string $message = null,
        public mixed $data = null
    ) {
        if ($code instanceof ErrorCode) {
            $this->isWellKnown = true;
            $this->isCustom = false;
            $this->code = $code->value;
            $this->message = $message ?: $code->getMessage();
        } elseif ($wellKnown = ErrorCode::tryFrom($code)) {
            $this->isWellKnown = true;
            $this->isCustom = false;
            $this->code = $wellKnown->value;
            $this->message = $message ?: $wellKnown->getMessage();
        } elseif (ErrorCode::isCustom($code)) {
            $this->code = $code;
            $this->isWellKnown = false;
            $this->isCustom = true;
            $this->message = $message ?: self::DEFAULT_CUSTOM_MESSAGE;
        } else {
            $this->code = ErrorCode::INTERNAL_ERROR->value;
            $this->isCustom = false;
            $this->isWellKnown = true;
            $this->message = $message
                ? "Got invalid error code $code. Message was: " . $message
                : "Got invalid error code $code";
        }
    }

    public static function forThrowable(Throwable $exception): Error
    {
        // TODO: any way to preserve the exception code?
        $self = new Error(ErrorCode::INTERNAL_ERROR, sprintf(
            '%s in %s(%d)',
            $exception->getMessage(),
            basename($exception->getFile()),
            $exception->getLine()
        ));
        if (! $self->isWellKnown) {
            $data = $exception->getTraceAsString();
            if (function_exists('iconv')) {
                $data = iconv('UTF-8', 'UTF-8//IGNORE', $data);
            }
            $self->data = $data;
        }

        return $self;
    }

    public function jsonSerialize(): object
    {
        $result = [
            'code'    => $this->code,
            'message' => $this->message,
        ];

        if ($this->data !== null) {
            $result['data'] = $this->data;
        }

        return (object) $result;
    }
}
