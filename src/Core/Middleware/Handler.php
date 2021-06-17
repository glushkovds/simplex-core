<?php

namespace Simplex\Core\Middleware;

interface Handler
{
    /**
     * @param mixed $payload any data
     * @param \Closure $next
     * @return mixed handled payload
     */
    public function handle($payload, \Closure $next);
}