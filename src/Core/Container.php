<?php


namespace Simplex\Core;

/**
 * Class Container
 * @package App\Core
 * @method Config getConfig
 */
class Container
{

    /** @var array */
    protected static $registry = [];

    public static function set(string $name, $payload)
    {
        static::$registry[$name] = $payload;
    }

    public static function get(string $name)
    {
        return static::$registry[$name] ?? null;
    }

    public static function __callStatic($name, $arguments)
    {
        if (strpos($name, 'get') === 0) {
            $registryName = lcfirst(substr($name, 3));
            return static::get($registryName);
        }
        throw new \BadMethodCallException();
    }

}