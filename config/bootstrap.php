<?php
declare(strict_types=1);

// Inicializacao comum do SGRP antes das rotas responderem.
mb_internal_encoding('UTF-8');
date_default_timezone_set('America/Manaus');

$GLOBALS['config'] = require __DIR__ . '/env.php';
require __DIR__ . '/security.php';
require __DIR__ . '/session.php';
require __DIR__ . '/database.php';
require dirname(__DIR__) . '/app/Helpers/functions.php';

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix)));
    $file = dirname(__DIR__) . '/app/' . $relative . '.php';
    if (is_file($file)) {
        require $file;
    }
});
