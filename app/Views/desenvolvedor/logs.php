<section class="section-header"><h1>Logs</h1><p>Limpeza filtrada exige pelo menos um filtro.</p></section>
<form method="get" class="card form-grid filters">
<label>Módulo<input name="modulo" value="<?= e($_GET['modulo'] ?? '') ?>"></label>
<label>Ação<input name="acao" value="<?= e($_GET['acao'] ?? '') ?>"></label>
<label>Termo<input name="termo" value="<?= e($_GET['termo'] ?? '') ?>"></label>
<label>Data inicial<input type="date" name="data_inicial" value="<?= e($_GET['data_inicial'] ?? '') ?>"></label>
<label>Data final<input type="date" name="data_final" value="<?= e($_GET['data_final'] ?? '') ?>"></label>
<label>Limite<input type="number" name="limite" value="<?= e($_GET['limite'] ?? '100') ?>"></label>
<div class="form-actions"><button class="button" type="submit">Filtrar</button></div>
</form>
<form method="post" action="<?= e(baseUrl('/desenvolvedor/logs/limpar')) ?>" class="inline-actions">
<?= csrfField() ?><input type="hidden" name="modo" value="todos"><button class="button button--danger" data-confirm="Limpar todos os logs?" type="submit">Limpar todos</button>
</form>
<div class="card table-wrap"><table><thead><tr><th>Data</th><th>Usuário</th><th>Módulo</th><th>Ação</th><th>Descrição</th></tr></thead><tbody>
<?php foreach ($logs as $log): ?><tr><td><?= e($log['criado_em']) ?></td><td><?= e($log['usuario_nome'] ?? '-') ?></td><td><?= e($log['modulo']) ?></td><td><?= e($log['acao']) ?></td><td><?= e($log['descricao']) ?></td></tr><?php endforeach; ?>
</tbody></table></div>
