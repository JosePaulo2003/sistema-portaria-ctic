<section class="section-header">
    <h1>Reservas</h1>
    <p>Visao geral das solicitacoes e reservas aprovadas.</p>
</section>

<div class="card table-wrap">
    <table>
        <thead>
            <tr>
                <th>Titulo</th>
                <th>Sala</th>
                <th>Solicitante</th>
                <th>Inicio</th>
                <th>Fim</th>
                <th>Situacao</th>
                <th>Acoes</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (($reservas ?? []) as $reserva): ?>
                <tr>
                    <td><?= e($reserva['titulo'] ?? '-') ?></td>
                    <td><?= e($reserva['sala_nome'] ?? '-') ?></td>
                    <td><?= e($reserva['usuario_nome'] ?? '-') ?></td>
                    <td><?= e(!empty($reserva['inicio_em']) ? date('d/m/Y H:i', strtotime($reserva['inicio_em'])) : '-') ?></td>
                    <td><?= e(!empty($reserva['fim_em']) ? date('d/m/Y H:i', strtotime($reserva['fim_em'])) : '-') ?></td>
                    <td><span class="status-badge"><?= e($reserva['situacao'] ?? '-') ?></span></td>
                    <td>
                        <form method="post" action="<?= e(baseUrl('/diretor/reservas/status')) ?>" class="inline-form">
                            <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>" data-csrf-token>
                            <input type="hidden" name="id" value="<?= e($reserva['id']) ?>">
                            <label class="sr-only">Status</label>
                            <select name="situacao">
                                <?php foreach (['pendente', 'confirmada', 'cancelada', 'encerrada'] as $situacao): ?>
                                    <option value="<?= e($situacao) ?>" <?= ($reserva['situacao'] ?? '') === $situacao ? 'selected' : '' ?>><?= e($situacao) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="button">Salvar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($reservas)): ?>
                <tr><td colspan="7">Nenhuma reserva registrada.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
