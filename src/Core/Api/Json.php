<?php
namespace Simplex\Core\Api;

class Json extends Base
{
    public function execute()
    {
        parent::tryAuth();
        header('Content-Type: application/json');

        try {
            $response = Response::fromMixed($this->{$this->getMethodName()}());
        } catch (\Throwable $e) {
            $response = Response::fromThrowable($e);
        }

        exit(json_encode($response->toArray(), JSON_UNESCAPED_UNICODE));
    }
}