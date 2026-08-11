<section class="section-header">
    <h1>Retiradas</h1>
    <p>A lista mostra apenas as chaves autorizadas para o seu usuario.</p>
</section>

<section class="resource-section">
    <h2>Chaves autorizadas</h2>
    <?php
    $retiradaAction = baseUrl('/retiradas-autorizadas/chave');
    $observacaoPlaceholder = 'Observacao opcional';
    require __DIR__ . '/../partials/lista-chaves-retirada.php';
    ?>
</section>
