<?php


namespace Simplex\Core\Cookie;


class CookieItem
{
    /** @var string */
    protected $key;
    /** @var string|null */
    protected $value;
    /** @var \DateTime */
    protected $expires;

    /**
     * CookieItem constructor.
     * @param string $key
     * @param string $value
     * @param null $expires
     */
    public function __construct($key, $value = null, $expires = null)
    {
        $this->key = $key;
        $this->value = $value ?? $_COOKIE[$key] ?? null;
        $this->expires = $expires;
    }

    public function save()
    {
        if (headers_sent()) {
            throw new \Exception("Cannot modify header information - headers already sent");
        }
        $_COOKIE[$this->key] = $this->value;
        setcookie($this->key, $this->value, $this->expires ? $this->expires->getTimestamp() : 0, "/");
    }

    /**
     * @param \DateTime $expires
     * @return $this
     */
    public function setExpires(\DateTime $expires)
    {
        $this->expires = $expires;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setValue(string $value)
    {
        $this->value = $value;
        return $this;
    }

}