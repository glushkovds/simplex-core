<?php


namespace Simplex\Core;

/**
 * Class Container
 *
 * @package App\Core
 * @method static Config getConfig
 * @method static Page getPage
 * @method static Core getCore
 * @method static \Simplex\Core\Request getRequest
 * @method static \Simplex\Core\Response getResponse
 * @method static User getUserLegacy
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

    public static function isSet(string $name): bool
    {
        return isset(static::$registry[$name]);
    }

    public static function __callStatic($name, $arguments)
    {
        if (strpos($name, 'get') === 0) {
            return static::get(lcfirst(substr($name, 3)));
        }

        throw new \BadMethodCallException();
    }

    public static function setAuthHandler(callable $closure)
    {
        static::$registry['authHandler'] = $closure;
    }

    /**
     * @return \Simplex\Core\Models\User|null
     */
    public static function getUser()
    {
        if (
            !($called =& static::$registry['authHandlerCalled'])
            && ($authHandler =& static::$registry['authHandler'])
            && is_callable($authHandler)
        ) {
            $authHandler();
            $called = true;
        }
        return static::get('user');
    }
}