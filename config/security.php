<?php
declare(strict_types=1);

/**
 * GUIA DE MANUTENCAO: Este arquivo aplica cabeçalhos HTTP, CSP e regras globais de proteção.
 *
 * Ponto de atencao: Mudanças de configuração parecem pequenas até derrubarem todas as rotas ao mesmo tempo. Valide em ambiente seguro e sem credenciais no commit.
 */

// Cabecalhos e diretivas PHP de seguranca aplicados globalmente.
$debug = (bool) ($GLOBALS['config']['app_debug'] ?? false);

ini_set('display_errors', $debug ? '1' : '0');
ini_set('display_startup_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');
ini_set('expose_php', '0');

// Mesma premissa de session.php: X-Forwarded-Proto precisa vir de proxy
// confiavel. Se a topologia mudar, revise os dois arquivos; configuracao pela
// metade e uma tradicao que este projeto educadamente dispensa.
$forwardedProto = strtolower(trim(explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0] ?? ''));
$requestIsHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $forwardedProto === 'https';

if (!headers_sent()) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
    header('Cross-Origin-Opener-Policy: same-origin');
    header('Cross-Origin-Resource-Policy: same-origin');
    header('X-Permitted-Cross-Domain-Policies: none');
    // Ao adicionar CDN, script inline ou fonte externa, ajuste a CSP de forma
    // minima. Liberar 'unsafe-inline' para tudo e como remover a porta porque a
    // chave emperrou.
    header("Content-Security-Policy: default-src 'self'; base-uri 'self'; frame-ancestors 'none'; form-action 'self'; object-src 'none'; img-src 'self' data: blob:; script-src 'self'; style-src 'self';");

    if ($requestIsHttps) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}
