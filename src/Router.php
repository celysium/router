<?php

namespace Celysium\Router;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Routing\RouteCollection;
use \Illuminate\Routing\Route as baseRoute;
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
                    'name' => $route->getName(),
                    'parameters' => $this->setRouteParameters($route->uri),
                    'body_parameters' => $this->setRouteBodyRequest($route)
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

    protected function setRouteBodyRequest(BaseRoute $route): array
    {
        if (is_callable($route->action['uses']))
            return $this->getBodyRequestFromClosure($route);

        return $this->getBodyRequestFromController($route);
    }

    protected function getBodyRequestFromClosure(BaseRoute $route): array
    {
        $reflectionClosure = new \ReflectionFunction($route->action['uses']);

        return $this->getRequestParameters(
            $reflectionClosure->getParameters(),
            $reflectionClosure
        );
    }

    protected function getBodyRequestFromController(BaseRoute $route): array
    {
        // TODO : check if it is controller then ... if not return null or sth like this []
        $routeController = explode('@', $route->getAction()['controller']);

        [$routeControllerClass, $routeControllerAction] = [
            $routeController[0],
            $routeController[1]
        ];

        $reflectionControllerClass = new \ReflectionClass($routeControllerClass);
        $reflectionControllerMethod = $reflectionControllerClass->getMethod($routeControllerAction);

        $refMethodParameters = $reflectionControllerMethod->getParameters();

        return $this->getRequestParameters($refMethodParameters, $reflectionControllerMethod);
    }

    protected function getRequestParameters(array $parameters, \ReflectionMethod|\ReflectionFunction $reflectionMethod = null): array
    {
        foreach ($parameters as $parameter) {

            $parameter = $parameter->getType()->getName();
            $reflectionClass = new \ReflectionClass($parameter);

            if ($reflectionClass->isSubclassOf(FormRequest::class)) {
                $reflectionRulesMethod = $reflectionClass->getMethod('rules');

                return $this->getRequestValidationBodyByFile(
                    $reflectionRulesMethod->getFileName(),
                    $reflectionRulesMethod->getStartLine(),
                    $reflectionRulesMethod->getEndLine()
                );
            } elseif ($reflectionClass->isInstance(app()->make(Request::class))) {

                return $this->getRequestValidationBodyByFile(
                    $reflectionMethod->getFileName(),
                    $reflectionMethod->getStartLine(),
                    $reflectionMethod->getEndLine(),
                    true
                );
            } else {
                continue;
            }
        }
    }

    protected function getRequestValidationBodyByFile(string $filePath, int $startLine, int $endLine, $isClosure = false): array
    {
        $fileContentIntoArray = file($filePath);

        $neededLines = array_slice($fileContentIntoArray, $startLine, $endLine);

        if ($isClosure)
            return $this->getClosureControllerRequestValidation($neededLines);

        return $this->getFormRequestValidation($neededLines);
    }

    protected function separateByPipeLine(string $line): array
    {
        $arrayKeyValues = preg_split('[=>]', $line);

        [$key, $values] = [
            $this->castArrayKey($arrayKeyValues[0]),
            $this->castArrayValues($arrayKeyValues[1])
        ];

        return [
            [
                'name' => $key,
                'rules' => explode('|', trim($values, '\,'))
            ]
        ];
    }

    protected function separateByArray(string $line): array
    {
        $arrayKeyValues = preg_split('[=>]', $line);

        [$key, $values] = [
            $this->castArrayKey($arrayKeyValues[0]),
            $this->castArrayValues($arrayKeyValues[1], true)
        ];

        return [
            [
                'name' => $key,
                'rules' => array_filter(explode(',', $values))
            ]
        ];
    }

    protected function castArrayKey(string $arrayKey): string
    {
        return str_replace("'", '', trim($arrayKey));
    }

    protected function castArrayValues(string $arrayValues, $isSeparateByArray = false): string
    {
        $castedValues = str_replace("'", '', trim($arrayValues));

        if ($isSeparateByArray)
            $castedValues = str_replace(array('[', ']'), '', $castedValues);

        return $castedValues;
    }

    protected function getClosureControllerRequestValidation(array $lines): array
    {
        $result = [];

        foreach ($lines as $line) { // TODO : check another way because maybe it exist => for another arrays
            if (!str_contains($line, '=>')) {
                continue;
            }

            if (str_contains($line, '|')) {
                $result[] = $this->separateByPipeLine($line);
            } else {
                $result[] = $this->separateByArray($line);
            }
        }

        return $result;
    }

    protected function getFormRequestValidation(array $lines): array
    {
        $result = [];

        foreach ($lines as $line) {
            if (!str_contains($line, '=>')) {
                continue;
            }

            if (str_contains($line, '|')) {
                $result[] = $this->separateByPipeLine($line);
            } else {
                $result[] = $this->separateByArray($line);
            }
        }

        return $result;
    }
}
