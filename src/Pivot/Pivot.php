<?php

namespace Celysium\Router\Pivot;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
//
class Pivot
{
    protected array $routes;

    public function __construct(array $routes)
    {
        $this->routes = $routes;
    }

    /**
     * Give me all api routes
     *
     * @return array
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Give me all methods that is used for api routes
     *
     * @return Collection
     */
    public function getMethods(): Collection
    {
        $methods = Arr::pluck($this->routes, 'method');

        $result = collect(Arr::collapse($methods));

        return $result->unique();
    }
}
