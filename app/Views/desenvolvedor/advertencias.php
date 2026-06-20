<section class="section-header"><h1>Advertências</h1></section>

<form method="post" class="card form-grid">
    <?= csrfField() ?>
    <label>Dias de bloqueio após 3 advertências
        <input type="number" min="1" name="dias" value="<?= e($config) ?>">
    </label>
    <div class="form-actions"><button class="button" type="submit">Salvar configuração</button></div>
</form>

<section class="section-header">
    <h1>Bloqueios por usuário</h1>
    <p>Um bloqueio por usuário. Altere o fim do bloqueio ou zere para liberar imediatamente.</p>
</section>

<div class="card table-wrap">
    <table>
        <thead>
            <tr>
                <th>Usuário</th>
                <th>E-mail</th>
                <th>Motivo</th>
                <th>Início</th>
                <th>Fim</th>
                <th>Situação</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($bloqueios as $bloqueio): ?>
                <tr>
                    <td><?= e($bloqueio['usuario_nome']) ?></td>
                    <td><?= e($bloqueio['usuario_email'] ?? $bloqueio['email'] ?? '-') ?></td>
                    <td><?= e($bloqueio['advertencia_motivo'] ?? '-') ?></td>
                    <td><?= e(date('d/m/Y H:i', strtotime($bloqueio['inicio_em']))) ?></td>
                    <td><?= e(date('d/m/Y H:i', strtotime($bloqueio['fim_em']))) ?></td>
                    <td><span class="status-badge"><?= e($bloqueio['situacao']) ?></span></td>
                    <td>
                        <div class="inline-actions">
                            <form method="post" action="<?= e(baseUrl('/desenvolvedor/bloqueios/atualizar')) ?>" class="inline-form">
                                <?= csrfField() ?>
                                <input type="hidden" name="id" value="<?= e($bloqueio['id']) ?>">
                                <input type="hidden" name="acao" value="atualizar">
                                <input type="datetime-local" name="fim_em" value="<?= e(date('Y-m-d\TH:i', strtotime($bloqueio['fim_em']))) ?>">
                                <button class="button" type="submit">Alterar</button>
                            </form>
                            <?php if ($bloqueio['situacao'] === 'ativo'): ?>
                                <form method="post" action="<?= e(baseUrl('/desenvolvedor/bloqueios/atualizar')) ?>">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="id" value="<?= e($bloqueio['id']) ?>">
                                    <input type="hidden" name="acao" value="zerar">
                                    <button class="button button--secondary" data-confirm="Zerar este bloqueio e liberar o usuário agora?" type="submit">Zerar</button>
                                </form>
                            <?php endif; ?>
                            <form method="post" action="<?= e(baseUrl('/desenvolvedor/bloqueios/excluir')) ?>">
                                <?= csrfField() ?>
                                <input type="hidden" name="id" value="<?= e($bloqueio['id']) ?>">
                                <button class="button button--danger" data-confirm="Apagar este bloqueio da lista?" type="submit">Apagar</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$bloqueios): ?>
                <tr><td colspan="7">Nenhum bloqueio registrado.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<section class="section-header">
    <h1>Histórico de advertências</h1>
    <form method="post" action="<?= e(baseUrl('/desenvolvedor/advertencias/limpar')) ?>" class="inline-actions">
        <?= csrfField() ?>
        <button class="button button--danger" data-confirm="Limpar todo o histórico de advertências? Os bloqueios existentes serão mantidos." type="submit">Limpar histórico</button>
    </form>
</section>

<div class="card table-wrap">
    <table>
        <thead>
            <tr>
                <th>Usuário</th>
                <th>Agente</th>
                <th>Motivo</th>
                <th>Data</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($advertencias as $adv): ?>
                <tr>
                    <td><?= e($adv['usuario_nome']) ?></td>
                    <td><?= e($adv['agente_nome']) ?></td>
                    <td><?= e($adv['motivo']) ?></td>
                    <td><?= e($adv['criado_em']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$advertencias): ?>
                <tr><td colspan="4">Nenhuma advertência registrada.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
