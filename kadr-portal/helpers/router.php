<?php

declare(strict_types=1);

namespace KadrPortal\Helpers;

class Router
{
    /**
     * @var array<string, array<int, array{pattern: string, handler: callable}>>
     */
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->routes['GET'][] = [
            'pattern' => $path,
            'handler' => $handler,
        ];
    }

    public function post(string $path, callable $handler): void
    {
        $this->routes['POST'][] = [
            'pattern' => $path,
            'handler' => $handler,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';
        $methodRoutes = $this->routes[$method] ?? [];

        foreach ($methodRoutes as $route) {
            $params = $this->match($route['pattern'], $path);

            if ($params === null) {
                continue;
            }

            if ($params === []) {
                ($route['handler'])();
                return;
            }

            ($route['handler'])(...array_values($params));
            return;
        }

        http_response_code(404);
        echo '404 Not Found';
    }

    /**
     * @return array<string, string>|null
     */
    private function match(string $pattern, string $path): ?array
    {
        if ($pattern === $path) {
            return [];
        }

        if ($pattern === '/') {
            return null;
        }

        $regex = $this->convertPatternToRegex($pattern);

        if ($regex === null) {
            return null;
        }

        if (!preg_match($regex['pattern'], $path, $matches)) {
            return null;
        }

        $params = [];

        foreach ($regex['parameters'] as $name) {
            if (isset($matches[$name])) {
                $params[$name] = $matches[$name];
            }
        }

        return $params;
    }

    /**
     * @return array{pattern: string, parameters: array<int, string>}|null
     */
    private function convertPatternToRegex(string $pattern): ?array
    {
        $trimmed = trim($pattern, '/');

        if ($trimmed === '') {
            return null;
        }

        $segments = explode('/', $trimmed);
        $regexParts = [];
        $parameters = [];

        foreach ($segments as $segment) {
            if ($segment === '') {
                return null;
            }

            if (preg_match('/^\{([a-zA-Z_][a-zA-Z0-9_]*)\}$/', $segment, $matches) === 1) {
                $name = $matches[1];
                $parameters[] = $name;
                $regexParts[] = '(?P<' . $name . '>[^/]+)';
                continue;
            }

            $regexParts[] = preg_quote($segment, '#');
        }

        $regex = '#^/' . implode('/', $regexParts) . '$#';

        return [
            'pattern' => $regex,
            'parameters' => $parameters,
        ];
    }
}
