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

    public function __construct($mixed)
    {
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

    public function output()
    {
        self::setContentType('application/json');
        echo json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }

    public function set($key, $value): self
    {
        $this->data[$key] = $value;
        return $this;
    }

    public function setError(\Throwable $err): self
    {
        $this->errorCode = $err->getCode();
        $this->errorMessage = $err->getMessage();
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