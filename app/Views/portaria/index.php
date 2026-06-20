<section class="section-header">
    <h1>Portaria</h1>
    <p>Controle de chaves, itens, visitantes e devoluções.</p>
</section>

<div class="dashboard-grid">
    <article class="card"><h2>Salas monitoradas</h2><strong><?= count($salas) ?></strong></article>
    <article class="card"><h2>Chaves pendentes</h2><strong><?= count(array_filter($abertas, fn ($m) => $m['sala_id'])) ?></strong></article>
    <article class="card"><h2>Itens pendentes</h2><strong><?= count(array_filter($abertas, fn ($m) => $m['item_portaria_id'])) ?></strong></article>
    <a class="card card-link" href="<?= e(baseUrl('/portaria/permissoes')) ?>"><h2>Permissões ativas</h2><p>Consultar permissões de retirada.</p></a>
</div>

<details class="accordion" open>
    <summary>Ambientes disponíveis para chave</summary>
    <?php require dirname(__DIR__) . '/partials/consulta-salas-resumo.php'; ?>
</details>

<details class="accordion">
    <summary>Fila de devoluções</summary>
    <?php $movimentacoes = $abertas; require __DIR__ . '/_movimentacoes.php'; ?>
</details>

<details class="accordion">
    <summary>Movimentações recentes</summary>
    <?php $movimentacoes = $recentes; require __DIR__ . '/_movimentacoes.php'; ?>
</details>
