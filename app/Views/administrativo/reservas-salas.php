<section class="section-header"><h1>Reservas de Salas</h1></section>
<div class="card table-wrap"><table><thead><tr><th>Título</th><th>Sala</th><th>Usuário</th><th>Início</th><th>Fim</th><th>Situação</th></tr></thead><tbody>
<?php foreach ($reservas as $r): ?><tr><td><?= e($r['titulo']) ?></td><td><?= e($r['sala_nome']) ?></td><td><?= e($r['usuario_nome']) ?></td><td><?= e($r['inicio_em']) ?></td><td><?= e($r['fim_em']) ?></td><td><?= e($r['situacao']) ?></td></tr><?php endforeach; ?>
</tbody></table></div>
