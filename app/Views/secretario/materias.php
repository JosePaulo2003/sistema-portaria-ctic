<section class="section-header">
    <h1>Cursos e Disciplinas</h1>
    <p>Cadastre cursos reais e vincule disciplinas a cada curso.</p>
</section>

<section class="section-header">
    <h1>Novo curso</h1>
</section>
<form method="post" action="<?= e(baseUrl('/secretario/materias')) ?>" class="card form-grid">
    <?= csrfField() ?>
    <input type="hidden" name="tipo" value="curso">
    <label>Nome do curso
        <input name="nome" required placeholder="Ex.: Sistemas de Informação">
    </label>
    <label>Código
        <input name="codigo" placeholder="Ex.: SI">
    </label>
    <label>Situação
        <select name="situacao">
            <option value="ativo">Ativo</option>
            <option value="inativo">Inativo</option>
        </select>
    </label>
    <div class="form-actions">
        <button class="button" type="submit">Criar curso</button>
    </div>
</form>

<section class="section-header">
    <h1>Cursos cadastrados</h1>
</section>
<div class="card table-wrap">
    <table>
        <thead>
            <tr>
                <th>Curso</th>
                <th>Código</th>
                <th>Situação</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cursos as $curso): ?>
                <tr>
                    <td colspan="4">
                        <form method="post" action="<?= e(baseUrl('/secretario/materias/curso/atualizar')) ?>" class="inline-form row-edit-form">
                            <?= csrfField() ?>
                            <input type="hidden" name="id" value="<?= e($curso['id']) ?>">
                            <label>Curso
                                <input name="nome" required value="<?= e($curso['nome']) ?>" aria-label="Nome do curso">
                            </label>
                            <label>Código
                                <input name="codigo" value="<?= e($curso['codigo']) ?>" placeholder="Ex.: LC, SI, ADM" aria-label="Código do curso">
                            </label>
                            <label>Situação
                                <select name="situacao" aria-label="Situação do curso">
                                    <option value="ativo" <?= $curso['situacao'] === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                                    <option value="inativo" <?= $curso['situacao'] === 'inativo' ? 'selected' : '' ?>>Inativo</option>
                                </select>
                            </label>
                            <button class="button" type="submit">Salvar</button>
                        </form>
                        <form method="post" action="<?= e(baseUrl('/secretario/materias/curso/excluir')) ?>" class="inline-actions">
                            <?= csrfField() ?>
                            <input type="hidden" name="id" value="<?= e($curso['id']) ?>">
                            <button class="button button--danger" data-confirm="Excluir este curso? Se houver vínculo, ele será inativado." type="submit">Excluir</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$cursos): ?>
                <tr><td colspan="4">Nenhum curso cadastrado.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<section class="section-header">
    <h1>Nova disciplina</h1>
</section>
<form method="post" action="<?= e(baseUrl('/secretario/materias')) ?>" class="card form-grid">
    <?= csrfField() ?>
    <input type="hidden" name="tipo" value="disciplina">
    <label>Disciplina
        <input name="nome" required placeholder="Ex.: Programação Web">
    </label>
    <label>Curso
        <select name="curso_id" required>
            <?php foreach ($cursos as $curso): ?>
                <option value="<?= e($curso['id']) ?>"><?= e($curso['nome']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Período
        <input name="periodo_referencia" required placeholder="Ex.: 2026.1">
    </label>
    <label>Professor
        <select name="professor_id">
            <option value="">Não vinculado</option>
            <?php foreach ($professores as $professor): ?>
                <option value="<?= e($professor['id']) ?>"><?= e($professor['nome']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Situação
        <select name="situacao">
            <option value="ativa">Ativa</option>
            <option value="inativa">Inativa</option>
        </select>
    </label>
    <label class="full">Observação
        <textarea name="observacao"></textarea>
    </label>
    <div class="form-actions">
        <button class="button" type="submit">Criar disciplina</button>
    </div>
</form>

<section class="section-header">
    <h1>Disciplinas cadastradas</h1>
</section>
<div class="card table-wrap">
    <table>
        <thead>
            <tr>
                <th>Disciplina</th>
                <th>Curso</th>
                <th>Período</th>
                <th>Professor</th>
                <th>Situação</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($disciplinas as $disciplina): ?>
                <tr>
                    <td colspan="6">
                        <form method="post" action="<?= e(baseUrl('/secretario/materias/disciplina/atualizar')) ?>" class="inline-form row-edit-form row-edit-form--wide">
                            <?= csrfField() ?>
                            <input type="hidden" name="id" value="<?= e($disciplina['id']) ?>">
                            <input name="nome" required value="<?= e($disciplina['nome']) ?>" aria-label="Nome da disciplina">
                            <select name="curso_id" required aria-label="Curso">
                                <?php foreach ($cursos as $curso): ?>
                                    <option value="<?= e($curso['id']) ?>" <?= (int) $curso['id'] === (int) $disciplina['curso_id'] ? 'selected' : '' ?>>
                                        <?= e($curso['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input name="periodo_referencia" required value="<?= e($disciplina['periodo_referencia']) ?>" aria-label="Período">
                            <select name="professor_id" aria-label="Professor">
                                <option value="">Não vinculado</option>
                                <?php foreach ($professores as $professor): ?>
                                    <option value="<?= e($professor['id']) ?>" <?= (int) ($disciplina['professor_id'] ?? 0) === (int) $professor['id'] ? 'selected' : '' ?>>
                                        <?= e($professor['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <select name="situacao" aria-label="Situação">
                                <option value="ativa" <?= $disciplina['situacao'] === 'ativa' ? 'selected' : '' ?>>Ativa</option>
                                <option value="inativa" <?= $disciplina['situacao'] === 'inativa' ? 'selected' : '' ?>>Inativa</option>
                            </select>
                            <input name="observacao" value="<?= e($disciplina['observacao'] ?? '') ?>" aria-label="Observação" placeholder="Observação">
                            <button class="button" type="submit">Salvar</button>
                        </form>
                        <form method="post" action="<?= e(baseUrl('/secretario/materias/disciplina/excluir')) ?>" class="inline-actions">
                            <?= csrfField() ?>
                            <input type="hidden" name="id" value="<?= e($disciplina['id']) ?>">
                            <button class="button button--danger" data-confirm="Excluir esta disciplina? Se houver vínculo, ela será inativada." type="submit">Excluir</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$disciplinas): ?>
                <tr><td colspan="6">Nenhuma disciplina cadastrada.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
