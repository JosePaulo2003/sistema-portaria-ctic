<section class="section-header">
    <h1>Logs</h1>
    <p>Auditoria de acoes e falhas tecnicas registradas pelo sistema.</p>
</section>

<form method="get" class="card form-grid filters">
    <label>Modulo<input name="modulo" value="<?= e($_GET['modulo'] ?? '') ?>"></label>
    <label>Acao<input name="acao" value="<?= e($_GET['acao'] ?? '') ?>"></label>
    <label>Termo<input name="termo" value="<?= e($_GET['termo'] ?? '') ?>"></label>
    <label>Data inicial<input type="date" name="data_inicial" value="<?= e($_GET['data_inicial'] ?? '') ?>"></label>
    <label>Data final<input type="date" name="data_final" value="<?= e($_GET['data_final'] ?? '') ?>"></label>
    <label>Limite<input type="number" name="limite" value="<?= e($_GET['limite'] ?? '100') ?>"></label>
    <div class="form-actions"><button class="button" type="submit">Filtrar</button></div>
</form>

<form method="post" action="<?= e(baseUrl('/desenvolvedor/logs/limpar')) ?>" class="inline-actions">
    <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>" data-csrf-token>
    <input type="hidden" name="modo" value="todos">
    <button class="button button--danger" data-confirm="Limpar todos os logs de auditoria?" type="submit">Limpar auditoria</button>
</form>

<section class="section-header">
    <h1>Auditoria</h1>
</section>
<div class="card table-wrap">
    <table>
        <thead>
            <tr><th>Data</th><th>Usuario</th><th>Modulo</th><th>Acao</th><th>Descricao</th></tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= e(formatDateTimeBr($log['criado_em'] ?? null)) ?></td>
                    <td><?= e($log['usuario_nome'] ?? '-') ?></td>
                    <td><?= e($log['modulo']) ?></td>
                    <td><?= e($log['acao']) ?></td>
                    <td><?= e($log['descricao']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($logs)): ?>
                <tr><td colspan="5">Nenhum log de auditoria registrado.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<section class="section-header">
    <h1>Logs tecnicos</h1>
    <p>Falhas internas, recuperacao de senha e eventos sensiveis.</p>
</section>
<div class="card table-wrap">
    <table>
        <thead>
            <tr><th>Data</th><th>Nivel</th><th>Origem</th><th>Usuario</th><th>Mensagem</th></tr>
        </thead>
        <tbody>
            <?php foreach (($systemLogs ?? []) as $log): ?>
                <tr>
                    <td><?= e(formatDateTimeBr($log['criado_em'] ?? null)) ?></td>
                    <td><span class="status-badge"><?= e($log['nivel']) ?></span></td>
                    <td><?= e($log['origem']) ?></td>
                    <td><?= e($log['usuario_nome'] ?? '-') ?></td>
                    <td><?= e($log['mensagem']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($systemLogs)): ?>
                <tr><td colspan="5">Nenhum log tecnico registrado.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
