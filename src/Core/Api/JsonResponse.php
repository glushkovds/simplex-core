<?php
namespace Simplex\Core\Api;

use Simplex\Core\Errors\Error;
use Simplex\Core\Errors\ErrorCodes;
use Simplex\Core\Response;

class JsonResponse extends Response
{
    private $errorCode = 0;
    private $errorMessage = '';
    private $data = [];

    /**
     * Creates a Response object from input data
     *
     * @param mixed $mixed Input data
     * @throws \Exception
     * @return \Simplex\Core\Response
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

    /**
     * Creates a Response object from Throwable
     *
     * @param \Throwable $t Throwable exception
     * @return \Simplex\Core\Response
     */
    public static function fromThrowable(\Throwable $t): Response
    {
        return (new static)->setError($t->getCode(), $t->getMessage());
    }

    /**
     * Utility function to send a JSON
     *
     * @param \Simplex\Core\Response|\Throwable|mixed $data Response object, mixed data or Throwable
     */
    public static function output($data)
    {
        self::setContentType('application/json');

        try {
            $data = self::fromMixed($data);
            echo $data->toJson();
        } catch (\Throwable $t) {
            echo self::fromThrowable($t)->toJson();
        }
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

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }
}