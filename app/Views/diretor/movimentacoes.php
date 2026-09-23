<?php
/**
 * GUIA DE MANUTENCAO: Este arquivo renderiza a tela movimentacoes do módulo diretor.
 *
 * Ponto de atencao: A view recebe dados prontos. Consultar banco aqui faria o HTML virar controller clandestino, e ninguém precisa desse segundo emprego.
 */
?>
<section class="section-header">
    <h1>Movimentações da portaria</h1>
    <p>Histórico recente de retirada e devolução de chaves e itens.</p>
</section>

<?php require dirname(__DIR__) . '/portaria/_movimentacoes.php'; ?>
