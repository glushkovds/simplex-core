<?php


namespace Simplex\Core\Cookie;


class CookieBag
{
    protected $prefix = '';
    /** @var CookieItem[] */
    protected $cookies = [];
    protected $prolongPeriod = '1 WEEK';

    public function __construct($prefix)
    {
        $this->prefix = $prefix;
    }

    protected function save()
    {
        foreach ($this->cookies as $cookie) {
            $cookie->save();
        }
    }

    public function prolong()
    {
        $expires = new \DateTime('+' . $this->prolongPeriod);
        foreach ($this->cookies as $cookie) {
            $cookie->setExpires($expires)->save();
        }
    }
}