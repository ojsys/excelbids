<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Small pattern router. Routes are declared as literal paths with optional
 * {placeholders}; handlers are "Controller@method" strings resolved lazily.
 */
final class Router
{
    /** @var array<string,array<int,array{pattern:string,regex:string,keys:array<int,string>,handler:mixed,middleware:array<int,string>}>> */
    private array $routes = ['GET' => [], 'POST' => [], 'PUT' => [], 'PATCH' => [], 'DELETE' => []];

    /** @var array<int,string> */
    private array $groupMiddleware = [];

    private string $groupPrefix = '';

    public function get(string $pattern, $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    /** Register the same handler for GET and POST (forms that render and submit). */
    public function form(string $pattern, $handler): void
    {
        $this->add('GET', $pattern, $handler);
        $this->add('POST', $pattern, $handler);
    }

    /**
     * Group routes behind a shared prefix and middleware stack.
     *
     * @param array<int,string> $middleware
     */
    public function group(string $prefix, array $middleware, callable $callback): void
    {
        $previousPrefix = $this->groupPrefix;
        $previousMiddleware = $this->groupMiddleware;

        $this->groupPrefix = rtrim($previousPrefix . '/' . trim($prefix, '/'), '/');
        $this->groupMiddleware = array_merge($previousMiddleware, $middleware);

        $callback($this);

        $this->groupPrefix = $previousPrefix;
        $this->groupMiddleware = $previousMiddleware;
    }

    private function add(string $method, string $pattern, $handler): void
    {
        $full = $this->groupPrefix . '/' . trim($pattern, '/');
        $full = '/' . trim($full, '/');
        if ($full === '/') {
            $full = '/';
        }

        $keys = [];
        $regex = preg_replace_callback('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', static function (array $m) use (&$keys): string {
            $keys[] = $m[1];
            return '([^/]+)';
        }, $full);

        $this->routes[$method][] = [
            'pattern'    => $full,
            'regex'      => '#^' . $regex . '$#',
            'keys'       => $keys,
            'handler'    => $handler,
            'middleware' => $this->groupMiddleware,
        ];
    }

    public function dispatch(Request $request): void
    {
        $method = $request->method();
        $uri = $request->uri();

        foreach ($this->routes[$method] ?? [] as $route) {
            if (!preg_match($route['regex'], $uri, $matches)) {
                continue;
            }

            array_shift($matches);
            $params = [];
            foreach ($route['keys'] as $i => $key) {
                $params[$key] = $matches[$i] ?? null;
            }

            foreach ($route['middleware'] as $middleware) {
                Middleware::run($middleware, $request);
            }

            $this->call($route['handler'], $request, $params);
            return;
        }

        // Path exists under another verb — surface that rather than a bare 404.
        foreach ($this->routes as $verb => $routes) {
            if ($verb === $method) {
                continue;
            }
            foreach ($routes as $route) {
                if (preg_match($route['regex'], $uri)) {
                    Response::error(405, 'Method not allowed');
                    return;
                }
            }
        }

        Response::error(404, 'Page not found');
    }

    /** @param array<string,mixed> $params */
    private function call($handler, Request $request, array $params): void
    {
        if (is_callable($handler)) {
            $handler($request, $params);
            return;
        }

        if (!is_string($handler) || !str_contains($handler, '@')) {
            throw new RuntimeException('Invalid route handler.');
        }

        [$class, $method] = explode('@', $handler, 2);
        $class = 'App\\Controllers\\' . $class;

        if (!class_exists($class)) {
            throw new RuntimeException("Controller {$class} not found.");
        }

        $controller = new $class();
        if (!method_exists($controller, $method)) {
            throw new RuntimeException("Method {$method} not found on {$class}.");
        }

        $controller->{$method}($request, $params);
    }
}
