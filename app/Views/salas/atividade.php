<?php
$formatarDataHora = static function ($valor): string {
    $texto = trim((string) $valor);
    if ($texto === '') {
        return 'Não informado';
    }
    $timestamp = strtotime($texto);
    return $timestamp ? date('d/m/Y H:i', $timestamp) : $texto;
};
$formatarData = static function ($valor): string {
    $texto = trim((string) $valor);
    if ($texto === '') {
        return 'Não informado';
    }
    $timestamp = strtotime($texto);
    return $timestamp ? date('d/m/Y', $timestamp) : $texto;
};
$formatarHora = static function ($valor): string {
    $texto = trim((string) $valor);
    return $texto !== '' ? substr($texto, 0, 5) : 'Não informado';
};
$valorOuTraco = static fn ($valor): string => trim((string) $valor) !== '' ? (string) $valor : 'Não informado';
$voltarUrl = $salaId > 0
    ? baseUrl('/salas/detalhes?id=' . $salaId . '&mes=' . $mes)
    : baseUrl('/calendario-salas?mes=' . $mes);
$salaNome = $valorOuTraco($atividade['sala_nome'] ?? null);
$situacao = (string) ($atividade['situacao'] ?? '');
$subtitulo = match ($tipoAtividade) {
    'reserva' => (string) ($atividade['titulo'] ?? 'Reserva de sala'),
    'aula' => (string) ($atividade['disciplina_nome'] ?? $atividade['disciplina'] ?? 'Aula recorrente'),
    default => 'Chave de ' . $salaNome,
};
$situacaoRotulo = match ($situacao) {
    'confirmada' => 'Confirmada',
    'pendente' => 'Pendente',
    'encerrada' => 'Encerrada',
    'cancelada' => 'Cancelada',
    'ativa' => 'Ativa',
    'inativa' => 'Inativa',
    'aberta' => 'Em aberto',
    'finalizada' => 'Finalizada',
    default => ucfirst($situacao ?: 'Não informada'),
};
?>

<section class="section-header activity-detail-heading">
    <div>
        <p class="activity-detail-heading__eyebrow"><?= e($tituloAtividade) ?></p>
        <h1><?= e($subtitulo) ?></h1>
        <p>Informações completas da atividade selecionada no calendário.</p>
    </div>
    <div class="activity-detail-heading__actions">
        <span class="status-badge"><?= e($situacaoRotulo) ?></span>
        <a class="button button--secondary" href="<?= e($voltarUrl) ?>">Voltar ao calendário</a>
    </div>
</section>

<div class="activity-detail-summary">
    <article class="card">
        <span>Sala</span>
        <strong><?= e($salaNome) ?></strong>
        <small><?= e($valorOuTraco($atividade['sala_codigo'] ?? null)) ?><?= !empty($atividade['sala_bloco']) ? ' · Bloco ' . e($atividade['sala_bloco']) : '' ?></small>
    </article>

    <?php if ($tipoAtividade === 'reserva'): ?>
        <article class="card">
            <span>Período reservado</span>
            <strong><?= e($formatarDataHora($atividade['inicio_em'] ?? null)) ?></strong>
            <small>até <?= e($formatarDataHora($atividade['fim_em'] ?? null)) ?></small>
        </article>
        <article class="card">
            <span>Solicitante</span>
            <strong><?= e($valorOuTraco($atividade['solicitante_nome'] ?? null)) ?></strong>
            <small><?= !empty($atividade['solicitante_nome_manual']) ? 'Pessoa sem cadastro' : 'Usuário cadastrado' ?></small>
        </article>
    <?php elseif ($tipoAtividade === 'aula'): ?>
        <article class="card">
            <span>Ocorrência selecionada</span>
            <strong><?= e($dataSelecionada !== '' ? $formatarData($dataSelecionada) : $valorOuTraco($atividade['dia_semana'] ?? null)) ?></strong>
            <small><?= e($formatarHora($atividade['horario_inicio'] ?? null)) ?> até <?= e($formatarHora($atividade['horario_fim'] ?? null)) ?></small>
        </article>
        <article class="card">
            <span>Professor</span>
            <strong><?= e($valorOuTraco($atividade['professor_nome'] ?? null)) ?></strong>
            <small><?= e($valorOuTraco($atividade['professor_email'] ?? null)) ?></small>
        </article>
    <?php else: ?>
        <article class="card">
            <span>Retirada</span>
            <strong><?= e($formatarDataHora($atividade['retirada_em'] ?? null)) ?></strong>
            <small><?= !empty($atividade['devolucao_real_em']) ? 'Devolvida em ' . e($formatarDataHora($atividade['devolucao_real_em'])) : 'Ainda não devolvida' ?></small>
        </article>
        <article class="card">
            <span>Quem retirou</span>
            <strong><?= e($valorOuTraco($atividade['usuario_nome'] ?? null)) ?></strong>
            <small><?= e($valorOuTraco($atividade['usuario_email'] ?? null)) ?></small>
        </article>
    <?php endif; ?>
</div>

<section class="card activity-detail-card">
    <header>
        <div>
            <h2>Informações completas</h2>
            <p>Todos os dados disponíveis para este registro.</p>
        </div>
    </header>

    <dl class="activity-detail-list">
        <?php if ($tipoAtividade === 'reserva'): ?>
            <div><dt>Título</dt><dd><?= e($valorOuTraco($atividade['titulo'] ?? null)) ?></dd></div>
            <div><dt>Solicitante</dt><dd><?= e($valorOuTraco($atividade['solicitante_nome'] ?? null)) ?></dd></div>
            <div><dt>Tipo de solicitante</dt><dd><?= !empty($atividade['solicitante_nome_manual']) ? 'Pessoa sem cadastro' : 'Usuário cadastrado' ?></dd></div>
            <?php if (empty($atividade['solicitante_nome_manual'])): ?>
                <div><dt>E-mail do solicitante</dt><dd><?= e($valorOuTraco($atividade['solicitante_email'] ?? null)) ?></dd></div>
            <?php endif; ?>
            <div><dt>Sala</dt><dd><?= e($salaNome) ?></dd></div>
            <div><dt>Código e bloco</dt><dd><?= e($valorOuTraco($atividade['sala_codigo'] ?? null)) ?><?= !empty($atividade['sala_bloco']) ? ' · Bloco ' . e($atividade['sala_bloco']) : '' ?></dd></div>
            <div><dt>Início</dt><dd><?= e($formatarDataHora($atividade['inicio_em'] ?? null)) ?></dd></div>
            <div><dt>Fim</dt><dd><?= e($formatarDataHora($atividade['fim_em'] ?? null)) ?></dd></div>
            <div><dt>Situação</dt><dd><?= e($situacaoRotulo) ?></dd></div>
            <div><dt>Tipo de reserva</dt><dd><?= e($valorOuTraco($atividade['tipo_reserva'] ?? null)) ?></dd></div>
            <div><dt>Período acadêmico</dt><dd><?= e($valorOuTraco($atividade['periodo_academico_nome'] ?? null)) ?></dd></div>
            <div class="is-wide"><dt>Finalidade</dt><dd><?= nl2br(e($valorOuTraco($atividade['finalidade'] ?? null))) ?></dd></div>
            <div><dt>Cadastrada em</dt><dd><?= e($formatarDataHora($atividade['criado_em'] ?? null)) ?></dd></div>
            <div><dt>Última atualização</dt><dd><?= e($formatarDataHora($atividade['atualizado_em'] ?? null)) ?></dd></div>
        <?php elseif ($tipoAtividade === 'aula'): ?>
            <div><dt>Disciplina</dt><dd><?= e($valorOuTraco($atividade['disciplina_nome'] ?? $atividade['disciplina'] ?? null)) ?></dd></div>
            <div><dt>Curso</dt><dd><?= e($valorOuTraco($atividade['curso_nome'] ?? null)) ?></dd></div>
            <div><dt>Professor</dt><dd><?= e($valorOuTraco($atividade['professor_nome'] ?? null)) ?></dd></div>
            <div><dt>E-mail do professor</dt><dd><?= e($valorOuTraco($atividade['professor_email'] ?? null)) ?></dd></div>
            <div><dt>Sala</dt><dd><?= e($salaNome) ?></dd></div>
            <div><dt>Código e bloco</dt><dd><?= e($valorOuTraco($atividade['sala_codigo'] ?? null)) ?><?= !empty($atividade['sala_bloco']) ? ' · Bloco ' . e($atividade['sala_bloco']) : '' ?></dd></div>
            <div><dt>Turma</dt><dd><?= e($valorOuTraco($atividade['turma'] ?? null)) ?></dd></div>
            <div><dt>Período acadêmico</dt><dd><?= e($valorOuTraco($atividade['periodo_academico'] ?? null)) ?></dd></div>
            <div><dt>Dia recorrente</dt><dd><?= e($valorOuTraco($atividade['dia_semana'] ?? null)) ?></dd></div>
            <div><dt>Ocorrência selecionada</dt><dd><?= e($dataSelecionada !== '' ? $formatarData($dataSelecionada) : 'Não informada') ?></dd></div>
            <div><dt>Horário</dt><dd><?= e($formatarHora($atividade['horario_inicio'] ?? null)) ?> até <?= e($formatarHora($atividade['horario_fim'] ?? null)) ?></dd></div>
            <div><dt>Situação</dt><dd><?= e($situacaoRotulo) ?></dd></div>
            <div><dt>Aluno bolsista</dt><dd><?= e($valorOuTraco($atividade['bolsista_nome'] ?? null)) ?></dd></div>
            <div><dt>Cadastrada por</dt><dd><?= e($valorOuTraco($atividade['cadastrado_por_nome'] ?? null)) ?></dd></div>
            <div class="is-wide"><dt>Observação</dt><dd><?= nl2br(e($valorOuTraco($atividade['observacao'] ?? null))) ?></dd></div>
            <div><dt>Cadastrada em</dt><dd><?= e($formatarDataHora($atividade['criado_em'] ?? null)) ?></dd></div>
            <div><dt>Última atualização</dt><dd><?= e($formatarDataHora($atividade['atualizado_em'] ?? null)) ?></dd></div>
        <?php else: ?>
            <?php
            $tipoMovimentacao = match ((string) ($atividade['tipo_movimentacao'] ?? '')) {
                'retirada_chave' => 'Retirada de chave',
                'devolucao_chave' => 'Chave devolvida',
                default => str_replace('_', ' ', (string) ($atividade['tipo_movimentacao'] ?? 'Movimentação')),
            };
            $devolvidoPor = !empty($atividade['devolvido_por_nome'])
                ? (string) $atividade['devolvido_por_nome']
                : (($atividade['situacao'] ?? '') === 'finalizada' ? 'Pessoa não cadastrada ou não informada' : 'Ainda não devolvida');
            ?>
            <div><dt>Tipo</dt><dd><?= e($tipoMovimentacao) ?></dd></div>
            <div><dt>Situação</dt><dd><?= e($situacaoRotulo) ?></dd></div>
            <div><dt>Quem retirou</dt><dd><?= e($valorOuTraco($atividade['usuario_nome'] ?? null)) ?></dd></div>
            <div><dt>E-mail</dt><dd><?= e($valorOuTraco($atividade['usuario_email'] ?? null)) ?></dd></div>
            <div><dt>Sala</dt><dd><?= e($salaNome) ?></dd></div>
            <div><dt>Código e bloco</dt><dd><?= e($valorOuTraco($atividade['sala_codigo'] ?? null)) ?><?= !empty($atividade['sala_bloco']) ? ' · Bloco ' . e($atividade['sala_bloco']) : '' ?></dd></div>
            <div><dt>Retirada</dt><dd><?= e($formatarDataHora($atividade['retirada_em'] ?? null)) ?></dd></div>
            <div><dt>Devolução prevista</dt><dd><?= e($formatarDataHora($atividade['devolucao_prevista_em'] ?? null)) ?></dd></div>
            <div><dt>Devolução real</dt><dd><?= e($formatarDataHora($atividade['devolucao_real_em'] ?? null)) ?></dd></div>
            <div><dt>Devolvido por</dt><dd><?= e($devolvidoPor) ?></dd></div>
            <div><dt>Registrado por</dt><dd><?= e($valorOuTraco($atividade['registrado_por_nome'] ?? null)) ?></dd></div>
            <div class="is-wide"><dt>Observação</dt><dd><?= nl2br(e($valorOuTraco($atividade['observacao'] ?? null))) ?></dd></div>
            <div><dt>Registro criado em</dt><dd><?= e($formatarDataHora($atividade['criado_em'] ?? null)) ?></dd></div>
            <div><dt>Última atualização</dt><dd><?= e($formatarDataHora($atividade['atualizado_em'] ?? null)) ?></dd></div>
        <?php endif; ?>
    </dl>
</section>
