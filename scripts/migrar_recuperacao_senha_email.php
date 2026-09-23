<?php
declare(strict_types=1);

/**
 * GUIA DE MANUTENCAO: Este arquivo instala as estruturas de recuperação de senha por e-mail.
 *
 * Ponto de atencao: Leia a saída antes de comemorar. Script que termina sem barulho pode ter funcionado ou apenas desistido com elegância.
 */

use App\Core\Database;

$projectDir = rtrim((string) ($argv[1] ?? dirname(__DIR__)), '/\\');
if (!is_file($projectDir . '/config/bootstrap.php')) {
    fwrite(STDERR, "Projeto SGRP inválido: {$projectDir}\n");
    exit(1);
}

require $projectDir . '/config/bootstrap.php';
$sql = file_get_contents($projectDir . '/database/patch_20260911_recuperacao_senha_email.sql');
if (!is_string($sql) || trim($sql) === '') {
    throw new RuntimeException('Arquivo da migração não encontrado ou vazio.');
}

Database::pdo()->exec($sql);
echo "MIGRACAO_RECUPERACAO_SENHA_EMAIL_OK\n";
