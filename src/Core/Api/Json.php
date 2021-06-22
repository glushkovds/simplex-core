<?php
namespace Simplex\Core\Api;

use Simplex\Core\Response;

class Json extends Base
{
    public function execute()
    {
        parent::tryAuth();

        try {
            $data = $this->{$this->getMethodName()}();
        } catch (\Throwable $ex) {
            $data = $ex;
        }

        Response::sendJson($data);
        exit;
    }
}