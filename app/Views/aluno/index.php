<?php
/**
 * GUIA DE MANUTENCAO: Este arquivo renderiza a tela index do módulo aluno.
 *
 * Ponto de atencao: A view recebe dados prontos. Consultar banco aqui faria o HTML virar controller clandestino, e ninguém precisa desse segundo emprego.
 */
?>
<section class="section-header"><h1>Aluno</h1><p>Consulta pública de ambientes.</p></section>
<div class="dashboard-grid"><a class="card card-link" href="<?= e(baseUrl('/aluno/consulta-salas')) ?>"><h2>Consulta de Salas</h2><p>Ver status público de salas e laboratórios.</p></a></div>
