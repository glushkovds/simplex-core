<?php
namespace Simplex\Core;

class Request
{
    /**
     * Checks if the request was POST
     * @return bool
     */
    public static function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] == 'POST';
    }

    /**
     * Checks if the request body is JSON data
     * @return bool
     */
    public static function isJson(): bool
    {
        return isset($_SERVER['HTTP_CONTENT_TYPE']) && $_SERVER['HTTP_CONTENT_TYPE'] == 'application/json';
    }

    /**
     * Checks if the request was AJAX
     * @return bool
     */
    public static function isAjax(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
    }

    /**
     * Gets GET parameters
     *
     * @param mixed|null $k
     * @return array|mixed
     */
    public static function get($k = null)
    {
        return $k ? $_GET[$k] : $_GET;
    }

    /**
     * Gets POST parameters
     *
     * @param mixed|null $k
     * @return array|mixed
     */
    public static function post($k = null)
    {
        return $k ? $_POST[$k] : $_POST;
    }

    /**
     * Gets input cookies
     *
     * @param mixed|null $k
     * @return array|mixed
     */
    public static function cookie($k = null)
    {
        return $k ? $_COOKIE[$k] : $_COOKIE;
    }

    /**
     * Gets input files
     *
     * @param null $k
     * @return array|mixed
     */
    public static function file($k = null)
    {
        return $k ? $_FILES[$k] : $_FILES;
    }

    /**
     * Gets JSON body parameters
     *
     * @return array
     */
    public static function json(): array
    {
        return json_decode(file_get_contents('php://input'), true);
    }
}