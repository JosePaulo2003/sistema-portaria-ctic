<?php
/**
 * GUIA DE MANUTENCAO: Este arquivo renderiza a tela emprestimos portaria do módulo administrativo.
 *
 * Ponto de atencao: A view recebe dados prontos. Consultar banco aqui faria o HTML virar controller clandestino, e ninguém precisa desse segundo emprego.
 */
?>
<section class="section-header"><h1>Empréstimos da Portaria</h1></section>
<?php require dirname(__DIR__) . '/portaria/_movimentacoes.php'; ?>
