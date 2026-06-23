<section class="section-header">
    <h1>Chaves disponíveis</h1>
    <p>A sala da Diretoria aparece primeiro quando estiver disponível.</p>
</section>

<?php
$retiradaAction = baseUrl('/diretor/chaves/retirar');
$observacaoPlaceholder = 'Observação opcional';
require dirname(__DIR__) . '/partials/lista-chaves-retirada.php';
?>
