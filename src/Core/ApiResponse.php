<?php

namespace Simplex\Core;

use Simplex\Core\Errors\Error;
use Simplex\Core\Errors\ErrorCodes;

class ApiResponse
{
    protected $errorCode = 0;
    protected $errorMessage = '';
    protected $data = [];

    /**
     * @throws \Exception
     */
    public static function fromMixed($mixed)
    {
        if (is_scalar($mixed)) {
            return (new static)->set('result', $mixed);
        }

        if (is_array($mixed)) {
            $self = new static;
            $self->data = $mixed;
            return $self;
        }

        if (is_object($mixed)) {
            if ($mixed instanceof self) {
                return $mixed;
            }

            throw Error::byCode(ErrorCodes::APP_UNSUPPORTED_RESPONSE_TYPE, null, ['class' => get_class($mixed)]);
        }

        throw Error::byCode(
            ErrorCodes::APP_UNSUPPORTED_RESPONSE,
            null,
            ['content' => substr(var_export($mixed, true), 0, 200)]
        );
    }

    public function set($key, $value): self
    {
        $this->data[$key] = $value;
        return $this;
    }

    public function setError($code, $message = ''): self
    {
        $this->errorCode = $code;
        $this->errorMessage = $message ?? ErrorCodes::getText($code);
        return $this;
    }

    public function toArray(): array
    {
        return [
                'error' => [
                    'code' => $this->errorCode,
                    'message' => $this->errorMessage,
                ]
            ] + $this->data;
    }
}