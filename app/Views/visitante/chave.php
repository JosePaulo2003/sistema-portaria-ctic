<section class="section-header">
    <h1>Chave Autorizada</h1>
    <p>A lista mostra as chaves autorizadas e a situacao atual de cada uma.</p>
</section>

<?php
$retiradaAction = baseUrl('/visitante/chave/retirar');
$observacaoPlaceholder = 'Observação opcional';
require __DIR__ . '/../partials/lista-chaves-retirada.php';
?>
