<?php
namespace Simplex\Core\Api;

use Simplex\Core\Errors\Error;
use Simplex\Core\Errors\ErrorCodes;

class Json extends Base
{
    public function execute()
    {
        try {
            static::tryAuth();
            $response = new JsonResponse($this->{$this->getMethodName()}());
        } catch (\Throwable $ex) {
            $response = new JsonResponse($ex);
        }

        $response->output();
        exit;
    }
}