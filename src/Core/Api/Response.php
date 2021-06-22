<?php
namespace Simplex\Core\Api;

use Simplex\Core\Errors\Error;
use Simplex\Core\Errors\ErrorCodes;

class Response
{
    protected $errorCode = 0;
    protected $errorMessage = '';
    protected $data = [];

    /**
     * @throws \Exception
     */
    public static function fromMixed($mixed): Response
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

    public static function fromThrowable(\Throwable $t): Response
    {
        return (new static)->setError($t->getCode(), $t->getMessage());
    }

    public function set($key, $value): self
    {
        $this->data[$key] = $value;
        return $this;
    }

    public function setError($code, $message = null): self
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