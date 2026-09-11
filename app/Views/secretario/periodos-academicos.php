<section class="section-header"><h1>Períodos Acadêmicos</h1></section>

<form method="post" class="card form-grid">
    <?= csrfField() ?>
    <label>Nome<input name="nome" required></label>
    <label>Início<input type="date" name="data_inicio" required></label>
    <label>Fim<input type="date" name="data_fim" required></label>
    <label>Situação
        <select name="situacao"><option>ativo</option><option>inativo</option><option>encerrado</option></select>
    </label>
    <div class="form-actions"><button class="button">Salvar</button></div>
</form>

<div class="card table-wrap">
    <table><thead><tr><th>Período</th><th>Ações</th></tr></thead><tbody>
    <?php foreach ($periodos as $p): ?>
        <tr><td colspan="2">
            <form method="post" action="<?= e(baseUrl('/secretario/periodos-academicos/atualizar')) ?>" class="inline-form row-edit-form row-edit-form--periodo">
                <?= csrfField() ?>
                <input type="hidden" name="id" value="<?= e($p['id']) ?>">
                <label>Nome<input name="nome" required value="<?= e($p['nome']) ?>"></label>
                <label>Início<input type="date" name="data_inicio" required value="<?= e($p['data_inicio']) ?>"></label>
                <label>Fim<input type="date" name="data_fim" required value="<?= e($p['data_fim']) ?>"></label>
                <label>Situação
                    <select name="situacao">
                        <?php foreach (['ativo', 'inativo', 'encerrado'] as $situacao): ?>
                            <option value="<?= e($situacao) ?>" <?= $p['situacao'] === $situacao ? 'selected' : '' ?>><?= e($situacao) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button class="button">Salvar</button>
            </form>
            <form method="post" action="<?= e(baseUrl('/secretario/periodos-academicos/excluir')) ?>" class="inline-actions">
                <?= csrfField() ?>
                <input type="hidden" name="id" value="<?= e($p['id']) ?>">
                <button class="button button--danger" data-confirm="Excluir este período? Se houver vínculo, ele será inativado.">Excluir</button>
            </form>
        </td></tr>
    <?php endforeach; ?>
    <?php if (!$periodos): ?><tr><td colspan="2">Nenhum período cadastrado.</td></tr><?php endif; ?>
    </tbody></table>
</div>
