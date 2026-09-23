<?php
declare(strict_types=1);

/**
 * GUIA DE MANUTENCAO: Este arquivo resolve templates e entrega dados às views sem transformar HTML em caça ao tesouro.
 *
 * Ponto de atencao: Alterar o núcleo repercute em todas as telas. O bug aqui não é local; ele só começa local para parecer educado.
 */

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
