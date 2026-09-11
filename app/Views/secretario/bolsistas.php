<section class="section-header"><h1>Bolsistas</h1></section>

<form method="post" class="card form-grid">
    <?= csrfField() ?>
    <label>Nome<input name="nome" required></label>
    <label>E-mail<input type="email" name="email" required></label>
    <label>Senha<input type="password" name="senha" placeholder="Informe a senha inicial" required></label>
    <label>Professor
        <select name="professor_indicador_id">
            <option value="">Sem professor</option>
            <?php foreach ($professores as $p): ?>
                <option value="<?= e($p['id']) ?>"><?= e($p['nome']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Situação
        <select name="situacao">
            <option>pendente</option>
            <option>ativo</option>
            <option>inativo</option>
        </select>
    </label>
    <label class="full">Projeto<input name="projeto_pesquisa"></label>
    <div class="form-actions"><button class="button">Salvar</button></div>
</form>

<div class="card table-wrap">
    <table>
        <thead>
            <tr>
                <th>Nome</th>
                <th>E-mail</th>
                <th>Projeto</th>
                <th>Situação</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($bolsistas as $b): ?>
                <tr>
                    <td colspan="5">
                        <form method="post" action="<?= e(baseUrl('/secretario/bolsistas/atualizar')) ?>" class="inline-form row-edit-form row-edit-form--bolsista">
                            <?= csrfField() ?>
                            <input type="hidden" name="id" value="<?= e($b['id']) ?>">
                            <label>Nome
                                <input name="nome" required value="<?= e($b['nome']) ?>">
                            </label>
                            <label>E-mail
                                <input type="email" name="email" required value="<?= e($b['email']) ?>">
                            </label>
                            <label>Senha
                                <input name="senha" placeholder="Manter senha atual">
                            </label>
                            <label>Professor
                                <select name="professor_indicador_id">
                                    <option value="">Sem professor</option>
                                    <?php foreach ($professores as $p): ?>
                                        <option value="<?= e($p['id']) ?>" <?= (int) ($b['professor_indicador_id'] ?? 0) === (int) $p['id'] ? 'selected' : '' ?>>
                                            <?= e($p['nome']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label>Situação
                                <select name="situacao">
                                    <?php foreach (['pendente', 'ativo', 'inativo', 'bloqueado'] as $situacao): ?>
                                        <option value="<?= e($situacao) ?>" <?= $b['situacao'] === $situacao ? 'selected' : '' ?>>
                                            <?= e($situacao) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label>Projeto
                                <input name="projeto_pesquisa" value="<?= e($b['projeto_pesquisa'] ?? '') ?>">
                            </label>
                            <button class="button" type="submit">Salvar</button>
                        </form>
                        <form method="post" action="<?= e(baseUrl('/secretario/bolsistas/excluir')) ?>" class="inline-actions">
                            <?= csrfField() ?>
                            <input type="hidden" name="id" value="<?= e($b['id']) ?>">
                            <button class="button button--danger" data-confirm="Apagar este bolsista? Se houver histórico, ele será removido do acesso e anonimizado." type="submit">Apagar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$bolsistas): ?>
                <tr><td colspan="5">Nenhum bolsista cadastrado.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
