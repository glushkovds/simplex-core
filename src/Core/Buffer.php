<?php

namespace Simplex\Core;


class Buffer
{

    protected static $data = [];

    public static function getOrSet($key, callable $callback)
    {
        if (!array_key_exists($key, static::$data)) {
            static::$data[$key] = $callback();
        }
        return static::$data[$key];
    }

}