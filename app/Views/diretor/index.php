<?php
$periodosResumo = [
    ['titulo' => 'Hoje', 'resumo' => $resumoHoje, 'periodicidade' => 'diario'],
    ['titulo' => 'Nesta semana', 'resumo' => $resumoSemana, 'periodicidade' => 'semanal'],
    ['titulo' => 'Neste mês', 'resumo' => $resumoMes, 'periodicidade' => 'mensal'],
];
?>

<section class="section-header director-heading">
    <div>
        <p class="director-heading__eyebrow">Painel executivo</p>
        <h1>Resumo da Direção</h1>
        <p>Visão consolidada do uso de chaves e itens da unidade.</p>
    </div>
    <a class="button" href="<?= e(baseUrl('/diretor/relatorios')) ?>">Abrir relatórios completos</a>
</section>

<section class="director-period-grid" aria-label="Resumo por período">
    <?php foreach ($periodosResumo as $periodo): ?>
        <a class="card director-period-card" href="<?= e(baseUrl('/diretor/relatorios?periodicidade=' . $periodo['periodicidade'] . '&referencia=' . date('Y-m-d'))) ?>">
            <header>
                <span><?= e($periodo['titulo']) ?></span>
                <strong><?= e($periodo['resumo']['total']) ?></strong>
            </header>
            <p>movimentações registradas</p>
            <dl>
                <div><dt>Chaves</dt><dd><?= e($periodo['resumo']['chaves']) ?></dd></div>
                <div><dt>Itens</dt><dd><?= e($periodo['resumo']['itens']) ?></dd></div>
                <div><dt>Pessoas</dt><dd><?= e($periodo['resumo']['usuarios']) ?></dd></div>
            </dl>
        </a>
    <?php endforeach; ?>
</section>

<section class="director-operational-strip" aria-label="Situação operacional atual">
    <article class="card">
        <span>Chaves em aberto</span>
        <strong><?= e($chavesAbertas) ?></strong>
        <small>aguardando devolução</small>
    </article>
    <article class="card">
        <span>Itens em aberto</span>
        <strong><?= e($itensAbertos) ?></strong>
        <small>aguardando devolução</small>
    </article>
    <article class="card">
        <span>Taxa de conclusão no mês</span>
        <strong><?= e($resumoMes['taxa_devolucao']) ?>%</strong>
        <small><?= e($resumoMes['finalizadas']) ?> registro(s) finalizado(s)</small>
    </article>
    <article class="card">
        <span>Usuários no mês</span>
        <strong><?= e($resumoMes['usuarios']) ?></strong>
        <small>pessoas distintas atendidas</small>
    </article>
</section>

<section class="director-shortcuts" aria-label="Acessos rápidos">
    <a class="card card-link" href="<?= e(baseUrl('/diretor/chaves')) ?>">
        <h2>Chaves</h2>
        <p>Consultar e registrar retiradas.</p>
    </a>
    <a class="card card-link" href="<?= e(baseUrl('/diretor/reservas')) ?>">
        <h2>Reservas</h2>
        <p>Analisar a agenda e as solicitações.</p>
    </a>
    <a class="card card-link" href="<?= e(baseUrl('/diretor/disponibilidade')) ?>">
        <h2>Disponibilidade</h2>
        <p>Consultar a situação dos ambientes.</p>
    </a>
    <a class="card card-link" href="<?= e(baseUrl('/calendario-salas')) ?>">
        <h2>Calendário</h2>
        <p>Visualizar reservas, aulas e retiradas.</p>
    </a>
</section>

<section class="section-stack">
    <div class="director-section-title">
        <div>
            <h2>Pendências de devolução</h2>
            <p>Registros que continuam em aberto neste momento.</p>
        </div>
        <span class="status-badge"><?= e(count($abertas)) ?> pendência(s)</span>
    </div>
    <?php $movimentacoes = $abertas; require dirname(__DIR__) . '/portaria/_movimentacoes.php'; ?>
</section>

<section class="section-stack">
    <div class="director-section-title">
        <div>
            <h2>Últimas movimentações</h2>
            <p>Atividade operacional registrada mais recentemente.</p>
        </div>
        <a class="button button--secondary" href="<?= e(baseUrl('/diretor/relatorios')) ?>">Ver histórico</a>
    </div>
    <?php $movimentacoes = $movimentacoesRecentes; require dirname(__DIR__) . '/portaria/_movimentacoes.php'; ?>
</section>
