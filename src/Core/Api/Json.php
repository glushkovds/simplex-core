<?php
namespace Simplex\Core\Api;

class Json extends Base
{
    public function execute(): string
    {
        $request = new JsonRequest();
        $response = new JsonResponse();

        try {
            if ($this->requireAuth) {
                $this->user = $this->assertAuthenticated();
            }

            $response->setData($this->{$this->getMethodName()}($request, $response));
        } catch (\Throwable $ex) {
            $response->setError($ex);
        }

        $response->output();
        exit;
    }
}