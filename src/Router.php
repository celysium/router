<?php

namespace Celysium\Router;

use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\Router as BaseRouter;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Router implements RouterInterface
{
    protected array $apiRoutes = [];

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

    public function parser(): void
    {
        $result = [];

        $this->apiRouteFinder();

        foreach ($this->apiRoutes as $route) {
            $this->routes[] =
                [
                    'method' => $route->methods,
                    'path' => $route->uri,
                ];
        }
    }

    public function apiRouteFinder(): void
    {
        /** @var RouteCollection $routesCollection */
        $allRoutes = $this->baseRouter
            ->getRoutes()
            ->getRoutes();


        foreach ($allRoutes as $route) {

            $action = $route->action;

            if (!Str::startsWith($action['prefix'], 'api')) {
                continue;
            }

            $this->apiRoutes[] = $route;
        }
    }
}

