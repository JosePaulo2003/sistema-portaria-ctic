<?php
/**
 * GUIA DE MANUTENCAO: Este arquivo renderiza a tela orientandos bolsistas do módulo professor.
 *
 * Ponto de atencao: A view recebe dados prontos. Consultar banco aqui faria o HTML virar controller clandestino, e ninguém precisa desse segundo emprego.
 */
$diasSemana = [
    'segunda' => 'Segunda',
    'terca' => 'Terca',
    'quarta' => 'Quarta',
    'quinta' => 'Quinta',
    'sexta' => 'Sexta',
    'sabado' => 'Sabado',
    'domingo' => 'Domingo',
];
$inicioAutorizacaoPadrao = new DateTimeImmutable();
$expiracaoAutorizacaoPadrao = $inicioAutorizacaoPadrao->modify('+6 months');
$bolsistasAtivos = array_values(array_filter(
    $bolsistas,
    static fn (array $bolsista): bool => ($bolsista['situacao'] ?? '') === 'ativo'
));
?>

<section class="section-header">
    <div>
        <h1>Orientandos e autorizacoes</h1>
        <p>Cadastre seus bolsistas, defina o acesso e gere o documento institucional em Word.</p>
    </div>
</section>

<section class="card authorization-intro">
    <div>
        <strong>Fluxo seguro e integrado</strong>
        <p>Somente bolsistas vinculados ao seu usuario aparecem na autorizacao. O SGRP preenche numero, professor, e-mail, sala, codigo, periodo, dias, horario, finalidade, nomes e matriculas. Como a permissao e criada ativa, o Word sai identificado como aprovado na data da emissao.</p>
    </div>
</section>

<form method="post" class="card form-grid">
    <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>" data-csrf-token>
    <div class="form-section-title full">
        <h2>Cadastrar novo bolsista</h2>
        <p>Use este formulario apenas quando o bolsista ainda nao estiver vinculado ao seu acesso.</p>
    </div>
    <label>Nome completo
        <input name="nome" required autocomplete="name">
    </label>
    <label>Matricula
        <input name="matricula" maxlength="80" placeholder="Matricula institucional">
    </label>
    <label>E-mail
        <input type="email" name="email" required autocomplete="email">
    </label>
    <label>Senha inicial
        <span class="password-generator">
            <input type="text" name="senha" placeholder="Informe ou gere uma senha" required data-generated-password>
            <button class="button button--secondary" type="button" data-generate-password>Gerar</button>
            <button class="button button--secondary" type="button" data-copy-password>Copiar</button>
        </span>
    </label>
    <label class="full">Projeto de pesquisa
        <input name="projeto_pesquisa">
    </label>
    <div class="form-actions full">
        <button class="button">Cadastrar bolsista</button>
    </div>
</form>

<?php if ($bolsistasAtivos): ?>
    <form method="post" action="<?= e(baseUrl('/professor/orientandos-bolsistas/liberar-chave')) ?>" class="card form-grid authorization-form">
        <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>" data-csrf-token>
        <div class="form-section-title full">
            <h2>Criar autorizacao de acesso</h2>
            <p>Selecione ate 11 bolsistas. A permissao sera salva no SGRP e o Word pode ser baixado ja preenchido.</p>
        </div>

        <fieldset class="full authorization-students">
            <legend>Bolsistas autorizados</legend>
            <div class="permission-room-options">
                <?php foreach ($bolsistasAtivos as $b): ?>
                    <label class="checkbox-pill authorization-student-option">
                        <input type="checkbox" name="usuario_ids[]" value="<?= e($b['id']) ?>">
                        <span>
                            <strong><?= e($b['nome']) ?></strong>
                            <small><?= e($b['matricula'] ?: 'Matricula nao informada') ?></small>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
            <small class="muted">O documento institucional comporta no maximo 11 nomes.</small>
        </fieldset>

        <label>Sala autorizada
            <select name="sala_id" required>
                <option value="">Selecione a sala</option>
                <?php foreach ($salas as $s): ?>
                    <option value="<?= e($s['id']) ?>"><?= e($s['nome']) ?><?= !empty($s['codigo']) ? ' - ' . e($s['codigo']) : '' ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Numero da autorizacao
            <input name="numero_autorizacao" maxlength="40" placeholder="Opcional; o SGRP gera um numero se ficar vazio">
        </label>
        <label>Inicio da autorizacao
            <input type="text" name="inicio_autorizacao_data" value="<?= e($inicioAutorizacaoPadrao->format('d/m/Y')) ?>" data-date-br="date" inputmode="numeric" required autocomplete="off">
        </label>
        <div data-expiration-field>
            <label>Expira em
                <input type="text" name="expira_em_data" value="<?= e($expiracaoAutorizacaoPadrao->format('d/m/Y')) ?>" data-date-br="date" inputmode="numeric" required autocomplete="off">
            </label>
        </div>
        <label class="checkbox-pill authorization-never-expire">
            <input type="checkbox" name="nunca_expirar" value="1" data-never-expire>
            <span>Nunca expirar</span>
        </label>

        <fieldset class="authorization-time">
            <legend>Horario inicial</legend>
            <div class="permission-datetime__controls">
                <label>Hora
                    <select name="horario_inicio_hora" required>
                        <?php for ($hora = 0; $hora <= 23; $hora++): $valor = str_pad((string) $hora, 2, '0', STR_PAD_LEFT); ?>
                            <option value="<?= e($valor) ?>" <?= $valor === '08' ? 'selected' : '' ?>><?= e($valor) ?></option>
                        <?php endfor; ?>
                    </select>
                </label>
                <label>Min.
                    <select name="horario_inicio_minuto" required>
                        <?php for ($minuto = 0; $minuto <= 55; $minuto += 5): $valor = str_pad((string) $minuto, 2, '0', STR_PAD_LEFT); ?>
                            <option value="<?= e($valor) ?>"><?= e($valor) ?></option>
                        <?php endfor; ?>
                    </select>
                </label>
            </div>
        </fieldset>

        <fieldset class="authorization-time">
            <legend>Horario final</legend>
            <div class="permission-datetime__controls">
                <label>Hora
                    <select name="horario_fim_hora" required>
                        <?php for ($hora = 0; $hora <= 23; $hora++): $valor = str_pad((string) $hora, 2, '0', STR_PAD_LEFT); ?>
                            <option value="<?= e($valor) ?>" <?= $valor === '18' ? 'selected' : '' ?>><?= e($valor) ?></option>
                        <?php endfor; ?>
                    </select>
                </label>
                <label>Min.
                    <select name="horario_fim_minuto" required>
                        <?php for ($minuto = 0; $minuto <= 55; $minuto += 5): $valor = str_pad((string) $minuto, 2, '0', STR_PAD_LEFT); ?>
                            <option value="<?= e($valor) ?>"><?= e($valor) ?></option>
                        <?php endfor; ?>
                    </select>
                </label>
            </div>
        </fieldset>

        <fieldset class="checkbox-group full">
            <legend>Dias permitidos</legend>
            <?php foreach ($diasSemana as $valor => $rotulo): ?>
                <label class="checkbox-pill">
                    <input type="checkbox" name="dias_semana[]" value="<?= e($valor) ?>">
                    <span><?= e($rotulo) ?></span>
                </label>
            <?php endforeach; ?>
            <small class="muted">Se nenhum dia for marcado, o acesso sera permitido em todos os dias dentro da validade.</small>
        </fieldset>

        <label class="full">Finalidade do acesso
            <textarea name="finalidade" maxlength="800" rows="4" required placeholder="Descreva a atividade, projeto ou motivo do acesso"></textarea>
        </label>

        <div class="form-actions full authorization-actions">
            <button class="button button--secondary" type="submit" name="acao" value="salvar">Salvar autorizacoes</button>
            <button class="button" type="submit" name="acao" value="salvar_baixar">Salvar e baixar Word editavel</button>
        </div>
    </form>
<?php elseif ($bolsistas): ?>
    <section class="card empty-state">
        <strong>Nenhum bolsista ativo para autorizar.</strong>
        <p>Ative um orientando na lista abaixo antes de criar uma nova autorizacao.</p>
    </section>
<?php endif; ?>

<section class="section-header section-header--compact">
    <div>
        <h2>Meus bolsistas</h2>
        <p>Atualize os dados que serao usados nas autorizacoes.</p>
    </div>
</section>

<div class="card table-wrap">
    <table>
        <thead>
            <tr>
                <th>Bolsista</th>
                <th>Matricula</th>
                <th>Projeto</th>
                <th>Situacao</th>
                <th>Acoes</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($bolsistas as $b): ?>
                <tr>
                    <td colspan="5">
                        <form method="post" action="<?= e(baseUrl('/professor/orientandos-bolsistas/atualizar')) ?>" class="inline-form row-edit-form row-edit-form--orientando">
                            <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>" data-csrf-token>
                            <input type="hidden" name="id" value="<?= e($b['id']) ?>">
                            <label>Nome
                                <input name="nome" required value="<?= e($b['nome']) ?>">
                            </label>
                            <label>Matricula
                                <input name="matricula" maxlength="80" value="<?= e($b['matricula'] ?? '') ?>">
                            </label>
                            <label>E-mail
                                <input type="email" name="email" required value="<?= e($b['email']) ?>">
                            </label>
                            <label>Senha
                                <input name="senha" placeholder="Manter senha atual">
                            </label>
                            <label>Situacao
                                <select name="situacao">
                                    <?php foreach (['ativo', 'inativo', 'bloqueado'] as $situacao): ?>
                                        <option value="<?= e($situacao) ?>" <?= $b['situacao'] === $situacao ? 'selected' : '' ?>><?= e($situacao) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label>Projeto
                                <input name="projeto_pesquisa" value="<?= e($b['projeto_pesquisa'] ?? '') ?>">
                            </label>
                            <button class="button">Salvar</button>
                        </form>
                        <form method="post" action="<?= e(baseUrl('/professor/orientandos-bolsistas/excluir')) ?>" class="inline-actions">
                            <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>" data-csrf-token>
                            <input type="hidden" name="id" value="<?= e($b['id']) ?>">
                            <button class="button button--danger" data-confirm="Apagar orientando? Se houver historico, ele sera anonimizado.">Apagar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$bolsistas): ?>
                <tr><td colspan="5">Nenhum bolsista cadastrado.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<section class="section-header section-header--compact">
    <div>
        <h2>Autorizacoes emitidas</h2>
        <p>Consulte sala, dias, horario e validade das permissoes registradas.</p>
    </div>
</section>

<div class="card table-wrap">
    <table>
        <thead>
            <tr>
                <th>Bolsista</th>
                <th>Sala</th>
                <th>Dias</th>
                <th>Horario</th>
                <th>Validade</th>
                <th>Finalidade</th>
                <th>Situacao</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (($permissoes ?? []) as $p): ?>
                <tr>
                    <td><?= e($p['usuario_nome'] ?? '-') ?></td>
                    <td><?= e($p['sala_nome'] ?? '-') ?></td>
                    <td><?= e($p['dias_semana'] ?: 'Todos os dias') ?></td>
                    <td><?= e(!empty($p['horario_inicio']) && !empty($p['horario_fim']) ? substr((string) $p['horario_inicio'], 0, 5) . ' as ' . substr((string) $p['horario_fim'], 0, 5) : 'Horario livre') ?></td>
                    <td><?= e(formatDateBr($p['inicio_autorizacao'] ?? null, '-')) ?> ate <?= e(formatDateBr($p['expira_em'] ?? null, 'Sem expiracao')) ?></td>
                    <td><?= e($p['observacao'] ?: '-') ?></td>
                    <td><span class="status-badge status-<?= e($p['situacao']) ?>"><?= e($p['situacao']) ?></span></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($permissoes)): ?>
                <tr><td colspan="7">Nenhuma autorizacao emitida para seus bolsistas.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
