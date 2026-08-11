<section class="section-header">
    <h1>Chaves</h1>
    <p>A lista mostra a situacao atual das chaves, com a Diretoria no topo.</p>
</section>

<?php
$retiradaAction = baseUrl('/diretor/chaves/retirar');
$observacaoPlaceholder = 'Observação opcional';
require dirname(__DIR__) . '/partials/lista-chaves-retirada.php';
?>
