<?php
namespace Simplex\Core\Api;

use Simplex\Core\Errors\Error;
use Simplex\Core\Errors\ErrorCodes;

class Json extends Base
{
    public function execute()
    {
        static::tryAuth();

        try {
            if (!$this->isAuthenticated() && $this->requireAuth) {
                throw Error::byCode(ErrorCodes::APP_UNAUTHORIZED);
            }

            $response = new JsonResponse($this->{$this->getMethodName()}());
        } catch (\Throwable $ex) {
            $response = new JsonResponse($ex);
        }

        $response->output();
        exit;
    }
}