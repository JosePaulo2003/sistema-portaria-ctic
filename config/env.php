<?php
declare(strict_types=1);

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
];
