<?php
namespace Simplex\Core\Api;

use Simplex\Core\Response;

class Json extends Base
{
    public function execute()
    {
        parent::tryAuth();

        Response::sendJson($this->{$this->getMethodName()}());
        exit;
    }
}