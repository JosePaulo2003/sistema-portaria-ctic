<?php
declare(strict_types=1);

namespace App\Core;

// Roteador simples que transforma URL + metodo HTTP em chamada de controller.
class Router
{
    private array $routes = [];

    public function get(string $path, array|callable $handler): void
    {
        $this->add('GET', $path, $handler, false);
    }

    public function post(string $path, array|callable $handler): void
    {
        $this->add('POST', $path, $handler, true);
    }

    // Uso exclusivo para webhooks autenticados por segredo proprio, sem cookie.
    public function postWithoutCsrf(string $path, array|callable $handler): void
    {
        $this->add('POST', $path, $handler, false);
    }

    private function add(string $method, string $path, array|callable $handler, bool $requiresCsrf): void
    {
        $this->routes[$method][rtrim($path, '/') ?: '/'] = [
            'handler' => $handler,
            'requires_csrf' => $requiresCsrf,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method);
        $base = basePath();
        if ($base !== '' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base)) ?: '/';
        }

        $path = rtrim('/' . ltrim($uri, '/'), '/') ?: '/';
        $route = $this->routes[$method][$path] ?? null;

        if (!$route) {
            http_response_code(404);
            echo 'PÃ¡gina nÃ£o encontrada.';
            return;
        }

        if ($method === 'POST' && ($route['requires_csrf'] ?? false)) {
            verifyCsrf();
        }

        $handler = $route['handler'];
        if (is_callable($handler)) {
            $handler();
            return;
        }

        [$controller, $action] = $handler;
        (new $controller())->$action();
    }
}
