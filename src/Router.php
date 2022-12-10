<?php

namespace Celysium\Router;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\Router as BaseRouter;
use function PHPUnit\Framework\isInstanceOf;

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

    protected function setRouteBodyRequest(\Illuminate\Routing\Route $route): array
    {
        // before you should check if it is not closure perform this method otherwise return another method
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
            $parameterClassInstance = new $parameterClassPath();

            if (is_subclass_of($parameterClassInstance, FormRequest::class)) {
                // so it is request validation
                $requestValidationReflection = new \ReflectionClass($parameterClassPath);

                $refMethod = $requestValidationReflection->getMethod('rules');

                return $this->getRequestValidationBodyByFile(
                    $refMethod->getFileName(),
                    $refMethod->getStartLine(),
                    $refMethod->getEndLine()
                );

            } elseif ($parameterClassInstance instanceof Request) {
                // it is closure in controller
                return $this->getRequestValidationBodyByFile(
                    $reflectionControllerMethod->getFileName(),
                    $reflectionControllerMethod->getStartLine(),
                    $reflectionControllerMethod->getEndLine(),
                    true
                );
            }
        }
    }

    protected function getRequestValidationBodyByFile(string $filePath, int $startLine, int $endline, $isClosureInController = false): array
    {
        $fileContentIntoArray = file($filePath);

        $neededLines = array_slice($fileContentIntoArray, $startLine, $endline);

        if ($isClosureInController)
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
