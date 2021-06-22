<?php
namespace Simplex\Core\Api;

class Json extends Base
{
    public function execute()
    {
        parent::tryAuth();

        try {
            // try executing the method
            $data = $this->{$this->getMethodName()}();
        } catch (\Throwable $ex) {
            // set data to exception instead
            $data = $ex;
        }

        JsonResponse::output($data);
        exit;
    }
}