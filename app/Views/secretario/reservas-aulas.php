<section class="section-header"><h1>Aulas do Semestre</h1></section>

<form method="post" class="card form-grid">
    <?= csrfField() ?>
    <label>Professor<select name="professor_id"><?php foreach ($professores as $p): ?><option value="<?= e($p['id']) ?>"><?= e($p['nome']) ?></option><?php endforeach; ?></select></label>
    <label>Disciplina cadastrada<select name="disciplina_id"><?php foreach ($disciplinas as $d): ?><option value="<?= e($d['id']) ?>"><?= e($d['nome']) ?></option><?php endforeach; ?></select></label>
    <label>Disciplina<input name="disciplina" required></label>
    <label>Período<input name="periodo_academico" required></label>
    <label>Sala<input name="sala_nome" required></label>
    <label>Turma<input name="turma" required></label>
    <label>Dia<input name="dia_semana" required></label>
    <label>Início<input type="time" name="horario_inicio" required></label>
    <label>Fim<input type="time" name="horario_fim" required></label>
    <label class="full">Observação<textarea name="observacao"></textarea></label>
    <div class="form-actions"><button class="button">Cadastrar aula</button></div>
</form>

<div class="card table-wrap">
    <table><thead><tr><th>Aula</th><th>Ações</th></tr></thead><tbody>
    <?php foreach ($reservas as $r): ?>
        <tr><td colspan="2">
            <form method="post" action="<?= e(baseUrl('/secretario/reservas-aulas/atualizar')) ?>" class="inline-form row-edit-form row-edit-form--aula">
                <?= csrfField() ?><input type="hidden" name="id" value="<?= e($r['id']) ?>">
                <label>Professor<select name="professor_id"><?php foreach ($professores as $p): ?><option value="<?= e($p['id']) ?>" <?= (int) $r['professor_id'] === (int) $p['id'] ? 'selected' : '' ?>><?= e($p['nome']) ?></option><?php endforeach; ?></select></label>
                <label>Disciplina cadastrada<select name="disciplina_id"><?php foreach ($disciplinas as $d): ?><option value="<?= e($d['id']) ?>" <?= (int) $r['disciplina_id'] === (int) $d['id'] ? 'selected' : '' ?>><?= e($d['nome']) ?></option><?php endforeach; ?></select></label>
                <label>Disciplina<input name="disciplina" required value="<?= e($r['disciplina']) ?>"></label>
                <label>Período<input name="periodo_academico" required value="<?= e($r['periodo_academico']) ?>"></label>
                <label>Sala<input name="sala_nome" required value="<?= e($r['sala_nome']) ?>"></label>
                <label>Turma<input name="turma" required value="<?= e($r['turma']) ?>"></label>
                <label>Dia<input name="dia_semana" required value="<?= e($r['dia_semana']) ?>"></label>
                <label>Início<input type="time" name="horario_inicio" required value="<?= e(substr($r['horario_inicio'], 0, 5)) ?>"></label>
                <label>Fim<input type="time" name="horario_fim" required value="<?= e(substr($r['horario_fim'], 0, 5)) ?>"></label>
                <label>Situação<select name="situacao"><?php foreach (['ativa','inativa','cancelada'] as $situacao): ?><option value="<?= e($situacao) ?>" <?= $r['situacao'] === $situacao ? 'selected' : '' ?>><?= e($situacao) ?></option><?php endforeach; ?></select></label>
                <label>Observação<input name="observacao" value="<?= e($r['observacao'] ?? '') ?>"></label>
                <button class="button">Salvar</button>
            </form>
            <form method="post" action="<?= e(baseUrl('/secretario/reservas-aulas/excluir')) ?>" class="inline-actions">
                <?= csrfField() ?><input type="hidden" name="id" value="<?= e($r['id']) ?>">
                <button class="button button--danger" data-confirm="Excluir esta aula do semestre?">Excluir</button>
            </form>
        </td></tr>
    <?php endforeach; ?>
    <?php if (!$reservas): ?><tr><td colspan="2">Nenhuma aula cadastrada.</td></tr><?php endif; ?>
    </tbody></table>
</div>
