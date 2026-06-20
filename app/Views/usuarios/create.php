<section class="section-header"><h1>Cadastrar usuário</h1></section>
<form method="post" class="card form-grid">
<?= csrfField() ?>
<label>Nome<input name="nome" required></label>
<label>E-mail<input type="email" name="email" required></label>
<label>Senha<input type="password" name="senha" minlength="8" required></label>
<label>Perfil<select name="perfil_id"><?php foreach ($perfis as $perfil): ?><option value="<?= e($perfil['id']) ?>"><?= e($perfil['nome']) ?></option><?php endforeach; ?></select></label>
<label>Situação<select name="situacao"><option>ativo</option><option>pendente</option><option>inativo</option><option>bloqueado</option></select></label>
<div class="form-actions"><button class="button" type="submit">Salvar</button></div>
</form>
