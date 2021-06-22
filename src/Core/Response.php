<?php
namespace Simplex\Core;

use Simplex\Core\Errors\Error;
use Simplex\Core\Errors\ErrorCodes;

abstract class Response
{
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
    public static function outputFile(string $fileName, string $fileContent)
    {
        header('Content-Length: ' . strlen($fileContent));
        self::setContentType('application/octet-stream');
        self::setContentDisposition($fileName);
        echo $fileContent;
    }

    public abstract function output();
}