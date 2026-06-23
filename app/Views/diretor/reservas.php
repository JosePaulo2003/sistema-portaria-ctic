<section class="section-header">
    <h1>Reservas</h1>
    <p>Visão geral das solicitações e reservas aprovadas.</p>
</section>

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
            <?php foreach (($reservas ?? []) as $reserva): ?>
                <tr>
                    <td><?= e($reserva['titulo'] ?? '-') ?></td>
                    <td><?= e($reserva['sala_nome'] ?? '-') ?></td>
                    <td><?= e($reserva['usuario_nome'] ?? '-') ?></td>
                    <td><?= e(!empty($reserva['inicio_em']) ? date('d/m/Y H:i', strtotime($reserva['inicio_em'])) : '-') ?></td>
                    <td><?= e(!empty($reserva['fim_em']) ? date('d/m/Y H:i', strtotime($reserva['fim_em'])) : '-') ?></td>
                    <td><span class="status-badge"><?= e($reserva['situacao'] ?? '-') ?></span></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($reservas)): ?>
                <tr><td colspan="6">Nenhuma reserva registrada.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
