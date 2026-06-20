<section class="section-header"><h1>Orientandos Bolsistas</h1></section>

<form method="post" class="card form-grid">
    <?= csrfField() ?>
    <label>Nome<input name="nome" required></label>
    <label>E-mail<input type="email" name="email" required></label>
    <label>Senha<input name="senha" placeholder="12345678"></label>
    <label class="full">Projeto de pesquisa<input name="projeto_pesquisa"></label>
    <div class="form-actions"><button class="button">Cadastrar orientando</button></div>
</form>

<div class="card table-wrap">
    <table><thead><tr><th>Orientando</th><th>Ações</th></tr></thead><tbody>
    <?php foreach ($bolsistas as $b): ?>
        <tr><td colspan="2">
            <form method="post" action="<?= e(baseUrl('/professor/orientandos-bolsistas/atualizar')) ?>" class="inline-form row-edit-form row-edit-form--orientando">
                <?= csrfField() ?><input type="hidden" name="id" value="<?= e($b['id']) ?>">
                <label>Nome<input name="nome" required value="<?= e($b['nome']) ?>"></label>
                <label>E-mail<input type="email" name="email" required value="<?= e($b['email']) ?>"></label>
                <label>Senha<input name="senha" placeholder="Manter senha atual"></label>
                <label>Situação<select name="situacao"><?php foreach (['pendente','ativo','inativo','bloqueado'] as $situacao): ?><option value="<?= e($situacao) ?>" <?= $b['situacao'] === $situacao ? 'selected' : '' ?>><?= e($situacao) ?></option><?php endforeach; ?></select></label>
                <label>Projeto<input name="projeto_pesquisa" value="<?= e($b['projeto_pesquisa'] ?? '') ?>"></label>
                <button class="button">Salvar</button>
            </form>
            <form method="post" action="<?= e(baseUrl('/professor/orientandos-bolsistas/excluir')) ?>" class="inline-actions">
                <?= csrfField() ?><input type="hidden" name="id" value="<?= e($b['id']) ?>">
                <button class="button button--danger" data-confirm="Apagar orientando? Se houver histórico, ele será removido do acesso e anonimizado.">Apagar</button>
            </form>
        </td></tr>
    <?php endforeach; ?>
    <?php if (!$bolsistas): ?><tr><td colspan="2">Nenhum orientando cadastrado.</td></tr><?php endif; ?>
    </tbody></table>
</div>
