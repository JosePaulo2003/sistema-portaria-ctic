<section class="section-header">
    <h1>Retiradas</h1>
    <p>As chaves aparecem apenas quando estão disponíveis e autorizadas.</p>
</section>

<section class="resource-section">
    <h2>Chaves disponíveis</h2>
    <?php
    $retiradaAction = baseUrl('/bolsista/retiradas/chave');
    $observacaoPlaceholder = 'Observação opcional';
    require __DIR__ . '/../partials/lista-chaves-retirada.php';
    ?>
</section>

<section class="resource-section">
    <h2>Itens disponíveis</h2>
    <?php
    $retiradaItemAction = baseUrl('/bolsista/retiradas/item');
    $observacaoItemPlaceholder = 'Observação opcional';
    require __DIR__ . '/../partials/lista-itens-retirada.php';
    ?>
</section>
