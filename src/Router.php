<?php

namespace Celysium\Router;


use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\Router as BaseRouter;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Router implements RouterInterface
{
    protected array $apiRoutes = [];

    protected array $routes = [];

    protected BaseRouter $baseRouter;

    /**
     * @throws BindingResolutionException
     */
    public function __construct(
    )
    {
        $this->baseRouter = app()->make(BaseRouter::class);
    }

    public function get(): Collection
    {
        return collect($this->routes);
    }

    public function parser(): void
    {
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
        $routesCollection = $this->baseRouter
            ->getRoutes();

        $arrayRoutes = $routesCollection
            ->getRoutes();

        foreach ($arrayRoutes as $route) {

            $action = $route->action;

            if (!Str::startsWith($action['prefix'], 'api')) {
                continue;
            }

            $this->apiRoutes[] = $route;
        }
    }
}

