<section class="section-header">
    <div>
        <h1>Materias do Curso</h1>
        <?php if ($curso): ?>
            <p><?= e($curso['nome']) ?></p>
        <?php endif; ?>
    </div>
</section>

<?php if (!$curso): ?>
    <article class="card">
        <h2>Curso nao vinculado</h2>
        <p>Este coordenador precisa estar vinculado a um curso antes de cadastrar materias.</p>
    </article>
<?php else: ?>
    <form method="post" action="<?= e(baseUrl('/coordenador/materias')) ?>" class="card form-grid">
        <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>" data-csrf-token>
        <label>Disciplina
            <input name="nome" required placeholder="Ex.: Programacao Web">
        </label>
        <label>Periodo
            <input name="periodo_referencia" required placeholder="Ex.: 2026.1">
        </label>
        <label>Professor
            <select name="professor_id">
                <option value="">Nao vinculado</option>
                <?php foreach ($professores as $professor): ?>
                    <option value="<?= e($professor['id']) ?>"><?= e($professor['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Situacao
            <select name="situacao">
                <option value="ativa">Ativa</option>
                <option value="inativa">Inativa</option>
            </select>
        </label>
        <label class="full">Observacao
            <textarea name="observacao"></textarea>
        </label>
        <div class="form-actions">
            <button class="button" type="submit">Criar materia</button>
        </div>
    </form>

    <div class="card table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Materia</th>
                    <th>Periodo</th>
                    <th>Professor</th>
                    <th>Situacao</th>
                    <th>Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($disciplinas as $disciplina): ?>
                    <tr>
                        <td colspan="5">
                            <form method="post" action="<?= e(baseUrl('/coordenador/materias/disciplina/atualizar')) ?>" class="inline-form row-edit-form row-edit-form--wide">
                                <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>" data-csrf-token>
                                <input type="hidden" name="id" value="<?= e($disciplina['id']) ?>">
                                <input name="nome" required value="<?= e($disciplina['nome']) ?>" aria-label="Nome da materia">
                                <input name="periodo_referencia" required value="<?= e($disciplina['periodo_referencia']) ?>" aria-label="Periodo">
                                <select name="professor_id" aria-label="Professor">
                                    <option value="">Nao vinculado</option>
                                    <?php foreach ($professores as $professor): ?>
                                        <option value="<?= e($professor['id']) ?>" <?= (int) ($disciplina['professor_id'] ?? 0) === (int) $professor['id'] ? 'selected' : '' ?>>
                                            <?= e($professor['nome']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <select name="situacao" aria-label="Situacao">
                                    <option value="ativa" <?= $disciplina['situacao'] === 'ativa' ? 'selected' : '' ?>>Ativa</option>
                                    <option value="inativa" <?= $disciplina['situacao'] === 'inativa' ? 'selected' : '' ?>>Inativa</option>
                                </select>
                                <input name="observacao" value="<?= e($disciplina['observacao'] ?? '') ?>" aria-label="Observacao" placeholder="Observacao">
                                <button class="button" type="submit">Salvar</button>
                            </form>
                            <form method="post" action="<?= e(baseUrl('/coordenador/materias/disciplina/excluir')) ?>" class="inline-actions">
                                <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>" data-csrf-token>
                                <input type="hidden" name="id" value="<?= e($disciplina['id']) ?>">
                                <button class="button button--danger" data-confirm="Excluir esta materia? Se houver vinculo, ela sera inativada." type="submit">Excluir</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$disciplinas): ?>
                    <tr><td colspan="5">Nenhuma materia cadastrada para este curso.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
