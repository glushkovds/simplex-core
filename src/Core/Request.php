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
        return ($_SERVER['HTTP_CONTENT_TYPE'] ?? null) == 'application/json';
    }

    /**
     * Checks if the request was AJAX
     * @return bool
     */
    public static function isAjax(): bool
    {
        return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? null) == 'XMLHttpRequest';
    }

    /**
     * Gets GET parameters
     *
     * @param mixed|null $k
     * @return array|mixed|null
     */
    public static function get($k = null)
    {
        return $k ? ($_GET[$k] ?? null) : $_GET;
    }

    /**
     * Gets POST parameters
     *
     * @param mixed|null $k
     * @return array|mixed|null
     */
    public static function post($k = null)
    {
        return $k ? ($_POST[$k] ?? null) : $_POST;
    }

    /**
     * Gets input cookies
     *
     * @param mixed|null $k
     * @return array|mixed|null
     */
    public static function cookie($k = null)
    {
        return $k ? ($_COOKIE[$k] ?? null) : $_COOKIE;
    }

    /**
     * Gets input files
     *
     * @param mixed|null $k
     * @return array|mixed|null
     */
    public static function file($k = null)
    {
        return $k ? ($_FILES[$k] ?? null) : $_FILES;
    }

    /**
     * Gets JSON body parameters
     *
     * @return array
     */
    public static function json(): array
    {
        return json_decode(file_get_contents('php://input'), true) ?: [];
    }
}