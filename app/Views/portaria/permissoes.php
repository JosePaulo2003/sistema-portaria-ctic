<section class="section-header"><h1>Permissões</h1><p>Somente professores, bolsistas e efetivos do CESIT-UEA podem ter permissão de retirada de itens.</p></section>
<h2>Chaves</h2><div class="card table-wrap"><table><thead><tr><th>Usuário</th><th>Sala</th><th>Autorizado por</th><th>Situação</th></tr></thead><tbody>
<?php foreach ($permissoesSalas as $p): ?><tr><td><?= e($p['usuario_nome']) ?></td><td><?= e($p['sala_nome']) ?></td><td><?= e($p['autorizador_nome']) ?></td><td><?= e($p['situacao']) ?></td></tr><?php endforeach; ?>
</tbody></table></div>
<h2>Itens</h2><div class="card table-wrap"><table><thead><tr><th>Usuário</th><th>Item/Recurso</th><th>Autorizado por</th><th>Situação</th></tr></thead><tbody>
<?php foreach ($permissoesItens as $p): ?><tr><td><?= e($p['usuario_nome']) ?></td><td><?= e($p['item_nome'] ?? $p['recurso_nome']) ?></td><td><?= e($p['autorizador_nome']) ?></td><td><?= e($p['situacao']) ?></td></tr><?php endforeach; ?>
</tbody></table></div>
