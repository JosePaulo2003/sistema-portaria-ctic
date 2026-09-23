<?php
/**
 * GUIA DE MANUTENCAO: Este arquivo renderiza a tela chaves do módulo diretor.
 *
 * Ponto de atencao: A view recebe dados prontos. Consultar banco aqui faria o HTML virar controller clandestino, e ninguém precisa desse segundo emprego.
 */
?>
<section class="section-header">
    <h1>Chaves</h1>
    <p>A lista mostra a situacao atual das chaves, com a Diretoria no topo.</p>
</section>

<?php
$retiradaAction = baseUrl('/diretor/chaves/retirar');
$observacaoPlaceholder = 'Observação opcional';
require dirname(__DIR__) . '/partials/lista-chaves-retirada.php';
?>
