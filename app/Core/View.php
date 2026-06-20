<?php
declare(strict_types=1);

namespace App\Core;

// Renderiza uma view dentro de um layout, isolando variaveis por escopo.
class View
{
    public static function render(string $view, array $data = [], string $layout = 'admin'): void
    {
        $viewFile = dirname(__DIR__) . '/Views/' . $view . '.php';
        $layoutFile = dirname(__DIR__) . '/Views/layouts/' . $layout . '.php';

        if (!is_file($viewFile)) {
            http_response_code(500);
            echo 'View nÃ£o encontrada.';
            return;
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        if (is_file($layoutFile)) {
            require $layoutFile;
            return;
        }

        echo $content;
    }
}
