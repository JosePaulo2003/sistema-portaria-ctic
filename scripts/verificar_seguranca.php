<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
$failures = [];
$postForms = 0;

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root . '/app/Views', FilesystemIterator::SKIP_DOTS)
);
$formPattern = '/<form\b(?=[^>]*\bmethod\s*=\s*["\']post["\'])[^>]*>.*?<\/form\s*>/is';

foreach ($iterator as $file) {
    if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
        continue;
    }
    $content = file_get_contents($file->getPathname()) ?: '';
    preg_match_all($formPattern, $content, $matches, PREG_OFFSET_CAPTURE);
    foreach ($matches[0] as [$form, $offset]) {
        $postForms++;
        $hasExplicitCsrfToken = str_contains($form, 'name="_csrf"')
            && str_contains($form, 'csrfToken()')
            && str_contains($form, 'data-csrf-token');
        if (!$hasExplicitCsrfToken) {
            $line = substr_count(substr($content, 0, $offset), "\n") + 1;
            $failures[] = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1))
                . ':' . $line . ' - formulario POST sem campo CSRF explicito.';
        }
    }
}

$router = file_get_contents($root . '/app/Core/Router.php') ?: '';
$routes = file_get_contents($root . '/routes/web.php') ?: '';
$adminLayout = file_get_contents($root . '/app/Views/layouts/admin.php') ?: '';
$security = file_get_contents($root . '/config/security.php') ?: '';
$session = file_get_contents($root . '/config/session.php') ?: '';

$checks = [
    'Roteador sem validacao CSRF central para POST.' => str_contains($router, "verifyCsrf();"),
    'Roteador sem excecao explicita para webhook.' => str_contains($router, 'postWithoutCsrf'),
    'Webhook nao usa a excecao CSRF autenticada por token.' => str_contains($routes, "postWithoutCsrf('/integracoes/google-form/usuarios'"),
    'Configuracao do guia ainda usa script JSON embutido.' => !str_contains($adminLayout, 'script type="application/json"'),
    'JSON do guia nao usa codificacao segura para HTML.' => str_contains($adminLayout, 'JSON_HEX_TAG') && str_contains($adminLayout, 'JSON_HEX_QUOT'),
    'Politica CSP ausente.' => str_contains($security, 'Content-Security-Policy:'),
    'Protecao de abertura entre origens ausente.' => str_contains($security, 'Cross-Origin-Opener-Policy:'),
    'Cookie seguro nao reconhece HTTPS do proxy.' => str_contains($session, 'HTTP_X_FORWARDED_PROTO'),
];

foreach ($checks as $message => $passed) {
    if (!$passed) {
        $failures[] = $message;
    }
}

if ($postForms === 0) {
    $failures[] = 'Nenhum formulario POST foi localizado; verifique o analisador.';
}

// Confirma o comportamento do roteador, alem de apenas procurar texto nos arquivos.
$GLOBALS['_security_test_csrf_checks'] = 0;
function basePath(): string
{
    return '';
}
function verifyCsrf(): void
{
    $GLOBALS['_security_test_csrf_checks']++;
}

require_once $root . '/app/Core/Router.php';
$routerTest = new App\Core\Router();
$secureRouteHandled = false;
$externalRouteHandled = false;
$routerTest->post('/teste-seguro', static function () use (&$secureRouteHandled): void {
    $secureRouteHandled = true;
});
$routerTest->postWithoutCsrf('/teste-externo', static function () use (&$externalRouteHandled): void {
    $externalRouteHandled = true;
});

ob_start();
$routerTest->dispatch('post', '/teste-seguro');
$routerTest->dispatch('POST', '/teste-externo');
ob_end_clean();

if (!$secureRouteHandled || !$externalRouteHandled || $GLOBALS['_security_test_csrf_checks'] !== 1) {
    $failures[] = 'O comportamento da protecao CSRF central nao corresponde ao esperado.';
}

if ($failures) {
    fwrite(STDERR, "VERIFICACAO_DE_SEGURANCA_FALHOU\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "VERIFICACAO_DE_SEGURANCA_OK\n";
echo "FORMULARIOS_POST_PROTEGIDOS={$postForms}\n";
echo "CSRF_CENTRAL=ativo\n";
echo "CSP=ativa\n";
echo "COOKIE_HTTPS_PROXY=ativo\n";
