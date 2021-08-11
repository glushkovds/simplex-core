<?php


namespace Simplex\Core;

/**
 * Class Container
 *
 * @package App\Core
 * @method static Config getConfig
 * @method static Page getPage
 * @method static Core getCore
 * @method static User getUser
 * @method static \Simplex\Core\Request getRequest
 * @method static \Simplex\Core\Response getResponse
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
            return static::get(lcfirst(substr($name, 3)));
        }

        throw new \BadMethodCallException();
    }
}