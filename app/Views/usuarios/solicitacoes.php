<section class="section-header">
    <h1>Solicitações de usuários</h1>
    <div class="solicitacoes-toolbar">
        <button class="button button--secondary" type="button" data-generate-all-passwords>Gerar senhas pendentes</button>
        <form method="post" action="<?= e(baseUrl('/desenvolvedor/usuarios/solicitacoes/limpar-analisadas')) ?>">
            <?= csrfField() ?>
            <button class="button button--secondary" type="submit" data-confirm="Remover todas as solicitações aprovadas e recusadas desta lista? As pendentes serão mantidas.">Limpar analisadas</button>
        </form>
        <a class="button button--secondary" href="<?= e(baseUrl('/desenvolvedor/usuarios')) ?>">Voltar</a>
    </div>
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
                            <div class="solicitacao-actions">
                            <form method="post" action="<?= e(baseUrl('/desenvolvedor/usuarios/solicitacoes/aprovar')) ?>" class="solicitacao-approval-form">
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
                                <label class="solicitacao-password-field">Senha inicial
                                    <span class="password-generator">
                                        <input type="text" name="senha" placeholder="Informe ou gere a senha inicial" required data-generated-password>
                                        <button class="button button--secondary" type="button" data-generate-password>Gerar</button>
                                        <button class="button button--secondary" type="button" data-copy-password>Copiar</button>
                                    </span>
                                </label>
                                <div class="solicitacao-submit-actions">
                                    <button class="button" type="submit">Aprovar e criar</button>
                                </div>
                            </form>
                            <form method="post" action="<?= e(baseUrl('/desenvolvedor/usuarios/solicitacoes/recusar')) ?>" class="solicitacao-reject-form">
                                <?= csrfField() ?>
                                <input type="hidden" name="id" value="<?= e($solicitacao['id']) ?>">
                                <button class="button button--danger" type="submit" data-confirm="Recusar esta solicitação?">Recusar</button>
                            </form>
                            </div>
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
