<?php
namespace Simplex\Core\Api;

use Simplex\Core\Errors\Error;
use Simplex\Core\Errors\ErrorCodes;
use Simplex\Core\Response;
use Simplex\Core\ResponseStatus;

class JsonResponse extends Response
{
    protected $errorCode = 0;
    protected $errorMessage = '';
    protected $data = [];

    /**
     * JsonResponse constructor.
     * @param mixed $mixed Input scalar type, associative array or Throwable
     */
    public function __construct($mixed)
    {
        parent::__construct();
        $this->setContentType('application/json');

        if (is_scalar($mixed)) {
            $this->set('result', $mixed);
            return;
        }

        if (is_array($mixed)) {
            $this->data = $mixed;
            return;
        }

        if (is_object($mixed)) {
            if ($mixed instanceof \Throwable) {
                $this->setError($mixed);
                return;
            }

            throw Error::byCode(ErrorCodes::APP_UNSUPPORTED_RESPONSE_TYPE, null, ['class' => get_class($mixed)]);
        }

        throw Error::byCode(
            ErrorCodes::APP_UNSUPPORTED_RESPONSE,
            null,
            ['content' => substr(var_export($mixed, true), 0, 200)]
        );
    }

    protected function makeBody(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }

    /**
     * Sets value in data by key
     *
     * @param mixed $key Key
     * @param mixed $value Value
     * @return $this
     */
    public function set($key, $value): self
    {
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * Sets error from throwable
     *
     * @param \Throwable $err Throwable to set from
     * @return $this
     */
    public function setError(\Throwable $err): self
    {
        if ($err->getCode() == ErrorCodes::APP_METHOD_NOT_FOUND) {
            $this->statusCode = 404;
        }

        $this->errorCode = $err->getCode();
        $this->errorMessage = $err->getMessage();
        return $this;
    }

    /**
     * Returns array with current error and data
     *
     * @return array|array[]
     */
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