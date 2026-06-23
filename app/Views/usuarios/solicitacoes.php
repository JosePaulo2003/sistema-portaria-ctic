<section class="section-header">
    <h1>Solicitações de usuários</h1>
    <a class="button button--secondary" href="<?= e(baseUrl('/desenvolvedor/usuarios')) ?>">Voltar</a>
</section>

<div class="card table-wrap">
    <table>
        <thead>
            <tr>
                <th>Solicitante</th>
                <th>Contato</th>
                <th>Perfil pedido</th>
                <th>Situação</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($solicitacoes as $solicitacao): ?>
                <tr>
                    <td>
                        <strong><?= e($solicitacao['nome']) ?></strong><br>
                        <small><?= e(date('d/m/Y H:i', strtotime($solicitacao['criado_em']))) ?></small>
                    </td>
                    <td>
                        <?= e($solicitacao['email']) ?><br>
                        <small><?= e($solicitacao['telefone'] ?: 'Sem telefone') ?><?= $solicitacao['matricula'] ? ' · ' . e($solicitacao['matricula']) : '' ?></small>
                    </td>
                    <td><?= e($solicitacao['perfil_solicitado']) ?></td>
                    <td><span class="status-badge"><?= e($solicitacao['situacao']) ?></span></td>
                    <td>
                        <?php if ($solicitacao['situacao'] === 'pendente'): ?>
                            <form method="post" action="<?= e(baseUrl('/desenvolvedor/usuarios/solicitacoes/aprovar')) ?>" class="inline-form row-edit-form">
                                <?= csrfField() ?>
                                <input type="hidden" name="id" value="<?= e($solicitacao['id']) ?>">
                                <label>Nome<input name="nome" value="<?= e($solicitacao['nome']) ?>" required></label>
                                <label>E-mail<input type="email" name="email" value="<?= e($solicitacao['email']) ?>" required></label>
                                <label>Perfil
                                    <select name="perfil_id" required>
                                        <?php foreach ($perfis as $perfil): ?>
                                            <option value="<?= e($perfil['id']) ?>" <?= mb_strtolower($perfil['nome']) === mb_strtolower($solicitacao['perfil_solicitado']) ? 'selected' : '' ?>>
                                                <?= e($perfil['nome']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label>Situação
                                    <select name="situacao">
                                        <option value="ativo">ativo</option>
                                        <option value="pendente">pendente</option>
                                    </select>
                                </label>
                                <label>Senha inicial<input name="senha" value="12345678"></label>
                                <button class="button" type="submit">Aprovar e criar</button>
                            </form>
                            <form method="post" action="<?= e(baseUrl('/desenvolvedor/usuarios/solicitacoes/recusar')) ?>" class="inline-actions">
                                <?= csrfField() ?>
                                <input type="hidden" name="id" value="<?= e($solicitacao['id']) ?>">
                                <button class="button button--danger" type="submit" data-confirm="Recusar esta solicitação?">Recusar</button>
                            </form>
                        <?php else: ?>
                            <span class="muted">Já analisada</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if (!empty($solicitacao['observacao'])): ?>
                    <tr>
                        <td colspan="5"><strong>Observação:</strong> <?= e($solicitacao['observacao']) ?></td>
                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>
            <?php if (!$solicitacoes): ?>
                <tr><td colspan="5">Nenhuma solicitação recebida.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
