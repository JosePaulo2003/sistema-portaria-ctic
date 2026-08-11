<section class="section-header">
    <h1>Retirada de Chaves e Itens</h1>
    <p>Servicos Gerais tem acesso aos recursos para apoio operacional e acompanha a situacao atual.</p>
</section>

<section class="resource-section">
    <h2>Chaves</h2>
    <?php
    $retiradaAction = baseUrl('/servicos-gerais/retiradas/chave');
    $observacaoPlaceholder = 'Limpeza, manutenção ou apoio operacional';
    require __DIR__ . '/../partials/lista-chaves-retirada.php';
    ?>
</section>

<section class="resource-section">
    <h2>Itens disponíveis</h2>
    <?php
    $retiradaItemAction = baseUrl('/servicos-gerais/retiradas/item');
    $observacaoItemPlaceholder = 'Limpeza, manutenção ou apoio operacional';
    require __DIR__ . '/../partials/lista-itens-retirada.php';
    ?>
</section>
