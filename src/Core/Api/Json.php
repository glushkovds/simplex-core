<?php
namespace Simplex\Core\Api;

class Json extends Base
{
    public function execute()
    {
        static::tryAuth();

        try {
            $response = new JsonResponse($this->{$this->getMethodName()}());
        } catch (\Throwable $ex) {
            $response = new JsonResponse($ex);
        }

        $response->output();
        exit;
    }
}