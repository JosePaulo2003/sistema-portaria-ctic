<?php $movimentacoesRecentes = $movimentacoes ?? []; ?>

<section class="section-header">
    <h1>Direção</h1>
    <p>Acompanhamento de chaves, reservas e movimentações da portaria.</p>
</section>

<div class="dashboard-grid">
    <a class="card card-link" href="<?= e(baseUrl('/diretor/chaves')) ?>">
        <h2>Chaves disponíveis</h2>
        <p>Retirar chaves disponíveis, com a Diretoria no topo da lista.</p>
    </a>
    <a class="card card-link" href="<?= e(baseUrl('/diretor/reservas')) ?>">
        <h2>Reservas</h2>
        <p><?= e(count($reservas ?? [])) ?> reserva(s) cadastrada(s).</p>
    </a>
    <a class="card card-link" href="<?= e(baseUrl('/diretor/movimentacoes')) ?>">
        <h2>Movimentações</h2>
        <p><?= e($chavesAbertas ?? 0) ?> chave(s) e <?= e($itensAbertos ?? 0) ?> item(ns) em aberto.</p>
    </a>
    <a class="card card-link" href="<?= e(baseUrl('/diretor/disponibilidade')) ?>">
        <h2>Disponibilidade</h2>
        <p>Consultar situação das salas e ambientes.</p>
    </a>
</div>

<section class="section-stack">
    <h2>Chaves e itens em aberto</h2>
    <?php $movimentacoes = $abertas ?? []; require dirname(__DIR__) . '/portaria/_movimentacoes.php'; ?>
</section>

<section class="section-stack">
    <h2>Últimas movimentações</h2>
    <?php $movimentacoes = $movimentacoesRecentes; require dirname(__DIR__) . '/portaria/_movimentacoes.php'; ?>
</section>
