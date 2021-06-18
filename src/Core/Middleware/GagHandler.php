<?php


namespace Simplex\Core\Middleware;


class GagHandler implements Handler
{

    public function handle($payload, Handler $next)
    {
        return $payload;
    }
}