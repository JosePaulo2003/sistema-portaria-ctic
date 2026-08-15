<section class="section-header"><h1>Cadastrar usuario</h1></section>
<form method="post" class="card form-grid">
    <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>" data-csrf-token>
    <label>Nome<input name="nome" required></label>
    <label>E-mail<input type="email" name="email" required></label>
    <label>Senha<input type="password" name="senha" minlength="8" required></label>
    <label>Perfil
        <select name="perfil_id">
            <?php foreach ($perfis as $perfil): ?>
                <option value="<?= e($perfil['id']) ?>"><?= e($perfil['nome']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Curso vinculado
        <select name="curso_id">
            <option value="">Sem curso</option>
            <?php foreach (($cursos ?? []) as $curso): ?>
                <option value="<?= e($curso['id']) ?>"><?= e($curso['nome']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Situacao
        <select name="situacao">
            <option>ativo</option>
            <option>pendente</option>
            <option>inativo</option>
            <option>bloqueado</option>
        </select>
    </label>
    <div class="form-actions"><button class="button" type="submit">Salvar</button></div>
</form>
