<?php
declare(strict_types=1);

use App\Core\Database;

$projectDir = rtrim((string) ($argv[1] ?? dirname(__DIR__)), '/\\');
if (!is_file($projectDir . '/config/bootstrap.php')) {
    fwrite(STDERR, "Projeto SGRP inválido: {$projectDir}\n");
    exit(1);
}

require $projectDir . '/config/bootstrap.php';
$sql = file_get_contents($projectDir . '/database/patch_20260911_codigo_recuperacao.sql');
if (!is_string($sql) || trim($sql) === '') {
    throw new RuntimeException('Arquivo da migração não encontrado ou vazio.');
}

Database::pdo()->exec($sql);
echo "MIGRACAO_CODIGO_RECUPERACAO_OK\n";
