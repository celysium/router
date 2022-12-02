<?php

namespace Celysium\Router;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\Router as BaseRouter;

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

    public function get(): array
    {
        $this->parser();

        return $this->routes;
    }

    public function parser(): void
    {
        $this->apiRouteFinder();

        foreach ($this->apiRoutes as $route) {
            $this->routes[] =
                [
                    'method' => $route->methods,
                    'path' => $route->uri,
                    'parameters' => $this->setRouteParameters($route->uri)
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
            if (!in_array('api', $route->action['middleware'])) {
                continue;
            }

            $this->apiRoutes[] = $route;
        }
    }

    public function setRouteParameters(string $routeUri): array
    {
        $routeParameters = [];

        $decomposedUri = explode('/', $routeUri);

        foreach ($decomposedUri as $path) {
            if (str_starts_with($path, '{') && str_ends_with($path, '}')) {
                $routeParameters[] = [
                    'name' => trim($path, "\{\}"),
                    'type' => 'string'
                ];
            }
        }

        return $routeParameters;
    }
}
