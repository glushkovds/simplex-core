<?php
namespace Simplex\Core;

abstract class Response
{
    protected $statusCode = 200;
    protected $cookies = [];
    protected $headers = [];

    public function __construct()
    {
    }

    /**
     * Sets redirect url
     * @param string $url URL to redirect to
     * @return self
     */
    public function redirect(string $url): self
    {
        $this->setHeader('Location', $url);
        return $this;
    }

    /**
     * Sets the cookie
     *
     * @param string $name Name
     * @param string $value Value
     * @param array $options Options
     * @return self
     */
    public function setCookie(string $name, string $value = '', array $options = []): self
    {
        $this->cookies[$name] = [
            'value' => $value,
            'options' => $options
        ];

        return $this;
    }

    /**
     * Sets the header
     *
     * @param string $name Name
     * @param string $value Value
     * @return $this
     */
    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Sends a header with status code
     * @param int $code Status code string
     * @return self
     */
    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * Sets the content type
     * @param string $contentType Content type
     * @return self
     */
    public function setContentType(string $contentType): self
    {
        $this->setHeader('Content-Type', $contentType);
        return $this;
    }

    /**
     * Sets the content disposition header
     * @param string $fileName File name
     * @return self
     */
    public function setContentDisposition(string $fileName): self
    {
        $this->setHeader('Content-Disposition', 'attachment; filename=' . $fileName);
        return $this;
    }

    /**
     * Utility function to send a file
     *
     * @param string $fileName File name
     * @param string $fileContent Content of the file
     */
    public function outputFile(string $fileName, string $fileContent)
    {
        $this->outputHeaders();

        header('Content-Length: ' . strlen($fileContent));
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: ' . $fileName);

        echo $fileContent;
    }

    public function output()
    {
        $this->outputHeaders();
        echo $this->makeBody();
    }

    private function outputHeaders()
    {
        http_response_code($this->statusCode);

        foreach ($this->cookies as $cookieName => $cookie) {
            setcookie($cookieName, $cookie['value'], $cookie['options']);
        }

        foreach ($this->headers as $header => $value) {
            header($header . ': ' . $value);
        }
    }

    protected abstract function makeBody(): string;
}