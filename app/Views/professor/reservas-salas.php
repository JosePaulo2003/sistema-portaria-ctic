<section class="section-header"><h1>Reservas de Salas</h1></section>

<form method="post" class="card form-grid">
    <?= csrfField() ?>
    <label>Sala<select name="sala_id"><?php foreach ($salas as $s): ?><option value="<?= e($s['id']) ?>"><?= e($s['nome']) ?></option><?php endforeach; ?></select></label>
    <label>Título<input name="titulo" required></label>
    <label>Início<input type="datetime-local" name="inicio_em" required></label>
    <label>Fim<input type="datetime-local" name="fim_em" required></label>
    <label class="full">Finalidade<textarea name="finalidade"></textarea></label>
    <div class="form-actions"><button class="button">Solicitar reserva</button></div>
</form>

<div class="card table-wrap">
    <table><thead><tr><th>Reserva</th><th>Ações</th></tr></thead><tbody>
    <?php foreach ($reservas as $r): ?>
        <tr><td colspan="2">
            <form method="post" action="<?= e(baseUrl('/professor/reservas-salas/atualizar')) ?>" class="inline-form row-edit-form row-edit-form--reserva">
                <?= csrfField() ?><input type="hidden" name="id" value="<?= e($r['id']) ?>">
                <label>Sala<select name="sala_id"><?php foreach ($salas as $s): ?><option value="<?= e($s['id']) ?>" <?= (int) $r['sala_id'] === (int) $s['id'] ? 'selected' : '' ?>><?= e($s['nome']) ?></option><?php endforeach; ?></select></label>
                <label>Título<input name="titulo" required value="<?= e($r['titulo']) ?>"></label>
                <label>Início<input type="datetime-local" name="inicio_em" required value="<?= e(date('Y-m-d\TH:i', strtotime($r['inicio_em']))) ?>"></label>
                <label>Fim<input type="datetime-local" name="fim_em" required value="<?= e(date('Y-m-d\TH:i', strtotime($r['fim_em']))) ?>"></label>
                <label>Situação<select name="situacao"><?php foreach (['pendente','confirmada','cancelada','encerrada'] as $situacao): ?><option value="<?= e($situacao) ?>" <?= $r['situacao'] === $situacao ? 'selected' : '' ?>><?= e($situacao) ?></option><?php endforeach; ?></select></label>
                <label>Finalidade<input name="finalidade" value="<?= e($r['finalidade'] ?? '') ?>"></label>
                <button class="button">Salvar</button>
            </form>
            <form method="post" action="<?= e(baseUrl('/professor/reservas-salas/excluir')) ?>" class="inline-actions">
                <?= csrfField() ?><input type="hidden" name="id" value="<?= e($r['id']) ?>">
                <button class="button button--danger" data-confirm="Excluir esta reserva?">Excluir</button>
            </form>
        </td></tr>
    <?php endforeach; ?>
    <?php if (!$reservas): ?><tr><td colspan="2">Nenhuma reserva cadastrada.</td></tr><?php endif; ?>
    </tbody></table>
</div>
