<?php

namespace Simplex\Core\Routing;

use Simplex\Core\Container;

class Resolver
{
    protected $menuByLink = [];

    public function resolve(string $routesFile): Route
    {
        $routes = include $routesFile;
        $request = Container::getRequest();
        if ($route =& $routes[trim($request->getPath(), '/')]) {
            return static::makeRoute($route);
        }
        $uri = $request->getUrlParts();
        $path = '';
        $uris = [];
        foreach ($uri as $u) {
            if ($u) {
                $path .= '/' . $u;
                $uris[] = $path;
            }
        }
        foreach (array_reverse($uris) as $path) {
            if ($route =& $routes[$path]) {
                return static::makeRoute($route);
            }
        }
        if ($className = $this->resolveDeprecated()) {
            return static::makeRoute($className);
        }
        if ($routeDefault = $routes['/'] ?? null) {
            return static::makeRoute($routeDefault);
        }
        if ($classDefault = Container::getConfig()::$component_default) {
            return static::makeRoute($classDefault);
        }
        throw new \Exception("Can't resolve route {$request->getPath()}");
    }

    /**
     * @param array $menuByLink
     * @return Resolver
     */
    public function setMenuByLink(array $menuByLink): Resolver
    {
        $this->menuByLink = $menuByLink;
        return $this;
    }

    protected function resolveDeprecated(): ?string
    {
        if (empty($this->menuByLink)) {
            return null;
        }
        $class = null;
        $path = '/';
        $i = 0;
        if (!empty($this->menuByLink[md5($path)]['class'])) {
            $class = $this->menuByLink[md5($path)]['class'];
        }
        $request = Container::getRequest();
        $uri = $request->getUrlParts();
        foreach ($uri as $u) {
            if ($u) {
                $path .= $u . '/';
                if (!empty($this->menuByLink[md5($path)])) {
                    $class = $this->menuByLink[md5($path)]['class'] ?? null;
                }
            }
            $i++;
        }
        return $class;
    }

    /**
     * @param string|array|Route $route
     * @return Route
     */
    protected static function makeRoute($route): Route
    {
        if (is_string($route)) {
            return new Route($route);
        }
        if (is_array($route)) {
            return new Route($route[0], $route[1] ?? null);
        }
        if ($route instanceof Route) {
            return $route;
        }
        throw new \InvalidArgumentException("Can't resolve route " . var_export($route, true));
    }
}