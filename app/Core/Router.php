<?php
declare(strict_types=1);

namespace App\Core;

// Roteador simples que transforma URL + metodo HTTP em chamada de controller.
class Router
{
    private array $routes = [];

    public function get(string $path, array|callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, array|callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    private function add(string $method, string $path, array|callable $handler): void
    {
        $this->routes[$method][rtrim($path, '/') ?: '/'] = $handler;
    }

    public function dispatch(string $method, string $uri): void
    {
        $base = basePath();
        if ($base !== '' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base)) ?: '/';
        }

        $path = rtrim('/' . ltrim($uri, '/'), '/') ?: '/';
        $handler = $this->routes[$method][$path] ?? null;

        if (!$handler) {
            http_response_code(404);
            echo 'PÃ¡gina nÃ£o encontrada.';
            return;
        }

        if (is_callable($handler)) {
            $handler();
            return;
        }

        [$controller, $action] = $handler;
        (new $controller())->$action();
    }
}
