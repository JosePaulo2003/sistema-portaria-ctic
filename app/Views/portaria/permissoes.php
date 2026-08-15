<section class="section-header">
    <h1>Permissoes</h1>
    <p>A Portaria pode liberar retirada de chaves para qualquer usuario ativo do sistema.</p>
</section>

<?php
$inicioAutorizacaoPadrao = new DateTimeImmutable();
$expiracaoAutorizacaoPadrao = $inicioAutorizacaoPadrao->modify('+1 hour');
?>

<?php if (empty($permissaoEdicao)): ?>
<form method="get" action="<?= e(baseUrl('/portaria/permissoes')) ?>" class="card form-grid">
    <label>Filtrar permissões por usuário
        <select name="usuario_id">
            <option value="">Todos os usuários</option>
            <?php foreach ($usuarios as $usuario): ?>
                <option value="<?= e($usuario['id']) ?>" <?= (int) ($usuarioFiltro ?? 0) === (int) $usuario['id'] ? 'selected' : '' ?>>
                    <?= e($usuario['nome']) ?> - <?= e($usuario['perfil_nome'] ?? '') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <div class="form-actions">
        <button class="button" type="submit">Filtrar</button>
        <a class="button button--secondary" href="<?= e(baseUrl('/portaria/permissoes')) ?>">Limpar</a>
    </div>
</form>

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
        <fieldset class="permission-key-field permission-room-picker" data-access-room>
            <legend>Chaves</legend>
            <div class="permission-room-options">
                <?php foreach ($salas as $sala): ?>
                    <label class="checkbox-pill">
                        <input type="checkbox" name="sala_ids[]" value="<?= e($sala['id']) ?>">
                        <span><?= e($sala['nome']) ?><?= !empty($sala['codigo']) ? ' - ' . e($sala['codigo']) : '' ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <span class="muted">Marque todas as salas que deseja autorizar.</span>
        </fieldset>
        <div class="permission-datetime permission-start-field">
            <span class="permission-datetime__title">Início da autorização</span>
            <div class="permission-datetime__controls">
                <label>Data
                    <input type="text" name="inicio_autorizacao_data" value="<?= e($inicioAutorizacaoPadrao->format('d/m/Y')) ?>" data-date-br="date" inputmode="numeric" required>
                </label>
                <label>Hora
                    <select name="inicio_autorizacao_hora" required>
                        <?php for ($hora = 0; $hora <= 23; $hora++): $horaValor = str_pad((string) $hora, 2, '0', STR_PAD_LEFT); ?>
                            <option value="<?= e($horaValor) ?>" <?= $inicioAutorizacaoPadrao->format('H') === $horaValor ? 'selected' : '' ?>><?= e($horaValor) ?></option>
                        <?php endfor; ?>
                    </select>
                </label>
                <label>Min.
                    <select name="inicio_autorizacao_minuto" required>
                        <?php for ($minuto = 0; $minuto <= 59; $minuto++): $minutoValor = str_pad((string) $minuto, 2, '0', STR_PAD_LEFT); ?>
                            <option value="<?= e($minutoValor) ?>" <?= $inicioAutorizacaoPadrao->format('i') === $minutoValor ? 'selected' : '' ?>><?= e($minutoValor) ?></option>
                        <?php endfor; ?>
                    </select>
                </label>
            </div>
        </div>
        <div class="permission-datetime permission-end-field">
            <span class="permission-datetime__title">Expira em</span>
            <div class="permission-datetime__controls" data-expiration-field>
                <label>Data
                    <input type="text" name="expira_em_data" value="<?= e($expiracaoAutorizacaoPadrao->format('d/m/Y')) ?>" data-date-br="date" inputmode="numeric" required>
                </label>
                <label>Hora
                    <select name="expira_em_hora" required>
                        <?php for ($hora = 0; $hora <= 23; $hora++): $horaValor = str_pad((string) $hora, 2, '0', STR_PAD_LEFT); ?>
                            <option value="<?= e($horaValor) ?>" <?= $expiracaoAutorizacaoPadrao->format('H') === $horaValor ? 'selected' : '' ?>><?= e($horaValor) ?></option>
                        <?php endfor; ?>
                    </select>
                </label>
                <label>Min.
                    <select name="expira_em_minuto" required>
                        <?php for ($minuto = 0; $minuto <= 59; $minuto++): $minutoValor = str_pad((string) $minuto, 2, '0', STR_PAD_LEFT); ?>
                            <option value="<?= e($minutoValor) ?>" <?= $expiracaoAutorizacaoPadrao->format('i') === $minutoValor ? 'selected' : '' ?>><?= e($minutoValor) ?></option>
                        <?php endfor; ?>
                    </select>
                </label>
            </div>
        </div>
        <label class="checkbox-pill permission-total-field">
            <input type="checkbox" name="acesso_total" value="1" data-access-total>
            <span>Liberar todas as chaves</span>
        </label>
        <label class="checkbox-pill permission-never-field">
            <input type="checkbox" name="nunca_expirar" value="1" data-never-expire>
            <span>Nunca expirar</span>
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

<?php else: ?>
    <?php
    $edicaoAcessoTotal = !empty($permissaoEdicao['acesso_total']);
    $edicaoNuncaExpirar = empty($permissaoEdicao['expira_em']);
    $edicaoDias = array_map(
        static fn (string $dia): string => comparableProfile(trim($dia)),
        array_filter(explode(',', (string) ($permissaoEdicao['dias_semana'] ?? '')))
    );
    $inicioAutorizacaoEdicao = parseDateTimeInput((string) ($permissaoEdicao['inicio_autorizacao'] ?? '')) ?? new DateTimeImmutable();
    $expiracaoAutorizacaoEdicao = parseDateTimeInput((string) ($permissaoEdicao['expira_em'] ?? '')) ?? $inicioAutorizacaoEdicao->modify('+1 hour');
    ?>
    <section class="card form-card permissions-card" id="editar-permissao">
        <h2>Editar permissão de <?= e($permissaoEdicao['usuario_nome']) ?></h2>
        <form method="post" action="<?= e(baseUrl('/portaria/permissoes/chaves/atualizar')) ?>" class="permissions-form">
            <?= csrfField() ?>
            <input type="hidden" name="id" value="<?= e($permissaoEdicao['id']) ?>">
            <label class="permission-user-field">Usuário
                <input type="text" value="<?= e($permissaoEdicao['usuario_nome']) ?>" readonly>
            </label>
            <label class="permission-key-field">Chave
                <select name="sala_id" data-access-room>
                    <option value="">Selecione</option>
                    <?php foreach ($salas as $sala): ?>
                        <option value="<?= e($sala['id']) ?>" <?= !$edicaoAcessoTotal && (int) $sala['id'] === (int) ($permissaoEdicao['sala_id'] ?? 0) ? 'selected' : '' ?>>
                            <?= e($sala['nome']) ?><?= !empty($sala['codigo']) ? ' - ' . e($sala['codigo']) : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <div class="permission-datetime permission-start-field">
                <span class="permission-datetime__title">Início da autorização</span>
                <div class="permission-datetime__controls">
                    <label>Data
                        <input type="text" name="inicio_autorizacao_data" value="<?= e($inicioAutorizacaoEdicao->format('d/m/Y')) ?>" data-date-br="date" inputmode="numeric" required>
                    </label>
                    <label>Hora
                        <select name="inicio_autorizacao_hora" required>
                            <?php for ($hora = 0; $hora <= 23; $hora++): $horaValor = str_pad((string) $hora, 2, '0', STR_PAD_LEFT); ?>
                                <option value="<?= e($horaValor) ?>" <?= $inicioAutorizacaoEdicao->format('H') === $horaValor ? 'selected' : '' ?>><?= e($horaValor) ?></option>
                            <?php endfor; ?>
                        </select>
                    </label>
                    <label>Min.
                        <select name="inicio_autorizacao_minuto" required>
                            <?php for ($minuto = 0; $minuto <= 59; $minuto++): $minutoValor = str_pad((string) $minuto, 2, '0', STR_PAD_LEFT); ?>
                                <option value="<?= e($minutoValor) ?>" <?= $inicioAutorizacaoEdicao->format('i') === $minutoValor ? 'selected' : '' ?>><?= e($minutoValor) ?></option>
                            <?php endfor; ?>
                        </select>
                    </label>
                </div>
            </div>
            <div class="permission-datetime permission-end-field">
                <span class="permission-datetime__title">Expira em</span>
                <div class="permission-datetime__controls" data-expiration-field>
                    <label>Data
                        <input type="text" name="expira_em_data" value="<?= e($expiracaoAutorizacaoEdicao->format('d/m/Y')) ?>" data-date-br="date" inputmode="numeric" required>
                    </label>
                    <label>Hora
                        <select name="expira_em_hora" required>
                            <?php for ($hora = 0; $hora <= 23; $hora++): $horaValor = str_pad((string) $hora, 2, '0', STR_PAD_LEFT); ?>
                                <option value="<?= e($horaValor) ?>" <?= $expiracaoAutorizacaoEdicao->format('H') === $horaValor ? 'selected' : '' ?>><?= e($horaValor) ?></option>
                            <?php endfor; ?>
                        </select>
                    </label>
                    <label>Min.
                        <select name="expira_em_minuto" required>
                            <?php for ($minuto = 0; $minuto <= 59; $minuto++): $minutoValor = str_pad((string) $minuto, 2, '0', STR_PAD_LEFT); ?>
                                <option value="<?= e($minutoValor) ?>" <?= $expiracaoAutorizacaoEdicao->format('i') === $minutoValor ? 'selected' : '' ?>><?= e($minutoValor) ?></option>
                            <?php endfor; ?>
                        </select>
                    </label>
                </div>
            </div>
            <label>Situação
                <select name="situacao">
                    <?php foreach (['ativa', 'revogada', 'expirada'] as $situacao): ?>
                        <option value="<?= e($situacao) ?>" <?= ($permissaoEdicao['situacao'] ?? '') === $situacao ? 'selected' : '' ?>><?= e($situacao) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="checkbox-pill permission-total-field">
                <input type="checkbox" name="acesso_total" value="1" data-access-total <?= $edicaoAcessoTotal ? 'checked' : '' ?>>
                <span>Liberar todas as chaves</span>
            </label>
            <label class="checkbox-pill permission-never-field">
                <input type="checkbox" name="nunca_expirar" value="1" data-never-expire <?= $edicaoNuncaExpirar ? 'checked' : '' ?>>
                <span>Nunca expirar</span>
            </label>
            <fieldset class="checkbox-group permission-days-field">
                <legend>Dias permitidos</legend>
                <?php foreach (['segunda' => 'Segunda', 'terca' => 'Terça', 'quarta' => 'Quarta', 'quinta' => 'Quinta', 'sexta' => 'Sexta', 'sabado' => 'Sábado', 'domingo' => 'Domingo'] as $valor => $rotulo): ?>
                    <label class="checkbox-pill"><input type="checkbox" name="dias_semana[]" value="<?= e($valor) ?>" <?= in_array($valor, $edicaoDias, true) ? 'checked' : '' ?>><span><?= e($rotulo) ?></span></label>
                <?php endforeach; ?>
            </fieldset>
            <label class="permission-note-field">Observação
                <textarea name="observacao" rows="3"><?= e($permissaoEdicao['observacao'] ?? '') ?></textarea>
            </label>
            <div class="form-actions permission-actions">
                <button class="button" type="submit">Salvar alterações</button>
                <a class="button button--secondary" href="<?= e(baseUrl('/portaria/permissoes?usuario_id=' . (int) $permissaoEdicao['usuario_id'])) ?>">Cancelar</a>
            </div>
        </form>
    </section>
<?php endif; ?>

<?php if (empty($permissaoEdicao)): ?>
<section class="resource-section">
    <div class="resource-section__header">
        <h2>Chaves autorizadas</h2>
        <span class="muted">Permissões revogadas desaparecem desta lista automaticamente.</span>
    </div>
    <div class="card table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Sala</th>
                    <th>Autorizado por</th>
                    <th>Validade</th>
                    <th>Cadastro</th>
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
                                <?= e(formatDateTimeBr($p['inicio_autorizacao'] ?? null, 'Inicio livre')) ?>
                                ate
                                <?= e(formatDateTimeBr($p['expira_em'] ?? null, 'sem expiracao')) ?>
                            </span>
                        </td>
                        <td><?= e(formatDateTimeBr($p['criado_em'] ?? null)) ?></td>
                        <td><span class="status-badge status-<?= e($p['situacao']) ?>"><?= e($p['situacao']) ?></span></td>
                        <td>
                            <a class="button button--secondary" href="<?= e(baseUrl('/portaria/permissoes?usuario_id=' . (int) $p['usuario_id'] . '&editar_id=' . (int) $p['id'] . '#editar-permissao')) ?>">Editar</a>
                            <?php if (($p['situacao'] ?? '') === 'ativa'): ?>
                                <form method="post" action="<?= e(baseUrl('/portaria/permissoes/chaves/revogar')) ?>" class="inline-form">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="id" value="<?= e($p['id']) ?>">
                                    <button class="button permission-revoke-button" type="submit" data-confirm="Revogar e remover esta permissão da lista?">Revogar</button>
                                </form>
                            <?php else: ?>
                                <span class="muted">Sem acao</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$permissoesSalas): ?>
                    <tr><td colspan="7">Nenhuma permissao de chave cadastrada.</td></tr>
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
                    <th>Cadastro</th>
                    <th>Situacao</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($permissoesItens as $p): ?>
                    <tr>
                        <td><?= e($p['usuario_nome']) ?></td>
                        <td><?= e($p['item_nome'] ?? $p['recurso_nome'] ?? '-') ?></td>
                        <td><?= e($p['autorizador_nome']) ?></td>
                        <td><?= e(formatDateTimeBr($p['criado_em'] ?? null)) ?></td>
                        <td><span class="status-badge status-<?= e($p['situacao']) ?>"><?= e($p['situacao']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$permissoesItens): ?>
                    <tr><td colspan="5">Nenhuma permissao de item cadastrada.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php endif; ?>
