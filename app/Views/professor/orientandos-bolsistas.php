<?php $diasSemana = ['Segunda', 'Terca', 'Quarta', 'Quinta', 'Sexta', 'Sabado', 'Domingo']; ?>

<section class="section-header">
    <h1>Orientandos Bolsistas</h1>
</section>

<form method="post" class="card form-grid">
    <?= csrfField() ?>
    <label>Nome
        <input name="nome" required>
    </label>
    <label>E-mail
        <input type="email" name="email" required>
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
    <div class="form-actions">
        <button class="button">Cadastrar bolsista</button>
    </div>
</form>

<?php if ($bolsistas): ?>
    <form method="post" action="<?= e(baseUrl('/professor/orientandos-bolsistas/liberar-chave')) ?>" class="card form-grid">
        <?= csrfField() ?>
        <label>Bolsista
            <select name="usuario_id" required>
                <?php foreach ($bolsistas as $b): ?>
                    <option value="<?= e($b['id']) ?>"><?= e($b['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Sala autorizada
            <select name="sala_id" required>
                <?php foreach ($salas as $s): ?>
                    <option value="<?= e($s['id']) ?>"><?= e($s['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Inicio
            <input type="datetime-local" name="inicio_autorizacao">
        </label>
        <label>Expira em
            <input type="datetime-local" name="expira_em">
        </label>
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
        <label class="full">Observacao
            <textarea name="observacao"></textarea>
        </label>
        <div class="form-actions">
            <button class="button">Liberar chave</button>
        </div>
    </form>
<?php endif; ?>

<div class="card table-wrap">
    <table>
        <thead>
            <tr>
                <th>Bolsista</th>
                <th>Projeto</th>
                <th>Situacao</th>
                <th>Acoes</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($bolsistas as $b): ?>
                <tr>
                    <td colspan="4">
                        <form method="post" action="<?= e(baseUrl('/professor/orientandos-bolsistas/atualizar')) ?>" class="inline-form row-edit-form row-edit-form--orientando">
                            <?= csrfField() ?>
                            <input type="hidden" name="id" value="<?= e($b['id']) ?>">
                            <label>Nome
                                <input name="nome" required value="<?= e($b['nome']) ?>">
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
                            <?= csrfField() ?>
                            <input type="hidden" name="id" value="<?= e($b['id']) ?>">
                            <button class="button button--danger" data-confirm="Apagar orientando? Se houver historico, ele sera anonimizado.">Apagar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$bolsistas): ?>
                <tr><td colspan="4">Nenhum bolsista cadastrado.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="card table-wrap">
    <table>
        <thead>
            <tr>
                <th>Bolsista</th>
                <th>Sala</th>
                <th>Dias</th>
                <th>Expira</th>
                <th>Situacao</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (($permissoes ?? []) as $p): ?>
                <tr>
                    <td><?= e($p['usuario_nome'] ?? '-') ?></td>
                    <td><?= e($p['sala_nome'] ?? '-') ?></td>
                    <td><?= e($p['dias_semana'] ?: 'Todos os dias') ?></td>
                    <td><?= e(!empty($p['expira_em']) ? date('d/m/Y H:i', strtotime($p['expira_em'])) : 'Sem expiracao') ?></td>
                    <td><span class="status-badge"><?= e($p['situacao']) ?></span></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($permissoes)): ?>
                <tr><td colspan="5">Nenhuma chave liberada para seus bolsistas.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
