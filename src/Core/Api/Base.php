<?php
namespace Simplex\Core\Api;

use Simplex\Core\Core;
use Simplex\Core\Errors\Error;
use Simplex\Core\Errors\ErrorCodes;
use Simplex\Core\Response;
use Simplex\Core\User;

abstract class Base
{
    /**
     * @var bool Should require authentication on all methods?
     */
    protected $requireAuth = false;

    /**
     * @return string
     */
    public abstract function execute(): string;

    /**
     * Gets name of the method to execute
     *
     * @throws \Simplex\Core\Errors\Error
     * @return string
     */
    protected function getMethodName(): string
    {
        $name = 'action' . ucfirst(Core::uri(0) ?: 'index');
        if (!method_exists($this, $name)) {
            throw Error::byCode(ErrorCodes::APP_METHOD_NOT_FOUND);
        }

        return $name;
    }

    protected function auth()
    {
        static::tryAuthBasic();

        if (!User::$id && $this->requireAuth) {
            throw Error::byCode(ErrorCodes::APP_UNAUTHORIZED);
        }
    }

    protected function tryAuthBasic()
    {
        if ($login =& $_SERVER['PHP_AUTH_USER']) {
            $pass = $_SERVER['PHP_AUTH_PW'] ?? '';
            User::authorizeOnce($login, $pass);
        }
    }
}
