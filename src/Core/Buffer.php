<?php

namespace Simplex\Core;


class Buffer
{

    protected static $data = [];

    /**
     * @param string $key
     * @param callable $callback
     * @return mixed
     */
    public static function getOrSet($key, callable $callback)
    {
        if (!array_key_exists($key, static::$data)) {
            static::$data[$key] = $callback();
        }
        return static::$data[$key];
    }

    /**
     * @param string $key
     */
    public static function delete($key)
    {
        if (array_key_exists($key, static::$data)) {
            unset(static::$data[$key]);
        }
    }

}