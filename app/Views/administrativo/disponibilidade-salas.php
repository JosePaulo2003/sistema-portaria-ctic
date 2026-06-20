<?php require dirname(__DIR__) . '/partials/consulta-salas-resumo.php'; ?>
<section class="section-header"><h1>Cadastro rápido de sala</h1></section>
<form method="post" action="<?= e(baseUrl('/secretario/salas')) ?>" class="card form-grid">
<?= csrfField() ?>
<label>Nome<input name="nome" required></label><label>Código<input name="codigo"></label><label>Bloco<input name="bloco"></label>
<label>Capacidade<input type="number" name="capacidade"></label>
<label>Tipo<select name="tipo_ambiente"><option value="laboratorio">Laboratório</option><option value="institucional">Institucional</option><option value="administrativo">Administrativo</option><option value="setor">Setor</option></select></label>
<label>Situação<select name="situacao"><option value="disponivel">Disponível</option><option value="manutencao">Manutenção</option><option value="bloqueada">Bloqueada</option></select></label>
<label class="full">Descrição<textarea name="descricao"></textarea></label>
<div class="form-actions"><button class="button" type="submit">Cadastrar sala</button></div>
</form>
