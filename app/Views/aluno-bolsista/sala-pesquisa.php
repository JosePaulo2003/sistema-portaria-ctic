<section class="section-header">
    <h1>Retiradas autorizadas</h1>
    <p>Use esta tela para retirar chaves autorizadas ao seu usuario.</p>
</section>

<article class="card">
    <h2><?= e($user['nome']) ?></h2>
    <p><strong>Projeto:</strong> <?= e($user['projeto_pesquisa'] ?? 'Nao informado') ?></p>
    <p><strong>Situacao:</strong> <?= e($user['situacao']) ?></p>
</article>

<section class="resource-section">
    <h2>Chaves</h2>
    <?php
    $retiradaAction = baseUrl('/bolsista/retiradas/chave');
    $observacaoPlaceholder = 'Observacao opcional';
    require __DIR__ . '/../partials/lista-chaves-retirada.php';
    ?>
</section>
