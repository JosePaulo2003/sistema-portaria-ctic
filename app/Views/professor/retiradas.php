<section class="section-header">
    <h1>Retiradas</h1>
    <p>A lista mostra as chaves autorizadas e a situacao atual de cada uma.</p>
</section>

<?php if (!empty($bloqueio)): ?>
    <?php
    $mensagemBloqueio = 'Você está temporariamente bloqueado para retirar chaves até ' . date('d/m/Y H:i', strtotime($bloqueio['fim_em'])) . '.';
    ?>
    <?= notificationStackHtml(notificationHtml('error', 'Atenção', $mensagemBloqueio, false)) ?>
<?php endif; ?>

<section class="resource-section">
    <h2>Chaves</h2>
    <?php
    $retiradaAction = baseUrl('/professor/retiradas/chave');
    $observacaoPlaceholder = 'Observação opcional';
    require __DIR__ . '/../partials/lista-chaves-retirada.php';
    ?>
</section>

<section class="resource-section">
    <h2>Itens disponíveis</h2>
    <?php
    $retiradaItemAction = baseUrl('/professor/retiradas/item');
    $observacaoItemPlaceholder = 'Observação opcional';
    require __DIR__ . '/../partials/lista-itens-retirada.php';
    ?>
</section>
