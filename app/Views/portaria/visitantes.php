<section class="section-header"><h1>Visitantes</h1></section>

<form method="post" class="card form-grid">
    <?= csrfField() ?>
    <label>Nome<input name="nome" required></label>
    <label>E-mail<input type="email" name="email" required></label>
    <label>Senha<input type="password" name="senha" placeholder="Informe a senha inicial" required></label>
    <div class="form-actions"><button class="button" type="submit">Criar visitante</button></div>
</form>

<div class="card table-wrap">
    <table><thead><tr><th>Visitante</th><th>Ações</th></tr></thead><tbody>
    <?php foreach ($visitantes as $v): ?>
        <tr><td colspan="2">
            <form method="post" action="<?= e(baseUrl('/portaria/visitantes/atualizar')) ?>" class="inline-form row-edit-form row-edit-form--visitante">
                <?= csrfField() ?><input type="hidden" name="id" value="<?= e($v['id']) ?>">
                <label>Nome<input name="nome" required value="<?= e($v['nome']) ?>"></label>
                <label>E-mail<input type="email" name="email" required value="<?= e($v['email']) ?>"></label>
                <label>Senha<input name="senha" placeholder="Manter senha atual"></label>
                <label>Situação<select name="situacao"><?php foreach (['ativo','pendente','inativo','bloqueado'] as $situacao): ?><option value="<?= e($situacao) ?>" <?= $v['situacao'] === $situacao ? 'selected' : '' ?>><?= e($situacao) ?></option><?php endforeach; ?></select></label>
                <button class="button">Salvar</button>
            </form>
            <form method="post" action="<?= e(baseUrl('/portaria/visitantes/excluir')) ?>" class="inline-actions">
                <?= csrfField() ?><input type="hidden" name="id" value="<?= e($v['id']) ?>">
                <button class="button button--danger" data-confirm="Apagar visitante? Se houver histórico, ele será removido do acesso e anonimizado.">Apagar</button>
            </form>
        </td></tr>
    <?php endforeach; ?>
    <?php if (!$visitantes): ?><tr><td colspan="2">Nenhum visitante cadastrado.</td></tr><?php endif; ?>
    </tbody></table>
</div>
