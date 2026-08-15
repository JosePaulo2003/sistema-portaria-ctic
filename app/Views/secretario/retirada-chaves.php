<section class="section-header">
    <h1>Retirada de Chaves e Itens</h1>
    <p>A lista mostra os recursos e a situacao atual para retirada.</p>
</section>

<section class="resource-section">
    <h2>Chaves</h2>
    <?php
    $retiradaAction = baseUrl('/secretario/retirada-chaves/retirar');
    $observacaoPlaceholder = 'Observação opcional';
    require __DIR__ . '/../partials/lista-chaves-retirada.php';
    ?>
</section>

<section class="resource-section">
    <h2>Itens disponíveis</h2>
    <?php
    $retiradaItemAction = baseUrl('/secretario/retirada-chaves/retirar-item');
    $observacaoItemPlaceholder = 'Observação opcional';
    require __DIR__ . '/../partials/lista-itens-retirada.php';
    ?>
</section>
