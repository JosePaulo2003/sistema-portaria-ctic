<?php
declare(strict_types=1);

// Diagnóstico do banco: fica restrito ao servidor local ou ao desenvolvedor autenticado.
header('Content-Type: text/html; charset=UTF-8');
require __DIR__ . '/config/bootstrap.php';

$remoteIp = $_SERVER['REMOTE_ADDR'] ?? '';
$acessoLocal = in_array($remoteIp, ['127.0.0.1', '::1'], true);
$desenvolvedorAutenticado = false;
try {
    $desenvolvedorAutenticado = isDeveloper();
} catch (Throwable) {
    $desenvolvedorAutenticado = false;
}
if (!$acessoLocal && !$desenvolvedorAutenticado) {
    http_response_code(403);
    exit('Acesso restrito.');
}

$checks = [];
$add = function (string $nome, bool $ok, string $detalhe = '') use (&$checks): void {
    $checks[] = compact('nome', 'ok', 'detalhe');
};
$tableExists = function (PDO $pdo, string $table): bool {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
    $stmt->execute([$table]);
    return (int) $stmt->fetchColumn() > 0;
};

$pdo = null;
try {
    $pdo = App\Core\Database::pdo();
    $add('Conexão com o banco', true, 'PDO conectou ao MySQL/MariaDB.');
} catch (Throwable $e) {
    $add('Conexão com o banco', false, $e->getMessage());
}

if ($pdo instanceof PDO) {
    $required = ['perfis','usuarios','salas','periodos_academicos','cursos','disciplinas','reservas','reservas_aula','itens_portaria','permissoes_salas','movimentacoes','logs_auditoria','advertencias_chaves','bloqueios_chaves','configuracoes_sistema'];
    foreach ($required as $table) {
        $exists = $tableExists($pdo, $table);
        $add("Tabela {$table}", $exists, $exists ? 'Encontrada.' : 'Não encontrada.');
    }
    $add('Usuários cadastrados', (int) $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn() > 0, 'Total: ' . $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn());
    $add('Coluna acesso_total', $tableExists($pdo, 'permissoes_salas') && (bool) $pdo->query("SHOW COLUMNS FROM permissoes_salas LIKE 'acesso_total'")->fetch(), 'Necessária para permissão total de chave.');
}

$okCount = count(array_filter($checks, fn (array $check): bool => $check['ok']));
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verificar Banco - SGRP</title>
    <link rel="stylesheet" href="<?= e(assetUrl('css/app.css')) ?>">
</head>
<body>
<main class="page-shell">
    <section class="section-header">
        <div>
            <h1>Verificação do Banco</h1>
            <p><?= e($okCount) ?> de <?= e(count($checks)) ?> verificações passaram.</p>
        </div>
        <a class="button button--secondary" href="<?= e(baseUrl('/login')) ?>">Voltar ao login</a>
    </section>
    <section class="check-list">
        <?php foreach ($checks as $check): ?>
            <article class="card">
                <h2><?= $check['ok'] ? 'OK' : 'Falha' ?> - <?= e($check['nome']) ?></h2>
                <p><?= e($check['detalhe']) ?></p>
            </article>
        <?php endforeach; ?>
    </section>
</main>
<footer class="site-footer">© CTIC-CESIT. Todos os direitos reservados.</footer>
</body>
</html>
