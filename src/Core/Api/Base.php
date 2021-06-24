<?php
namespace Simplex\Core\Api;

use Simplex\Core\Core;
use Simplex\Core\Errors\Error;
use Simplex\Core\Errors\ErrorCodes;
use Simplex\Core\Response;
use Simplex\Core\User;

class Base
{
    public function execute()
    {
        $this->tryAuth();

        try {
            return $this->{$this->getMethodName()}();
        } catch (\Throwable $ex) {
            http_response_code(404);
            return $ex->getCode() . ' ' . $ex->getMessage();
        }
    }

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

    protected function tryAuth()
    {
        static::tryAuthBasic();
    }

    protected function tryAuthBasic()
    {
        if ($login =& $_SERVER['PHP_AUTH_USER']) {
            $pass = $_SERVER['PHP_AUTH_PW'] ?? '';
            User::authorizeOnce($login, $pass);
        }
    }
}
