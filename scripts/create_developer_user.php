<?php
declare(strict_types=1);

use App\Core\Database;

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Este script deve ser executado pelo terminal.\n");
    exit(1);
}

require dirname(__DIR__) . '/config/bootstrap.php';

[$script, $name, $email, $password] = array_pad($argv, 4, null);

$name = trim((string) $name);
$email = trim((string) $email);
$password = (string) $password;

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12) {
    fwrite(STDERR, "Uso: php {$script} \"Nome\" \"email@dominio\" \"senha_com_12_ou_mais_caracteres\"\n");
    exit(1);
}

$pdo = Database::pdo();

$profileStmt = $pdo->prepare("SELECT id FROM perfis WHERE nome = 'Desenvolvedor' LIMIT 1");
$profileStmt->execute();
$profileId = $profileStmt->fetchColumn();

if (!$profileId) {
    fwrite(STDERR, "Perfil Desenvolvedor nao encontrado. Importe database/seeds.sql antes de executar.\n");
    exit(1);
}

$existsStmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = ? LIMIT 1');
$existsStmt->execute([$email]);

if ($existsStmt->fetchColumn()) {
    fwrite(STDERR, "Ja existe um usuario com este e-mail.\n");
    exit(1);
}

$insertStmt = $pdo->prepare(
    'INSERT INTO usuarios (nome, email, senha_hash, perfil_id, situacao) VALUES (?, ?, ?, ?, ?)'
);
$insertStmt->execute([
    $name,
    $email,
    password_hash($password, PASSWORD_DEFAULT),
    (int) $profileId,
    'ativo',
]);

fwrite(STDOUT, "Usuario Desenvolvedor criado com sucesso.\n");
