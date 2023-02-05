<?php

namespace Simplex\Core;

class Request
{
    protected $isHttps;
    protected $host;
    protected $requestMethod;
    protected $headers;
    protected $getParams;
    protected $postParams;
    protected $cookies;
    protected $files;
    protected $requestBody;
    protected $urlPath;
    protected $urlParts;
    protected $serverInfo = [];

    /**
     * Request constructor.
     */
    public function __construct()
    {
        // cache values
        $this->host = $_SERVER['HTTP_HOST'];
        $this->isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off';
        $this->requestMethod = $_SERVER['REQUEST_METHOD'];
        $this->getParams = $_GET;
        $this->postParams = $_POST;
        $this->cookies = $_COOKIE;
        $this->files = $_FILES;
        $this->requestBody = file_get_contents('php://input');

        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                // convert HTTP_X_X to x-x
                $this->headers[strtolower(str_replace('_', '-', substr($key, 5)))] = $value;
            }
        }

        // parse and compose URL
        $urlData = parse_url($_SERVER['REQUEST_URI']);
        $this->urlPath = $urlData['path'];
        $this->urlParts = array_slice(explode('/', $this->urlPath), 1);
        $this->serverInfo = $_SERVER;
    }

    public function getFullUrl(): string
    {
        return ($this->isHttps ? 'https://' : 'http://') . $this->host . $this->urlPath;
    }

    /**
     * Returns full URL path
     * @return string
     */
    public function getPath(): string
    {
        return $this->urlPath;
    }

    /**
     * Returns broken down URL parts
     * @return array
     */
    public function getUrlParts(): array
    {
        return $this->urlParts;
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

    /**
     * @return bool
     */
    public function isHttps(): bool
    {
        return $this->isHttps;
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @return array
     */
    public function getServerInfo(): array
    {
        return $this->serverInfo;
    }
}