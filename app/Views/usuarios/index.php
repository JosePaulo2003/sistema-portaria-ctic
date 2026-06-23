<section class="section-header">
    <h1>Usuários</h1>
    <div class="inline-actions">
        <a class="button button--secondary" href="<?= e(baseUrl('/desenvolvedor/usuarios/solicitacoes')) ?>">Solicitações</a>
        <a class="button" href="<?= e(baseUrl('/usuarios/cadastro')) ?>">Novo usuário</a>
    </div>
</section>
<div class="card table-wrap"><table><thead><tr><th>Nome</th><th>E-mail</th><th>Perfil</th><th>Situação</th><th>Ações</th></tr></thead><tbody>
<?php foreach ($usuarios as $usuario): ?><tr>
<td><?= e($usuario['nome']) ?></td>
<td><?= e($usuario['email']) ?></td>
<td><?= e($usuario['perfil_nome']) ?></td>
<td><span class="status-badge"><?= e($usuario['situacao']) ?></span></td>
<td>
    <div class="inline-actions">
        <a class="button button--secondary" href="<?= e(baseUrl('/usuarios/editar?id=' . $usuario['id'])) ?>">Editar</a>
        <?php if ((int) $usuario['id'] !== (int) currentUser()['id']): ?>
            <form method="post" action="<?= e(baseUrl('/usuarios/excluir')) ?>">
                <?= csrfField() ?>
                <input type="hidden" name="id" value="<?= e($usuario['id']) ?>">
                <button class="button button--danger" data-confirm="Apagar este usuário? Essa ação remove o acesso imediatamente." type="submit">Apagar</button>
            </form>
        <?php endif; ?>
    </div>
</td>
</tr><?php endforeach; ?>
</tbody></table></div>
