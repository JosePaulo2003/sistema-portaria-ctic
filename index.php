<?php
declare(strict_types=1);

// Ponto de entrada da aplicacao; carrega o bootstrap e entrega a requisicao ao roteador.
header('Content-Type: text/html; charset=UTF-8');

require __DIR__ . '/config/bootstrap.php';

$router = require __DIR__ . '/routes/web.php';
$router->dispatch($_SERVER['REQUEST_METHOD'], parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
