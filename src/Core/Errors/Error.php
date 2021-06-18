<?php

namespace Simplex\Core\Errors;

use RuntimeException;
use Simplex\Core\Log;
use Throwable;

/**
 * Class Error
 *
 */
class Error extends \Error
{

    /**
     * @var array Дополнительные необязательные параметры, например, для кастомизации текста ошибки.
     */
    public $params = [];

    /** @inheritDoc */
    public function __construct($message = "", $code = 0, Throwable $previous = null, $params = [])
    {
        parent::__construct($message, $code, $previous);
        $this->params = $params;
    }

    /**
     * @param string $message Например {code, -10} Пользователь не найден
     * @return null|Error
     */
    public static function createFromMessage(string $message)
    {
        $matches = [];
        if (preg_match('#{error,\s*-?([0-9]+)}\s*(.*)#', $message, $matches)) {
            return new static($matches[2], (int)$matches[1]);
        }
        return null;
    }

    /**
     * @param bool $withCode
     * @return string
     */
    public function toString($withCode = false)
    {
        $ret = $this->message;
        if ($withCode && $this->code !== null) {
            $ret = "{error, $this->code} $this->message";
        }
        return $ret;
    }

    /**
     * Бросить исключение определенного класса
     * @param string $exceptionClass
     */
    public function throwCustom($exceptionClass = RuntimeException::class)
    {
        throw new $exceptionClass($this->message, $this->code);
    }

    /**
     * @param int $code from ErrorCodes
     * @param Throwable $previous [optional = null]
     * @param array $params
     * @return Error
     */
    public static function byCode(int $code, Throwable $previous = null, $params = []): Error
    {
        $class = Error::class;
        $error = new $class(ErrorCodes::getText($code, false, $params), $code, $previous);
        $error->params = $params;
        return $error;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return static::toArrayThrowableInner($this) + [
                'params' => $this->params,
            ];
    }

    /**
     * @param Throwable $e
     * @return array
     */
    protected static function toArrayThrowableInner(Throwable $e)
    {
        return [
            'code' => $e->getCode(),
            'message' => $e->getMessage(),
            'class' => get_class($e),
            'file' => $e->getFile() . ':' . $e->getLine(),
            'previous' => $e->getPrevious() ? [
                'code' => $e->getPrevious()->getCode(),
                'message' => $e->getPrevious()->getMessage(),
                'class' => get_class($e->getPrevious()),
                'file' => $e->getPrevious()->getFile() . ':' . $e->getPrevious()->getLine()
            ] : null,
        ];
    }

    /**
     * @param Throwable $e
     * @return array
     */
    public static function toArrayThrowable(Throwable $e)
    {
        if ($e instanceof self) {
            return $e->toArray();
        }
        return self::toArrayThrowableInner($e);
    }

    /**
     * @param Throwable $e
     * @return string
     */
    public static function serializeThrowable(Throwable $e)
    {
        return (string)json_encode(self::toArrayThrowable($e), JSON_PARTIAL_OUTPUT_ON_ERROR);
    }

    /**
     * @return string
     */
    public function serialize()
    {
        return (string)json_encode($this->toArray(), JSON_PARTIAL_OUTPUT_ON_ERROR);
    }

    /**
     * Send this error to logs
     */
    public function report()
    {
        $level = 'error';
        Log::$level($this->getMessage());
    }

    /**
     * @param Throwable $throwable
     * @return static
     */
    public static function byThrowable(Throwable $throwable)
    {
        if ($throwable instanceof static) {
            return clone $throwable;
        }
        return new static(
            $throwable->getMessage(),
            $throwable->getCode() ?: ErrorCodes::APP_INTERNAL_ERROR,
            $throwable->getPrevious(),
            ($throwable instanceof self) ? $throwable->params : []
        );
    }
}
