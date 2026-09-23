<?php
/**
 * GUIA DE MANUTENCAO: Este arquivo renderiza a tela index do módulo aluno bolsista.
 *
 * Ponto de atencao: A view recebe dados prontos. Consultar banco aqui faria o HTML virar controller clandestino, e ninguém precisa desse segundo emprego.
 */
?>
<section class="section-header"><h1>Aluno Bolsista</h1></section>
<div class="dashboard-grid">
    <a class="card card-link" href="<?= e(baseUrl('/bolsista/retiradas')) ?>">
        <h2>Retiradas autorizadas</h2>
        <p>Chaves e itens liberados para seu usuario.</p>
    </a>
</div>
