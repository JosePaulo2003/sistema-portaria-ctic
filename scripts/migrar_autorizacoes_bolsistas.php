<?php
declare(strict_types=1);

use App\Core\Database;

$projectDir = rtrim((string) ($argv[1] ?? dirname(__DIR__)), '/\\');
$backupDir = rtrim((string) ($argv[2] ?? ($projectDir . '/storage/backups/autorizacoes-bolsistas-manual')), '/\\');

if (!is_file($projectDir . '/config/bootstrap.php')) {
    fwrite(STDERR, "Projeto SGRP invalido: {$projectDir}\n");
    exit(1);
}

require $projectDir . '/config/bootstrap.php';

$pdo = Database::pdo();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if (!is_dir($backupDir) && !mkdir($backupDir, 0700, true) && !is_dir($backupDir)) {
    throw new RuntimeException('Nao foi possivel criar a pasta de backup da migracao.');
}

foreach (['usuarios', 'permissoes_salas'] as $tabela) {
    $dados = $pdo->query('SELECT * FROM ' . $tabela . ' ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    $json = json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    if (!is_string($json) || file_put_contents($backupDir . '/' . $tabela . '-antes-da-migracao.json', $json) === false) {
        throw new RuntimeException('Nao foi possivel salvar o backup da tabela ' . $tabela . '.');
    }
}

$colunaExiste = static function (PDO $pdo, string $tabela, string $coluna): bool {
    $stmt = $pdo->prepare(
        'SELECT COUNT(*)
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $stmt->execute([$tabela, $coluna]);
    return (int) $stmt->fetchColumn() > 0;
};

if (!$colunaExiste($pdo, 'usuarios', 'matricula')) {
    $pdo->exec('ALTER TABLE usuarios ADD COLUMN matricula VARCHAR(80) NULL AFTER email');
}
if (!$colunaExiste($pdo, 'permissoes_salas', 'horario_inicio')) {
    $pdo->exec('ALTER TABLE permissoes_salas ADD COLUMN horario_inicio TIME NULL AFTER expira_em');
}
if (!$colunaExiste($pdo, 'permissoes_salas', 'horario_fim')) {
    $pdo->exec('ALTER TABLE permissoes_salas ADD COLUMN horario_fim TIME NULL AFTER horario_inicio');
}

echo "MIGRACAO_AUTORIZACOES_BOLSISTAS_OK\n";
echo "DADOS_EXISTENTES_PRESERVADOS=SIM\n";
echo "BACKUP_TABELAS={$backupDir}\n";
