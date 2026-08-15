<section class="section-header">
    <div>
        <h1>Cursos</h1>
        <p>Cadastro dos cursos que depois serao vinculados aos coordenadores.</p>
    </div>
</section>

<form method="post" action="<?= e(baseUrl('/secretario/cursos')) ?>" class="card form-grid">
    <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>" data-csrf-token>
    <label>Nome do curso
        <input name="nome" required placeholder="Ex.: Licenciatura em Computacao">
    </label>
    <label>Codigo
        <input name="codigo" placeholder="Ex.: LC">
    </label>
    <label>Situacao
        <select name="situacao">
            <option value="ativo">Ativo</option>
            <option value="inativo">Inativo</option>
        </select>
    </label>
    <div class="form-actions">
        <button class="button" type="submit">Criar curso</button>
    </div>
</form>

<div class="card table-wrap">
    <table>
        <thead>
            <tr>
                <th>Curso</th>
                <th>Codigo</th>
                <th>Situacao</th>
                <th>Acoes</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cursos as $curso): ?>
                <tr>
                    <td colspan="4">
                        <form method="post" action="<?= e(baseUrl('/secretario/cursos/atualizar')) ?>" class="inline-form row-edit-form">
                            <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>" data-csrf-token>
                            <input type="hidden" name="id" value="<?= e($curso['id']) ?>">
                            <label>Curso
                                <input name="nome" required value="<?= e($curso['nome']) ?>" aria-label="Nome do curso">
                            </label>
                            <label>Codigo
                                <input name="codigo" value="<?= e($curso['codigo']) ?>" aria-label="Codigo do curso">
                            </label>
                            <label>Situacao
                                <select name="situacao" aria-label="Situacao do curso">
                                    <option value="ativo" <?= $curso['situacao'] === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                                    <option value="inativo" <?= $curso['situacao'] === 'inativo' ? 'selected' : '' ?>>Inativo</option>
                                </select>
                            </label>
                            <button class="button" type="submit">Salvar</button>
                        </form>
                        <form method="post" action="<?= e(baseUrl('/secretario/cursos/excluir')) ?>" class="inline-actions">
                            <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>" data-csrf-token>
                            <input type="hidden" name="id" value="<?= e($curso['id']) ?>">
                            <button class="button button--danger" data-confirm="Excluir este curso? Se houver vinculo, ele sera inativado." type="submit">Excluir</button>
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
