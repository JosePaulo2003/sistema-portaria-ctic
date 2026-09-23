<?php
/**
 * GUIA DE MANUTENCAO: Este arquivo renderiza a tela retiradas do módulo motorista.
 *
 * Ponto de atencao: A view recebe dados prontos. Consultar banco aqui faria o HTML virar controller clandestino, e ninguém precisa desse segundo emprego.
 */
?>
<section class="section-header">
    <h1>Chaves Autorizadas</h1>
    <p>A lista mostra somente as chaves autorizadas para o seu usuario.</p>
</section>

<?php
$retiradaAction = baseUrl('/motorista/retiradas/chave');
$observacaoPlaceholder = 'Observacao opcional';
require __DIR__ . '/../partials/lista-chaves-retirada.php';
?>
