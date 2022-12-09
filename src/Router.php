<?php

namespace Celysium\Router;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
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

    protected function setRouteBodyRequest(\Illuminate\Routing\Route $route): array
    {
        // before you should check if it is not closutre pefrom this method otherwise return another method
        $routeController = explode('@', $route->getAction()['controller']);

        [$routeControllerClass, $routeControllerAction] = [
            $routeController[0],
            $routeController[1]
        ];

        $reflectionControllerClass = new \ReflectionClass($routeControllerClass);
        $reflectionControllerMethod = $reflectionControllerClass->getMethod($routeControllerAction);

        $refMethodParameters = $reflectionControllerMethod->getParameters();

        foreach ($refMethodParameters as $methodParameter) {

            $parameterClassPath = $methodParameter->getType()->getName(); // TODO : put this to try catch because maybe it has int , string or ...
            $paramterClassInstance = new $parameterClassPath();

            if (is_subclass_of($paramterClassInstance, FormRequest::class)) {
                // so it is request validation
                $requestValidationReflection = new \ReflectionClass($parameterClassPath);

                $refMethod = $requestValidationReflection->getMethod('rules');

                $this->getRequestValidationBodyByFile(
                    $refMethod->getFileName(),
                    $refMethod->getStartLine(),
                    $refMethod->getEndLine()
                );

            } elseif ($paramterClassInstance instanceof Request) {
                // it is closure in controller
                dump('khodesh');
            }
        }

        // get all paramters
        // check if are extended from base validator
        // then get name of the class
        // if it is base class it means it is closure
        // if it is not base class so get reflection class , method , body of the it
    }

    protected function getRequestValidationBodyByFile(string $filePath, int $startLine, int $endline)
    {
        // TODO : for this function check [] and | (pipeline)
        $fileContentIntoArray = file($filePath);

        $neededLines = array_slice($fileContentIntoArray, $startLine, $endline);

        foreach ($neededLines as $line) {
            if (!str_contains($line, '=>')) {
                continue;
            }

            dump($line);
        }
        dd('end');
    }
}
