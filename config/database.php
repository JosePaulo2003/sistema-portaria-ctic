<?php
declare(strict_types=1);

/**
 * GUIA DE MANUTENCAO: Este arquivo define os parâmetros PDO usados pela conexão.
 *
 * Ponto de atencao: Mudanças de configuração parecem pequenas até derrubarem todas as rotas ao mesmo tempo. Valide em ambiente seguro e sem credenciais no commit.
 */

// Configuracao de conexao com o banco a partir do .env.
return [
    'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
    'port' => $_ENV['DB_PORT'] ?? '3306',
    'database' => $_ENV['DB_DATABASE'] ?? 'sgrp',
    'username' => $_ENV['DB_USERNAME'] ?? 'root',
    'password' => $_ENV['DB_PASSWORD'] ?? '',
    'charset' => 'utf8mb4',
    'timezone' => $_ENV['DB_TIMEZONE'] ?? '-04:00',
];
