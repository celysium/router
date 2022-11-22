<?php

namespace Celysium\Router;

use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\Router as BaseRouter;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Router implements RouterInterface
{
    protected array $routes = [];

    public function __construct(
        protected BaseRouter $baseRouter
    )
    {
    }

    public function get(): Collection
    {
        return collect($this->routes);
    }

    public function parser(): array
    {
        $result = [];

        $this->apiRouteFinder();

        foreach ($this->routes as $route) {
            $result[] =
                [
                    'method' => $route->methods,
                    'path' => $route->uri,
                ];
        }

        return $result;
    }

    public function apiRouteFinder(): void
    {
        /** @var RouteCollection $routesCollection */
        $routesCollection = $this->baseRouter->getRoutes();
        dd($routesCollection);
        $arrayRoutes = $routesCollection->getRoutes();

        /** @var Route $route */
        foreach ($arrayRoutes as $route) {

            $action = $route->action;

            if (!Str::startsWith($action['prefix'], 'api')) {
                continue;
            }

            $this->routes[] = $route;
        }
    }
}

