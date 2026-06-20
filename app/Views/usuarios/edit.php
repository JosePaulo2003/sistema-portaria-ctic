<section class="section-header">
    <h1>Editar usuário</h1>
    <a class="button button--secondary" href="<?= e(baseUrl('/desenvolvedor/usuarios')) ?>">Voltar</a>
</section>

<form method="post" class="card form-grid">
    <?= csrfField() ?>
    <input type="hidden" name="id" value="<?= e($usuario['id']) ?>">

    <label>Nome
        <input name="nome" required value="<?= e($usuario['nome']) ?>">
    </label>

    <label>E-mail
        <input type="email" name="email" required value="<?= e($usuario['email']) ?>">
    </label>

    <label>Nova senha
        <input type="password" name="senha" minlength="8" placeholder="Deixe em branco para manter">
    </label>

    <label>Perfil
        <select name="perfil_id">
            <?php foreach ($perfis as $perfil): ?>
                <option value="<?= e($perfil['id']) ?>" <?= (int) $perfil['id'] === (int) $usuario['perfil_id'] ? 'selected' : '' ?>>
                    <?= e($perfil['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>

    <label>Situação
        <select name="situacao">
            <?php foreach (['ativo', 'pendente', 'inativo', 'bloqueado'] as $situacao): ?>
                <option value="<?= e($situacao) ?>" <?= $usuario['situacao'] === $situacao ? 'selected' : '' ?>>
                    <?= e($situacao) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>

    <label class="full">Projeto de pesquisa
        <input name="projeto_pesquisa" value="<?= e($usuario['projeto_pesquisa'] ?? '') ?>">
    </label>

    <div class="form-actions">
        <button class="button" type="submit">Salvar alterações</button>
    </div>
</form>
