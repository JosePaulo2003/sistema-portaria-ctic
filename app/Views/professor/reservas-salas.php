<section class="section-header">
    <h1>Reservas de Salas</h1>
</section>

<form method="post" class="card form-grid">
    <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>" data-csrf-token>
    <label>Sala
        <select name="sala_id" required>
            <?php foreach ($salas as $s): ?>
                <option value="<?= e($s['id']) ?>"><?= e($s['nome']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Titulo
        <input name="titulo" required>
    </label>
    <label>Inicio
        <input type="datetime-local" name="inicio_em" required>
    </label>
    <label>Fim
        <input type="datetime-local" name="fim_em" required>
    </label>
    <label class="full">Finalidade
        <textarea name="finalidade"></textarea>
    </label>
    <div class="form-actions">
        <button class="button">Solicitar reserva</button>
    </div>
</form>

<div class="card table-wrap">
    <table>
        <thead>
            <tr>
                <th>Titulo</th>
                <th>Sala</th>
                <th>Inicio</th>
                <th>Fim</th>
                <th>Situacao</th>
                <th>Acoes</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reservas as $r): ?>
                <tr>
                    <td>
                        <strong><?= e($r['titulo']) ?></strong>
                        <?php if (!empty($r['finalidade'])): ?>
                            <br><span class="muted"><?= e($r['finalidade']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= e($r['sala_nome'] ?? '-') ?></td>
                    <td><?= e(date('d/m/Y H:i', strtotime($r['inicio_em']))) ?></td>
                    <td><?= e(date('d/m/Y H:i', strtotime($r['fim_em']))) ?></td>
                    <td><span class="status-badge"><?= e($r['situacao']) ?></span></td>
                    <td>
                        <?php if (($r['situacao'] ?? '') === 'pendente'): ?>
                            <form method="post" action="<?= e(baseUrl('/professor/reservas-salas/excluir')) ?>" class="inline-actions">
                                <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>" data-csrf-token>
                                <input type="hidden" name="id" value="<?= e($r['id']) ?>">
                                <button class="button button--danger" data-confirm="Excluir esta reserva?">Excluir</button>
                            </form>
                        <?php else: ?>
                            <span class="muted">Sem acao</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$reservas): ?>
                <tr><td colspan="6">Nenhuma reserva cadastrada.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
