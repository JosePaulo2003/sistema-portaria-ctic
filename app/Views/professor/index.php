<?php
/**
 * GUIA DE MANUTENCAO: Este arquivo renderiza a tela index do módulo professor.
 *
 * Ponto de atencao: A view recebe dados prontos. Consultar banco aqui faria o HTML virar controller clandestino, e ninguém precisa desse segundo emprego.
 */
?>
<section class="section-header"><h1>Professor</h1><p>Reservas, aulas, orientandos e retiradas autorizadas.</p></section>
<div class="dashboard-grid"><a class="card card-link" href="<?= e(baseUrl('/professor/reservas-salas')) ?>"><h2>Reservas</h2><p>Solicitar sala.</p></a><a class="card card-link" href="<?= e(baseUrl('/professor/aulas-semestre')) ?>"><h2>Aulas</h2><p>Consultar aulas do semestre.</p></a><a class="card card-link" href="<?= e(baseUrl('/professor/orientandos-bolsistas')) ?>"><h2>Bolsistas e autorizacoes</h2><p>Cadastrar orientandos, liberar acesso e gerar o documento Word.</p></a><a class="card card-link" href="<?= e(baseUrl('/professor/retiradas')) ?>"><h2>Retiradas</h2><p>Chaves e itens permitidos.</p></a></div>
