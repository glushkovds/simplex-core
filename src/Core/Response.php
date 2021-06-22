<?php
namespace Simplex\Core;

use Simplex\Core\Errors\Error;
use Simplex\Core\Errors\ErrorCodes;

class Response
{
    protected $errorCode = 0;
    protected $errorMessage = '';
    protected $data = [];

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
     * Sends Location cookie and terminates execution
     * @param string $url URL to redirect to
     */
    public static function redirect(string $url)
    {
        header('Location: ' . $url);
        exit;
    }

    /**
     * Sets the cookie
     *
     * @param string $name Name
     * @param string $value Value
     * @param array $options Options
     */
    public static function setCookie(string $name, string $value = '', array $options = [])
    {
        setcookie($name, $value, $options);
    }

    /**
     * Sends a header with status code
     * @param string $code Status code string
     */
    public static function setStatusCode(string $code)
    {
        header('HTTP/1.1 ' . $code);
    }

    /**
     * Sets the content type
     * @param string $contentType Content type
     */
    public static function setContentType(string $contentType)
    {
        header('Content-Type: ' . $contentType);
    }

    /**
     * Sets the content disposition header
     * @param string $fileName File name
     */
    public static function setContentDisposition(string $fileName)
    {
        header('Content-Disposition: attachment; filename=' . $fileName);
    }

    /**
     * Utility function to send a file
     *
     * @param string $fileName File name
     * @param string $fileContent Content of the file
     */
    public static function sendFile(string $fileName, string $fileContent)
    {
        self::setContentDisposition($fileName);
        echo $fileContent;
    }

    /**
     * Utility function to send a JSON
     *
     * @param \Simplex\Core\Response|\Throwable|mixed $data Response object, mixed data or Throwable
     */
    public static function sendJson($data)
    {
        self::setContentType('application/json');

        if ($data instanceof self) {
            echo $data->toJson();
            return;
        }

        try {
            $data = self::fromMixed($data);
            echo $data->toJson();
            return;
        } catch (\Throwable $t) {
            $data = $t;
        }

        if ($data instanceof \Throwable) {
            echo self::fromThrowable($data)->toJson();
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