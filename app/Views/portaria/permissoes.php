<section class="section-header">
    <h1>Permissoes</h1>
    <p>A Portaria pode liberar retirada de chaves para qualquer usuario ativo do sistema.</p>
</section>

<section class="card form-card permissions-card">
    <h2>Adicionar permissao de chave</h2>
    <form method="post" action="<?= e(baseUrl('/portaria/permissoes/chaves')) ?>" class="permissions-form">
        <?= csrfField() ?>
        <label class="permission-user-field">
            Usuario
            <select name="usuario_id" required>
                <option value="">Selecione</option>
                <?php foreach ($usuarios as $usuario): ?>
                    <?php if (($usuario['situacao'] ?? '') !== 'ativo') continue; ?>
                    <option value="<?= e($usuario['id']) ?>">
                        <?= e($usuario['nome']) ?> - <?= e($usuario['perfil_nome'] ?? '') ?> (<?= e($usuario['email']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="permission-key-field">
            Chave
            <select name="sala_id">
                <option value="">Selecione</option>
                <?php foreach ($salas as $sala): ?>
                    <option value="<?= e($sala['id']) ?>"><?= e($sala['nome']) ?><?= !empty($sala['codigo']) ? ' - ' . e($sala['codigo']) : '' ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="permission-start-field">
            Inicio da autorizacao
            <input type="datetime-local" name="inicio_autorizacao" required>
        </label>
        <label class="permission-end-field">
            Expira em
            <input type="datetime-local" name="expira_em" required>
        </label>
        <label class="checkbox-pill permission-total-field">
            <input type="checkbox" name="acesso_total" value="1">
            <span>Liberar todas as chaves</span>
        </label>
        <fieldset class="checkbox-group permission-days-field">
            <legend>Dias permitidos</legend>
            <label class="checkbox-pill"><input type="checkbox" name="dias_semana[]" value="segunda"><span>Segunda</span></label>
            <label class="checkbox-pill"><input type="checkbox" name="dias_semana[]" value="terca"><span>Terca</span></label>
            <label class="checkbox-pill"><input type="checkbox" name="dias_semana[]" value="quarta"><span>Quarta</span></label>
            <label class="checkbox-pill"><input type="checkbox" name="dias_semana[]" value="quinta"><span>Quinta</span></label>
            <label class="checkbox-pill"><input type="checkbox" name="dias_semana[]" value="sexta"><span>Sexta</span></label>
            <label class="checkbox-pill"><input type="checkbox" name="dias_semana[]" value="sabado"><span>Sabado</span></label>
            <label class="checkbox-pill"><input type="checkbox" name="dias_semana[]" value="domingo"><span>Domingo</span></label>
        </fieldset>
        <label class="permission-note-field">
            Observacao
            <textarea name="observacao" rows="3" placeholder="Motivo ou observacao interna"></textarea>
        </label>
        <div class="form-actions permission-actions">
            <button class="button" type="submit">Salvar permissao</button>
        </div>
    </form>
</section>

<section class="resource-section">
    <h2>Chaves autorizadas</h2>
    <div class="card table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Sala</th>
                    <th>Autorizado por</th>
                    <th>Validade</th>
                    <th>Situacao</th>
                    <th>Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($permissoesSalas as $p): ?>
                    <tr>
                        <td><?= e($p['usuario_nome']) ?></td>
                        <td><?= !empty($p['acesso_total']) ? 'Todas as chaves' : e($p['sala_nome'] ?? '-') ?></td>
                        <td><?= e($p['autorizador_nome']) ?></td>
                        <td>
                            <span class="muted">
                                <?= e($p['inicio_autorizacao'] ?: 'Inicio livre') ?>
                                ate
                                <?= e($p['expira_em'] ?: 'sem expiracao') ?>
                            </span>
                        </td>
                        <td><span class="status-badge status-<?= e($p['situacao']) ?>"><?= e($p['situacao']) ?></span></td>
                        <td>
                            <?php if (($p['situacao'] ?? '') === 'ativa'): ?>
                                <form method="post" action="<?= e(baseUrl('/portaria/permissoes/chaves/revogar')) ?>" class="inline-form">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="id" value="<?= e($p['id']) ?>">
                                    <button class="button permission-revoke-button" type="submit">Revogar</button>
                                </form>
                            <?php else: ?>
                                <span class="muted">Sem acao</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$permissoesSalas): ?>
                    <tr><td colspan="6">Nenhuma permissao de chave cadastrada.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="resource-section">
    <h2>Permissoes de itens</h2>
    <div class="card table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Item/Recurso</th>
                    <th>Autorizado por</th>
                    <th>Situacao</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($permissoesItens as $p): ?>
                    <tr>
                        <td><?= e($p['usuario_nome']) ?></td>
                        <td><?= e($p['item_nome'] ?? $p['recurso_nome'] ?? '-') ?></td>
                        <td><?= e($p['autorizador_nome']) ?></td>
                        <td><span class="status-badge status-<?= e($p['situacao']) ?>"><?= e($p['situacao']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$permissoesItens): ?>
                    <tr><td colspan="4">Nenhuma permissao de item cadastrada.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
