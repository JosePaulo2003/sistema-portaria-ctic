<section class="section-header">
    <h1>Reservas pendentes</h1>
    <p>Aprove apenas reservas sem conflito com outra reserva confirmada, chave retirada ou sala indisponível.</p>
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
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reservas as $reserva): ?>
                <tr>
                    <td><?= e($reserva['titulo']) ?></td>
                    <td><?= e($reserva['sala_nome'] ?? '-') ?></td>
                    <td><?= e($reserva['usuario_nome']) ?></td>
                    <td><?= e(date('d/m/Y H:i', strtotime($reserva['inicio_em']))) ?></td>
                    <td><?= e(date('d/m/Y H:i', strtotime($reserva['fim_em']))) ?></td>
                    <td>
                        <form method="post" action="<?= e(baseUrl('/portaria/reservas/atualizar')) ?>" class="inline-actions">
                            <?= csrfField() ?>
                            <input type="hidden" name="id" value="<?= e($reserva['id']) ?>">
                            <button class="button" name="acao" value="aprovar" type="submit">Aprovar</button>
                            <button class="button button--danger" name="acao" value="recusar" type="submit" data-confirm="Recusar esta reserva?">Recusar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$reservas): ?>
                <tr><td colspan="6">Nenhuma reserva pendente.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
