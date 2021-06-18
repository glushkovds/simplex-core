<?php

namespace Simplex\Core\Errors;

/**
 * Class ErrorCollection реализует интерфейс массива. Представляет собой список ошибок.
 * Можно например так
 * $errors = new ErrorCollection([Error::byCode(101)]);
 * $errors[101]->getMessage();
 * throw $errors[101];
 */
class ErrorCollection implements \ArrayAccess
{

    /**
     * @var Error[]
     */
    protected $errors = [];

    /**
     *
     * @param Error[] $errors
     */
    public function __construct(array $errors = [])
    {
        foreach ($errors as $e) {
            $this->add($e);
        }
    }

    /**
     * @param Error $e
     */
    public function add(Error $e)
    {
        $this->errors[$e->getCode()] = $e;
    }

    /**
     * @return Error|null
     */
    public function getFirst()
    {
        return $this->errors ? reset($this->errors) : null;
    }

    public function __get($key)
    {
        return $this->errors[$key];
    }

    public function __set($key, $value)
    {
        $this->errors[$key] = $value;
    }

    public function __isset($key)
    {
        return isset($this->errors[$key]);
    }

    public function __unset($key)
    {
        unset($this->errors[$key]);
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->errors[] = $value;
        } else {
            $this->errors[$offset] = $value;
        }
    }

    public function offsetExists($offset)
    {
        return isset($this->errors[$offset]);
    }

    public function offsetUnset($offset)
    {
        if ($this->offsetExists($offset)) {
            unset($this->errors[$offset]);
        }
    }

    public function offsetGet($offset)
    {
        return $this->offsetExists($offset) ? $this->errors[$offset] : null;
    }


}
