<?php
declare(strict_types=1);

/**
 * GUIA DE MANUTENCAO: Este arquivo carrega o .env e oferece valores normalizados à aplicação.
 *
 * Ponto de atencao: Mudanças de configuração parecem pequenas até derrubarem todas as rotas ao mesmo tempo. Valide em ambiente seguro e sem credenciais no commit.
 */

// Leitor simples do .env usado pelas configuracoes da aplicacao.
$envFile = dirname(__DIR__) . '/.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $_ENV[$key] = trim($value, "\"'");
    }
}

return [
    'app_name' => $_ENV['APP_NAME'] ?? 'SGRP',
    'app_env' => $_ENV['APP_ENV'] ?? 'local',
    'app_debug' => filter_var($_ENV['APP_DEBUG'] ?? true, FILTER_VALIDATE_BOOL),
    'app_base_path' => $_ENV['APP_BASE_PATH'] ?? '',
    'app_url' => rtrim((string) ($_ENV['APP_URL'] ?? ''), '/'),
    'form_webhook_token' => $_ENV['FORM_WEBHOOK_TOKEN'] ?? '',
    'mail_host' => $_ENV['MAIL_HOST'] ?? '',
    'mail_port' => (int) ($_ENV['MAIL_PORT'] ?? 587),
    'mail_encryption' => strtolower((string) ($_ENV['MAIL_ENCRYPTION'] ?? 'tls')),
    'mail_username' => $_ENV['MAIL_USERNAME'] ?? '',
    'mail_password' => $_ENV['MAIL_PASSWORD'] ?? '',
    'mail_from_address' => $_ENV['MAIL_FROM_ADDRESS'] ?? '',
    'mail_from_name' => $_ENV['MAIL_FROM_NAME'] ?? ($_ENV['APP_NAME'] ?? 'SGRP'),
    'mail_timeout' => max(5, min(60, (int) ($_ENV['MAIL_TIMEOUT'] ?? 15))),
];
