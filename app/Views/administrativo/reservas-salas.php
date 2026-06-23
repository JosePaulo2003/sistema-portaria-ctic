<section class="section-header"><h1>Reservas de Salas</h1></section>

<form method="post" class="card form-grid">
    <?= csrfField() ?>
    <label>Sala
        <select name="sala_id" required>
            <?php foreach ($salas as $s): ?>
                <option value="<?= e($s['id']) ?>"><?= e($s['nome']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Título<input name="titulo" required></label>
    <label>Início<input type="datetime-local" name="inicio_em" required></label>
    <label>Fim<input type="datetime-local" name="fim_em" required></label>
    <label class="full">Finalidade<textarea name="finalidade"></textarea></label>
    <div class="form-actions"><button class="button">Solicitar reserva</button></div>
</form>

<div class="card table-wrap"><table><thead><tr><th>Título</th><th>Sala</th><th>Usuário</th><th>Início</th><th>Fim</th><th>Situação</th></tr></thead><tbody>
<?php foreach ($reservas as $r): ?><tr><td><?= e($r['titulo']) ?></td><td><?= e($r['sala_nome']) ?></td><td><?= e($r['usuario_nome']) ?></td><td><?= e($r['inicio_em']) ?></td><td><?= e($r['fim_em']) ?></td><td><?= e($r['situacao']) ?></td></tr><?php endforeach; ?>
<?php if (!$reservas): ?><tr><td colspan="6">Nenhuma reserva cadastrada.</td></tr><?php endif; ?>
</tbody></table></div>
