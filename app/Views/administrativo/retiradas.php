<section class="section-header">
    <h1>Retirada de Chaves e Itens</h1>
    <p>Somente recursos disponíveis aparecem nesta lista.</p>
</section>

<section class="section-stack">
    <h2>Chaves disponíveis</h2>
    <?php
    $retiradaAction = baseUrl('/administrativo/retiradas/chave');
    $observacaoPlaceholder = 'Observação opcional';
    require __DIR__ . '/../partials/lista-chaves-retirada.php';
    ?>
</section>

<section class="section-stack">
    <h2>Itens disponíveis</h2>
    <?php
    $retiradaItemAction = baseUrl('/administrativo/retiradas/item');
    $observacaoItemPlaceholder = 'Observação opcional';
    require __DIR__ . '/../partials/lista-itens-retirada.php';
    ?>
</section>
