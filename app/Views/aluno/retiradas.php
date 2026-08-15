<section class="section-header">
    <h1>Retiradas</h1>
    <p>A lista mostra apenas as chaves autorizadas para o seu usuario.</p>
</section>

<?php if (!empty($bloqueio)): ?>
    <?php $mensagemBloqueio = 'Voce esta temporariamente bloqueado para retirar chaves ate ' . date('d/m/Y H:i', strtotime($bloqueio['fim_em'])) . '.'; ?>
    <?= notificationStackHtml(notificationHtml('error', 'Atencao', $mensagemBloqueio, false)) ?>
<?php endif; ?>

<section class="resource-section">
    <h2>Chaves autorizadas</h2>
    <?php
    $retiradaAction = baseUrl('/retiradas-autorizadas/chave');
    $observacaoPlaceholder = 'Observacao opcional';
    require __DIR__ . '/../partials/lista-chaves-retirada.php';
    ?>
</section>
