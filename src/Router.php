<?php

namespace Celysium\Router;

use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\Router as BaseRouter;

class Router implements RouterInterface
{
    protected array $apiRoutes = [];

    protected array $routes = [];

    protected BaseRouter $baseRouter;

    public function __construct(BaseRouter $baseRouter)
    {
        $this->baseRouter = $baseRouter;
    }

    public function get(): array
    {
        $this->parser();

        return $this->routes;
    }

    protected function parser(): void
    {
        $this->apiRouteFinder();

        foreach ($this->apiRoutes as $route) {
            $this->routes[] =
                [
                    'method' => $route->methods,
                    'path' => $route->uri,
                    'name' => $route->getName() ,
                    'parameters' => $this->setRouteParameters($route->uri)
                ];
        }
    }

    protected function apiRouteFinder(): void
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

    protected function setRouteParameters(string $routeUri): array
    {
        $routeParameters = [];

        $decomposedUri = explode('/', $routeUri);

        foreach ($decomposedUri as $path) {
            if (str_starts_with($path, '{') && str_ends_with($path, '}')) {
                $routeParameters[] = [
                    'is_optional' => str_contains($path, '?') && $this->performOptionalParameter($path),
                    'name' => trim($path, "\{\}"),
                    'type' => 'string'
                ];
            }
        }

        return $routeParameters;
    }

    protected function performOptionalParameter(&$optionalParameter): bool
    {
        $optionalParameter = str_replace('?', '', $optionalParameter);

        return true;
    }
}
