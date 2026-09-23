<?php
/**
 * GUIA DE MANUTENCAO: Este arquivo renderiza a tela index do módulo servicos gerais.
 *
 * Ponto de atencao: A view recebe dados prontos. Consultar banco aqui faria o HTML virar controller clandestino, e ninguém precisa desse segundo emprego.
 */
?>
<section class="section-header"><h1>Serviços Gerais</h1><p>Retirada de chaves para limpeza e manutenção.</p></section>
<div class="dashboard-grid"><a class="card card-link" href="<?= e(baseUrl('/servicos-gerais/retiradas')) ?>"><h2>Retiradas</h2><p>Registrar movimentação de chave.</p></a></div>
