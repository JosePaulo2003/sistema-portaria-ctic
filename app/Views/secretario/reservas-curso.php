<section class="section-header">
    <h1>Reservas do Curso</h1>
    <p>Solicite reservas de sala. A Portaria aprova quando não houver conflito.</p>
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
    <label>Título
        <input name="titulo" required>
    </label>
    <label>Início
        <input type="datetime-local" name="inicio_em" required>
    </label>
    <label>Fim
        <input type="datetime-local" name="fim_em" required>
    </label>
    <label class="full">Finalidade
        <textarea name="finalidade"></textarea>
    </label>
    <div class="form-actions">
        <button class="button" type="submit">Solicitar reserva</button>
    </div>
</form>

<div class="card table-wrap">
    <table>
        <thead>
            <tr>
                <th>Título</th>
                <th>Sala</th>
                <th>Solicitante</th>
                <th>Início</th>
                <th>Fim</th>
                <th>Situação</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (($reservas ?? []) as $r): ?>
                <tr>
                    <td><?= e($r['titulo'] ?? '-') ?></td>
                    <td><?= e($r['sala_nome'] ?? '-') ?></td>
                    <td><?= e($r['usuario_nome'] ?? '-') ?></td>
                    <td><?= e(!empty($r['inicio_em']) ? date('d/m/Y H:i', strtotime($r['inicio_em'])) : '-') ?></td>
                    <td><?= e(!empty($r['fim_em']) ? date('d/m/Y H:i', strtotime($r['fim_em'])) : '-') ?></td>
                    <td><span class="status-badge"><?= e($r['situacao'] ?? '-') ?></span></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($reservas)): ?>
                <tr><td colspan="6">Nenhuma reserva cadastrada.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
