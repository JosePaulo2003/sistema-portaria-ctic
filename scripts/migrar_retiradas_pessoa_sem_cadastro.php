<?php
declare(strict_types=1);

/**
 * GUIA DE MANUTENCAO: Este arquivo adiciona suporte ao nome manual nas movimentações.
 *
 * Ponto de atencao: Leia a saída antes de comemorar. Script que termina sem barulho pode ter funcionado ou apenas desistido com elegância.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Acesso restrito.');
}

require dirname(__DIR__) . '/config/bootstrap.php';

$pdo = App\Core\Database::pdo();
$column = $pdo->query("SHOW COLUMNS FROM movimentacoes LIKE 'usuario_nome_manual'")->fetch();
if (!$column) {
    $pdo->exec('ALTER TABLE movimentacoes ADD COLUMN usuario_nome_manual VARCHAR(180) NULL AFTER usuario_id');
}

echo "MIGRACAO_RETIRADAS_PESSOA_SEM_CADASTRO_OK\n";
