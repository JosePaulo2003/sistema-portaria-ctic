<?php
/**
 * GUIA DE MANUTENCAO: Este arquivo renderiza a tela retiradas do módulo administrativo.
 *
 * Ponto de atencao: A view recebe dados prontos. Consultar banco aqui faria o HTML virar controller clandestino, e ninguém precisa desse segundo emprego.
 */
?>
<section class="section-header">
    <h1>Retirada de Chaves e Itens</h1>
    <p>A lista mostra os recursos e a situacao atual para retirada.</p>
</section>

<section class="section-stack">
    <h2>Chaves</h2>
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
