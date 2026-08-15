<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Acesso restrito.');
}

$destination = trim((string) ($argv[1] ?? ''));
if ($destination === '') {
    fwrite(STDERR, "Informe o caminho do arquivo de backup.\n");
    exit(1);
}

require dirname(__DIR__) . '/config/bootstrap.php';

$pdo = App\Core\Database::pdo();
$visitors = $pdo->query(
    "SELECT u.*
     FROM usuarios u
     JOIN perfis p ON p.id = u.perfil_id
     WHERE p.nome = 'Visitante'
     ORDER BY u.id"
)->fetchAll();
$ids = array_map(static fn (array $row): int => (int) $row['id'], $visitors);

$permissions = ['salas' => [], 'itens' => []];
if ($ids) {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM permissoes_salas WHERE usuario_id IN ($placeholders) ORDER BY id");
    $stmt->execute($ids);
    $permissions['salas'] = $stmt->fetchAll();
    $stmt = $pdo->prepare("SELECT * FROM permissoes_itens WHERE usuario_id IN ($placeholders) ORDER BY id");
    $stmt->execute($ids);
    $permissions['itens'] = $stmt->fetchAll();
}

$payload = json_encode([
    'gerado_em' => date(DATE_ATOM),
    'visitantes' => $visitors,
    'permissoes' => $permissions,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

if (file_put_contents($destination, $payload . PHP_EOL, LOCK_EX) === false) {
    fwrite(STDERR, "Nao foi possivel gravar o backup.\n");
    exit(1);
}
chmod($destination, 0600);
echo "BACKUP_VISITANTES_OK={$destination}\n";
