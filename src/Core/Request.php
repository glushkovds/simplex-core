<?php
namespace Simplex\Core;

class Request
{
    protected $requestMethod;
    protected $headers;
    protected $getParams;
    protected $postParams;
    protected $cookies;
    protected $files;
    protected $requestBody;

    /**
     * Request constructor.
     */
    public function __construct()
    {
        $this->requestMethod = $_SERVER['REQUEST_METHOD'];
        $this->getParams = $_GET;
        $this->postParams = $_POST;
        $this->cookies = $_COOKIE;
        $this->files = $_FILES;
        $this->requestBody = file_get_contents('php://input');

        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') == 0) {
                // convert HTTP_X_X to x-x
                $this->headers[strtolower(str_replace('_', '-', substr($key, 5)))] = $value;
            }
        }
    }

    /**
     * Checks if the request was POST
     * @return bool
     */
    public function isPost(): bool
    {
        return $this->requestMethod == 'POST';
    }

    /**
     * Checks if the request body is JSON data
     * @return bool
     */
    public function isJson(): bool
    {
        return $this->header('Content-Type') == 'application/json';
    }

    /**
     * Checks if the request was AJAX
     * @return bool
     */
    public function isAjax(): bool
    {
        return $this->header('X-Requested-With') == 'XMLHttpRequest';
    }

    /**
     * Gets GET parameters
     *
     * @param mixed|null $k
     * @return array|mixed|null
     */
    public function get($k = null)
    {
        return $k ? ($this->getParams[$k] ?? null) : $this->getParams;
    }

    /**
     * Gets POST parameters
     *
     * @param mixed|null $k
     * @return array|mixed|null
     */
    public function post($k = null)
    {
        return $k ? ($this->postParams[$k] ?? null) : $this->postParams;
    }

    /**
     * Gets input cookies
     *
     * @param mixed|null $k
     * @return array|mixed|null
     */
    public function cookie($k = null)
    {
        return $k ? ($this->cookies[$k] ?? null) : $this->cookies;
    }

    /**
     * Gets input files
     *
     * @param mixed|null $k
     * @return array|mixed|null
     */
    public function file($k = null)
    {
        return $k ? ($this->files[$k] ?? null) : $this->files;
    }

    /**
     * Returns input headers
     *
     * @param string|null $k
     * @return mixed|null
     */
    public function header(?string $k = null)
    {
        return $k ? ($this->headers[strtolower($k)] ?? null) : $this->headers;
    }
}