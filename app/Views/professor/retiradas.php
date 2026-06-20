<section class="section-header">
    <h1>Retiradas</h1>
    <p>As chaves aparecem apenas quando estão disponíveis e autorizadas.</p>
</section>

<?php if (!empty($bloqueio)): ?>
    <div class="flash flash--error">Você está temporariamente bloqueado para retirar chaves até <?= e(date('d/m/Y H:i', strtotime($bloqueio['fim_em']))) ?>.</div>
<?php endif; ?>

<section class="resource-section">
    <h2>Chaves disponíveis</h2>
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
