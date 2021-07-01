<?php
namespace Simplex\Core\Api;

use Simplex\Core\Errors\Error;
use Simplex\Core\Errors\ErrorCodes;
use Simplex\Core\Response;

class JsonResponse extends Response
{
    protected $errorCode = 0;
    protected $errorMessage = '';
    protected $data = [];

    /**
     * JsonResponse constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setContentType('application/json');
    }

    /**
     * @param mixed $mixed Input scalar type, associative array or Throwable
     * @throws \Simplex\Core\Errors\Error
     */
    public function setData($mixed): self
    {
        if (is_scalar($mixed)) {
            $this->set('result', $mixed);
            return $this;
        }

        if (is_array($mixed)) {
            $this->data = $mixed;
            return $this;
        }

        if (is_object($mixed)) {
            if ($mixed instanceof \Throwable) {
                $this->setError($mixed);
                return $this;
            }

            if (method_exists($mixed, 'toArray')) {
                $this->data = $mixed->toArray();
                return $this;
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
        // override status code if it wasn't set to other one yet
        if ($this->statusCode == 200) {
            $this->statusCode = ErrorCodes::getHttpStatusCode($err->getCode()) ?? 500;
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