<section class="section-header">
    <h1>Retiradas</h1>
    <p>A lista mostra as chaves autorizadas e a situacao atual de cada uma.</p>
</section>

<section class="resource-section">
    <h2>Chaves</h2>
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
