<?php $diasSemana = ['Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado', 'Domingo']; ?>

<?php $actionPrefix = $actionPrefix ?? '/secretario/chaves-autorizadas'; ?>

<section class="section-header"><h1>Chaves Autorizadas</h1></section>

<form method="post" action="<?= e(baseUrl($actionPrefix)) ?>" class="card form-grid">
    <?= csrfField() ?>
    <label>Usuário
        <select name="usuario_id">
            <?php foreach ($usuarios as $u): ?>
                <option value="<?= e($u['id']) ?>"><?= e($u['nome']) ?> - <?= e($u['perfil_nome']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Sala
        <select name="sala_id" data-access-room>
            <?php foreach ($salas as $s): ?>
                <option value="<?= e($s['id']) ?>"><?= e($s['nome']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Início
        <input type="text" name="inicio_autorizacao" data-date-br="datetime" placeholder="dd/mm/aaaa hh:mm" inputmode="numeric" maxlength="16" pattern="\d{2}/\d{2}/\d{4}\s\d{2}:\d{2}" title="Use o formato dd/mm/aaaa hh:mm" autocomplete="off">
    </label>
    <label>Expira em
        <input type="text" name="expira_em" data-expiration-field data-date-br="datetime" placeholder="dd/mm/aaaa hh:mm" inputmode="numeric" maxlength="16" pattern="\d{2}/\d{2}/\d{4}\s\d{2}:\d{2}" title="Use o formato dd/mm/aaaa hh:mm" autocomplete="off">
    </label>
    <div class="permission-options full">
        <label class="checkbox-pill">
            <input type="checkbox" name="acesso_total" value="1" data-access-total>
            <span>Acesso total</span>
        </label>
        <label class="checkbox-pill">
            <input type="checkbox" name="nunca_expirar" value="1" data-never-expire>
            <span>Nunca expirar</span>
        </label>
    </div>
    <details class="days-picker full" data-days-picker>
        <summary class="days-picker__summary">
            <span class="days-picker__label">Dias permitidos</span>
            <strong data-days-summary>Todos os dias</strong>
        </summary>
        <div class="checkbox-group days-picker__options">
            <?php foreach ($diasSemana as $dia): ?>
                <label class="checkbox-pill">
                    <input type="checkbox" name="dias_semana[]" value="<?= e($dia) ?>">
                    <span><?= e($dia) ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </details>
    <label class="full">Observação
        <textarea name="observacao"></textarea>
    </label>
    <div class="form-actions"><button class="button">Liberar chave</button></div>
</form>

<div class="card table-wrap">
    <table>
        <thead>
            <tr>
                <th>Usuário</th>
                <th>Sala</th>
                <th>Dias</th>
                <th>Expira</th>
                <th>Situação</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($permissoes as $p): ?>
                <?php
                $diasSelecionados = array_values(array_filter(array_map('trim', explode(',', (string) ($p['dias_semana'] ?? '')))));
                $diasResumo = $diasSelecionados ? implode(', ', $diasSelecionados) : 'Todos os dias';
                $acessoTotal = !empty($p['acesso_total']);
                $nuncaExpirar = empty($p['expira_em']);
                ?>
                <tr>
                    <td colspan="6">
                        <form method="post" action="<?= e(baseUrl($actionPrefix . '/atualizar')) ?>" class="permission-edit-form">
                            <?= csrfField() ?>
                            <input type="hidden" name="id" value="<?= e($p['id']) ?>">
                            <label>Usuário
                                <select name="usuario_id">
                                    <?php foreach ($usuarios as $u): ?>
                                        <option value="<?= e($u['id']) ?>" <?= (int) $u['id'] === (int) $p['usuario_id'] ? 'selected' : '' ?>>
                                            <?= e($u['nome']) ?> - <?= e($u['perfil_nome']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label>Sala
                                <select name="sala_id" data-access-room>
                                    <?php foreach ($salas as $s): ?>
                                        <option value="<?= e($s['id']) ?>" <?= !$acessoTotal && (int) $s['id'] === (int) ($p['sala_id'] ?? 0) ? 'selected' : '' ?>>
                                            <?= e($s['nome']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label>Início
                                <input type="text" name="inicio_autorizacao" data-date-br="datetime" value="<?= e(formatDateTimeBr($p['inicio_autorizacao'] ?? null, '')) ?>" placeholder="dd/mm/aaaa hh:mm" inputmode="numeric" maxlength="16" pattern="\d{2}/\d{2}/\d{4}\s\d{2}:\d{2}" title="Use o formato dd/mm/aaaa hh:mm" autocomplete="off">
                            </label>
                            <label>Expira em
                                <input type="text" name="expira_em" data-expiration-field data-date-br="datetime" value="<?= e(formatDateTimeBr($p['expira_em'] ?? null, '')) ?>" placeholder="dd/mm/aaaa hh:mm" inputmode="numeric" maxlength="16" pattern="\d{2}/\d{2}/\d{4}\s\d{2}:\d{2}" title="Use o formato dd/mm/aaaa hh:mm" autocomplete="off">
                            </label>
                            <label>Cadastrada em
                                <input type="text" value="<?= e(formatDateTimeBr($p['criado_em'] ?? null)) ?>" readonly>
                            </label>
                            <label>Situação
                                <select name="situacao">
                                    <?php foreach (['ativa', 'revogada', 'expirada'] as $situacao): ?>
                                        <option value="<?= e($situacao) ?>" <?= $p['situacao'] === $situacao ? 'selected' : '' ?>>
                                            <?= e($situacao) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <div class="permission-options full">
                                <label class="checkbox-pill">
                                    <input type="checkbox" name="acesso_total" value="1" data-access-total <?= $acessoTotal ? 'checked' : '' ?>>
                                    <span>Acesso total</span>
                                </label>
                                <label class="checkbox-pill">
                                    <input type="checkbox" name="nunca_expirar" value="1" data-never-expire <?= $nuncaExpirar ? 'checked' : '' ?>>
                                    <span>Nunca expirar</span>
                                </label>
                            </div>
                            <details class="days-picker full" data-days-picker>
                                <summary class="days-picker__summary">
                                    <span class="days-picker__label">Dias permitidos</span>
                                    <strong data-days-summary><?= e($diasResumo) ?></strong>
                                </summary>
                                <div class="checkbox-group days-picker__options">
                                    <?php foreach ($diasSemana as $dia): ?>
                                        <label class="checkbox-pill">
                                            <input type="checkbox" name="dias_semana[]" value="<?= e($dia) ?>" <?= in_array($dia, $diasSelecionados, true) ? 'checked' : '' ?>>
                                            <span><?= e($dia) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </details>
                            <label class="full">Observação
                                <textarea name="observacao"><?= e($p['observacao'] ?? '') ?></textarea>
                            </label>
                            <div class="form-actions">
                                <button class="button" type="submit">Salvar</button>
                            </div>
                        </form>
                        <form method="post" action="<?= e(baseUrl($actionPrefix . '/revogar')) ?>" class="inline-actions">
                            <?= csrfField() ?>
                            <input type="hidden" name="id" value="<?= e($p['id']) ?>">
                            <button class="button button--danger" data-confirm="Excluir esta permissão de chave?" type="submit">Excluir</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$permissoes): ?>
                <tr><td colspan="6">Nenhuma permissão cadastrada.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
