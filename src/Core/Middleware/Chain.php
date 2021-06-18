<?php


namespace Simplex\Core\Middleware;


class Chain implements Handler
{

    /** @var Handler[] */
    protected $middlewares = [];

    /**
     * Chain constructor.
     * @param Handler[] $middlewares
     */
    public function __construct($middlewares)
    {
        $this->middlewares = $middlewares;
    }

    public function handle($payload, Handler $next)
    {
        $handler = array_shift($this->middlewares);
        return $handler ? $handler->handle($payload, $this) : $payload;
    }

    public function process($payload)
    {
        return $this->handle($payload, new GagHandler());
    }

}