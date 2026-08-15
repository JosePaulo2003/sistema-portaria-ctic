<section class="section-header">
    <h1>Editar usuario</h1>
    <a class="button button--secondary" href="<?= e(baseUrl('/desenvolvedor/usuarios')) ?>">Voltar</a>
</section>

<form method="post" class="card form-grid">
    <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>" data-csrf-token>
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

    <label>Curso vinculado
        <select name="curso_id">
            <option value="">Sem curso</option>
            <?php foreach (($cursos ?? []) as $curso): ?>
                <option value="<?= e($curso['id']) ?>" <?= (int) ($usuario['curso_id'] ?? 0) === (int) $curso['id'] ? 'selected' : '' ?>>
                    <?= e($curso['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>

    <label>Situacao
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
        <button class="button" type="submit">Salvar alteracoes</button>
    </div>
</form>
