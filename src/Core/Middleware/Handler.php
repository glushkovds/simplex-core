<?php

namespace Simplex\Core\Middleware;

interface Handler
{
    /**
     * @param mixed $payload any data
     * @param Handler $next
     * @return mixed handled payload
     */
    public function handle($payload, Handler $next);
}