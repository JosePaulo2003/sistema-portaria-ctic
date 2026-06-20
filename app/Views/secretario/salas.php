<section class="section-header"><h1>Salas</h1></section>

<form method="post" class="card form-grid">
    <?= csrfField() ?>
    <label>Nome<input name="nome" required></label>
    <label>Código<input name="codigo"></label>
    <label>Bloco<input name="bloco"></label>
    <label>Capacidade<input type="number" name="capacidade"></label>
    <label>Tipo<select name="tipo_ambiente"><option value="laboratorio">Laboratório</option><option value="institucional">Institucional</option><option value="administrativo">Administrativo</option><option value="setor">Setor</option></select></label>
    <label>Situação<select name="situacao"><option value="disponivel">Disponível</option><option value="manutencao">Manutenção</option><option value="bloqueada">Bloqueada</option></select></label>
    <label class="full">Descrição<textarea name="descricao"></textarea></label>
    <div class="form-actions"><button class="button">Cadastrar sala</button></div>
</form>

<div class="card table-wrap">
    <table><thead><tr><th>Sala</th><th>Ações</th></tr></thead><tbody>
    <?php foreach ($salas as $s): ?>
        <tr><td colspan="2">
            <form method="post" action="<?= e(baseUrl('/secretario/salas/atualizar')) ?>" class="inline-form row-edit-form row-edit-form--sala">
                <?= csrfField() ?>
                <input type="hidden" name="id" value="<?= e($s['id']) ?>">
                <label>Nome<input name="nome" required value="<?= e($s['nome']) ?>"></label>
                <label>Código<input name="codigo" value="<?= e($s['codigo']) ?>"></label>
                <label>Bloco<input name="bloco" value="<?= e($s['bloco']) ?>"></label>
                <label>Capacidade<input type="number" name="capacidade" value="<?= e($s['capacidade']) ?>"></label>
                <label>Tipo<select name="tipo_ambiente"><?php foreach (['laboratorio'=>'Laboratório','institucional'=>'Institucional','administrativo'=>'Administrativo','setor'=>'Setor'] as $valor => $label): ?><option value="<?= e($valor) ?>" <?= $s['tipo_ambiente'] === $valor ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
                <label>Situação<select name="situacao"><?php foreach (['disponivel'=>'Disponível','manutencao'=>'Manutenção','bloqueada'=>'Bloqueada'] as $valor => $label): ?><option value="<?= e($valor) ?>" <?= $s['situacao'] === $valor ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
                <label>Descrição<input name="descricao" value="<?= e($s['descricao']) ?>"></label>
                <button class="button">Salvar</button>
            </form>
            <form method="post" action="<?= e(baseUrl('/secretario/salas/excluir')) ?>" class="inline-actions">
                <?= csrfField() ?><input type="hidden" name="id" value="<?= e($s['id']) ?>">
                <button class="button button--danger" data-confirm="Excluir sala? Se houver vínculo, ela será bloqueada.">Excluir</button>
            </form>
        </td></tr>
    <?php endforeach; ?>
    <?php if (!$salas): ?><tr><td colspan="2">Nenhuma sala cadastrada.</td></tr><?php endif; ?>
    </tbody></table>
</div>
