<?php

namespace Simplex\Core;

use Simplex\Core\Errors\ErrorCodes;

class ApiJson extends ApiBase
{
    public function execute()
    {
        parent::tryAuth();
        header('Content-Type: application/json');

        $method = 'action' . ucfirst(Core::uri(0) ?: 'index');
        if ($method && method_exists($this, $method)) {
            try {
                $data = json_decode(file_get_contents('php://input'), true) + $_GET;
                $response = ApiResponse::fromMixed($this->$method($data));
            } catch (\Throwable $e) {
                $response = (new ApiResponse)->setError($e->getCode(), $e->getMessage());
            }

            exit(json_encode($response->toArray(), JSON_UNESCAPED_UNICODE));
        }

        header("HTTP/1.0 404 Not Found");

        $response = (new ApiResponse)->setError(ErrorCodes::APP_METHOD_NOT_FOUND);
        exit(json_encode($response->toArray(), JSON_UNESCAPED_UNICODE));
    }
}