<?php
/**
 * GUIA DE MANUTENCAO: Este arquivo renderiza a tela index do módulo visitante.
 *
 * Ponto de atencao: A view recebe dados prontos. Consultar banco aqui faria o HTML virar controller clandestino, e ninguém precisa desse segundo emprego.
 */
?>
<section class="section-header"><h1>Visitante</h1><p>Acesso temporário a chave específica.</p></section>
<div class="dashboard-grid"><a class="card card-link" href="<?= e(baseUrl('/visitante/chave')) ?>"><h2>Chave</h2><p>Consultar e registrar retirada autorizada.</p></a></div>
