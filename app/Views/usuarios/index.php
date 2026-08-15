<section class="section-header">
    <h1>Usuarios</h1>
    <div class="inline-actions">
        <a class="button button--secondary" href="<?= e(baseUrl('/desenvolvedor/usuarios/solicitacoes')) ?>">Solicitacoes</a>
        <a class="button" href="<?= e(baseUrl('/usuarios/cadastro')) ?>">Novo usuario</a>
    </div>
</section>
<div class="card table-wrap">
    <table>
        <thead>
            <tr><th>Nome</th><th>E-mail</th><th>Perfil</th><th>Curso</th><th>Situacao</th><th>Acoes</th></tr>
        </thead>
        <tbody>
            <?php foreach ($usuarios as $usuario): ?>
                <tr>
                    <td><?= e($usuario['nome']) ?></td>
                    <td><?= e($usuario['email']) ?></td>
                    <td><?= e($usuario['perfil_nome']) ?></td>
                    <td><?= e($usuario['curso_nome'] ?? '-') ?></td>
                    <td><span class="status-badge"><?= e($usuario['situacao']) ?></span></td>
                    <td>
                        <div class="inline-actions">
                            <a class="button button--secondary" href="<?= e(baseUrl('/usuarios/editar?id=' . $usuario['id'])) ?>">Editar</a>
                            <?php if ((int) $usuario['id'] !== (int) currentUser()['id']): ?>
                                <form method="post" action="<?= e(baseUrl('/usuarios/excluir')) ?>">
                                    <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>" data-csrf-token>
                                    <input type="hidden" name="id" value="<?= e($usuario['id']) ?>">
                                    <button class="button button--danger" data-confirm="Apagar este usuario? Essa acao remove o acesso imediatamente." type="submit">Apagar</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$usuarios): ?>
                <tr><td colspan="6">Nenhum usuario cadastrado.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
