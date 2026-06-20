<section class="section-header">
    <h1>Chave Autorizada</h1>
    <p>As chaves aparecem apenas quando estão disponíveis e autorizadas.</p>
</section>

<?php
$retiradaAction = baseUrl('/visitante/chave/retirar');
$observacaoPlaceholder = 'Observação opcional';
require __DIR__ . '/../partials/lista-chaves-retirada.php';
?>
