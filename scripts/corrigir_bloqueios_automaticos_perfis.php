<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Acesso restrito.');
}

require dirname(__DIR__) . '/config/bootstrap.php';

$pdo = App\Core\Database::pdo();
$bloqueios = $pdo->query(
    "SELECT b.*, u.nome AS usuario_nome, p.nome AS perfil_nome
     FROM bloqueios_chaves b
     JOIN usuarios u ON u.id = b.usuario_id
     JOIN perfis p ON p.id = u.perfil_id
     WHERE b.situacao = 'ativo'
       AND b.advertencia_id IS NOT NULL"
)->fetchAll();

$perfisBloqueaveis = [
    comparableProfile('Aluno'),
    comparableProfile('Aluno Bolsista'),
];
$advertenciaAnterior = $pdo->prepare(
    'SELECT advertencia_id
     FROM bloqueios_chaves
     WHERE usuario_id = ?
       AND advertencia_id IS NOT NULL
       AND id < ?
     ORDER BY id DESC
     LIMIT 1'
);
$contarPrimeiroCiclo = $pdo->prepare(
    'SELECT COUNT(*)
     FROM advertencias_chaves
     WHERE usuario_id = ? AND id <= ?'
);
$contarProximoCiclo = $pdo->prepare(
    'SELECT COUNT(*)
     FROM advertencias_chaves
     WHERE usuario_id = ? AND id > ? AND id <= ?'
);
$paraCancelar = [];

foreach ($bloqueios as $bloqueio) {
    $perfil = comparableProfile((string) ($bloqueio['perfil_nome'] ?? ''));
    if (!in_array($perfil, $perfisBloqueaveis, true)) {
        $bloqueio['motivo_correcao'] = 'perfil_sem_bloqueio_automatico';
        $paraCancelar[] = $bloqueio;
        continue;
    }

    $usuarioId = (int) $bloqueio['usuario_id'];
    $advertenciaId = (int) $bloqueio['advertencia_id'];
    $advertenciaAnterior->execute([$usuarioId, (int) $bloqueio['id']]);
    $anteriorId = $advertenciaAnterior->fetchColumn();
    if ($anteriorId === false) {
        $contarPrimeiroCiclo->execute([$usuarioId, $advertenciaId]);
        $ocorrenciasNoCiclo = (int) $contarPrimeiroCiclo->fetchColumn();
    } else {
        $contarProximoCiclo->execute([$usuarioId, (int) $anteriorId, $advertenciaId]);
        $ocorrenciasNoCiclo = (int) $contarProximoCiclo->fetchColumn();
    }

    if ($ocorrenciasNoCiclo < 3) {
        $bloqueio['motivo_correcao'] = 'menos_de_3_ocorrencias_no_ciclo';
        $bloqueio['ocorrencias_no_ciclo'] = $ocorrenciasNoCiclo;
        $paraCancelar[] = $bloqueio;
    }
}

$backupPath = trim((string) ($argv[1] ?? ''));
if ($backupPath !== '') {
    $json = json_encode([
        'gerado_em' => date(DATE_ATOM),
        'bloqueios_automaticos_cancelados' => $paraCancelar,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    if (file_put_contents($backupPath, $json . PHP_EOL, LOCK_EX) === false) {
        throw new RuntimeException('Nao foi possivel criar o backup dos bloqueios corrigidos.');
    }
    chmod($backupPath, 0600);
}

$cancelados = 0;
$stmt = $pdo->prepare(
    "UPDATE bloqueios_chaves
     SET situacao = 'cancelado', fim_em = LEAST(fim_em, NOW())
     WHERE id = ? AND situacao = 'ativo'"
);

$pdo->beginTransaction();
try {
    foreach ($paraCancelar as $bloqueio) {
        $stmt->execute([(int) $bloqueio['id']]);
        $cancelados += $stmt->rowCount();
    }
    $pdo->commit();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $exception;
}

echo "CORRECAO_BLOQUEIOS_AUTOMATICOS_OK={$cancelados}\n";
