<?php
declare(strict_types=1);

namespace App\Core;

// Controller base com atalhos para views HTML e respostas JSON.
class Controller
{
    protected function view(string $view, array $data = [], string $layout = 'admin'): void
    {
        View::render($view, $data, $layout);
    }

    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}
