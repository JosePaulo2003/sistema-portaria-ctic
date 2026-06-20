<section class="section-header"><h1>Itens da Portaria</h1></section>

<form method="post" class="card form-grid">
    <?= csrfField() ?>
    <label>Nome<input name="nome" required></label>
    <label>Código<input name="codigo"></label>
    <label>Categoria<input name="categoria"></label>
    <label>Quantidade<input type="number" name="quantidade" value="1" min="1"></label>
    <label>Situação<select name="situacao"><option value="disponivel">Disponível</option><option value="indisponivel">Indisponível</option><option value="manutencao">Manutenção</option></select></label>
    <label class="full">Descrição<textarea name="descricao"></textarea></label>
    <div class="form-actions"><button class="button">Cadastrar item</button></div>
</form>

<div class="card table-wrap">
    <table><thead><tr><th>Item</th><th>Ações</th></tr></thead><tbody>
    <?php foreach ($itens as $i): ?>
        <tr><td colspan="2">
            <form method="post" action="<?= e(baseUrl('/secretario/itens/atualizar')) ?>" class="inline-form row-edit-form row-edit-form--item">
                <?= csrfField() ?><input type="hidden" name="id" value="<?= e($i['id']) ?>">
                <label>Nome<input name="nome" required value="<?= e($i['nome']) ?>"></label>
                <label>Código<input name="codigo" value="<?= e($i['codigo']) ?>"></label>
                <label>Categoria<input name="categoria" value="<?= e($i['categoria']) ?>"></label>
                <label>Quantidade<input type="number" name="quantidade" min="0" value="<?= e($i['quantidade']) ?>"></label>
                <label>Situação<select name="situacao"><?php foreach (['disponivel'=>'Disponível','indisponivel'=>'Indisponível','manutencao'=>'Manutenção'] as $valor => $label): ?><option value="<?= e($valor) ?>" <?= $i['situacao'] === $valor ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
                <label>Descrição<input name="descricao" value="<?= e($i['descricao']) ?>"></label>
                <button class="button">Salvar</button>
            </form>
            <form method="post" action="<?= e(baseUrl('/secretario/itens/excluir')) ?>" class="inline-actions">
                <?= csrfField() ?><input type="hidden" name="id" value="<?= e($i['id']) ?>">
                <button class="button button--danger" data-confirm="Excluir item? Se houver vínculo, ele será marcado como indisponível.">Excluir</button>
            </form>
        </td></tr>
    <?php endforeach; ?>
    <?php if (!$itens): ?><tr><td colspan="2">Nenhum item cadastrado.</td></tr><?php endif; ?>
    </tbody></table>
</div>
