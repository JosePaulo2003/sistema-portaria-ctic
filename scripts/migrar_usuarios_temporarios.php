<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Acesso restrito.');
}

require dirname(__DIR__) . '/config/bootstrap.php';

$pdo = App\Core\Database::pdo();
$column = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'acesso_expira_em'")->fetch();
if (!$column) {
    $pdo->exec('ALTER TABLE usuarios ADD COLUMN acesso_expira_em DATETIME NULL AFTER situacao');
}

$index = $pdo->query("SHOW INDEX FROM usuarios WHERE Key_name = 'idx_usuarios_acesso_expira'")->fetch();
if (!$index) {
    $pdo->exec('ALTER TABLE usuarios ADD INDEX idx_usuarios_acesso_expira (acesso_expira_em)');
}

echo "MIGRACAO_USUARIOS_TEMPORARIOS_OK\n";
