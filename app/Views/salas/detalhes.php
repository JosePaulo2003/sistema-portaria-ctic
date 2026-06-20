<section class="section-header">
    <div>
        <h1><?= e($sala['nome']) ?></h1>
        <p><?= e(ucfirst($sala['tipo_ambiente'])) ?><?= $sala['capacidade'] ? ' · ' . e($sala['capacidade']) . ' lugares' : '' ?></p>
    </div>
    <?php if ($status): ?>
        <span class="status-badge status-<?= e(mb_strtolower(str_replace(['ç','ã','á','é','í','ó','ú'], ['c','a','a','e','i','o','u'], $status['status_consulta_publica']))) ?>">
            <?= e($status['status_consulta_publica']) ?>
        </span>
    <?php endif; ?>
</section>

<div class="dashboard-grid">
    <article class="card">
        <h2>Código</h2>
        <p><?= e($sala['codigo'] ?: 'Não informado') ?></p>
    </article>
    <article class="card">
        <h2>Bloco</h2>
        <p><?= e($sala['bloco'] ?: 'Não informado') ?></p>
    </article>
    <article class="card">
        <h2>Situação</h2>
        <p><?= e($status['motivo_status'] ?? ucfirst($sala['situacao'])) ?></p>
    </article>
</div>

<section class="resource-section">
    <h2>Reservas</h2>
    <div class="card table-wrap">
        <table>
            <thead><tr><th>Título</th><th>Usuário</th><th>Início</th><th>Fim</th><th>Situação</th></tr></thead>
            <tbody>
                <?php foreach ($reservas as $reserva): ?>
                    <tr>
                        <td><?= e($reserva['titulo']) ?></td>
                        <td><?= e($reserva['usuario_nome']) ?></td>
                        <td><?= e(date('d/m/Y H:i', strtotime($reserva['inicio_em']))) ?></td>
                        <td><?= e(date('d/m/Y H:i', strtotime($reserva['fim_em']))) ?></td>
                        <td><span class="status-badge"><?= e($reserva['situacao']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$reservas): ?><tr><td colspan="5">Nenhuma reserva registrada para esta sala.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="resource-section">
    <h2>Aulas do semestre</h2>
    <div class="card table-wrap">
        <table>
            <thead><tr><th>Disciplina</th><th>Professor</th><th>Turma</th><th>Dia</th><th>Horário</th><th>Situação</th></tr></thead>
            <tbody>
                <?php foreach ($aulas as $aula): ?>
                    <tr>
                        <td><?= e($aula['disciplina']) ?></td>
                        <td><?= e($aula['professor_nome']) ?></td>
                        <td><?= e($aula['turma']) ?></td>
                        <td><?= e($aula['dia_semana']) ?></td>
                        <td><?= e(substr($aula['horario_inicio'], 0, 5)) ?> às <?= e(substr($aula['horario_fim'], 0, 5)) ?></td>
                        <td><span class="status-badge"><?= e($aula['situacao']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$aulas): ?><tr><td colspan="6">Nenhuma aula vinculada a esta sala.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="resource-section">
    <h2>Ocupações e movimentações</h2>
    <div class="card table-wrap">
        <table>
            <thead><tr><th>Usuário</th><th>Tipo</th><th>Retirada</th><th>Devolução</th><th>Situação</th><th>Observação</th></tr></thead>
            <tbody>
                <?php foreach ($movimentacoes as $mov): ?>
                    <tr>
                        <td><?= e($mov['usuario_nome']) ?></td>
                        <td><?= e(str_replace('_', ' ', $mov['tipo_movimentacao'])) ?></td>
                        <td><?= e($mov['retirada_em'] ? date('d/m/Y H:i', strtotime($mov['retirada_em'])) : '-') ?></td>
                        <td><?= e($mov['devolucao_real_em'] ? date('d/m/Y H:i', strtotime($mov['devolucao_real_em'])) : '-') ?></td>
                        <td><span class="status-badge"><?= e($mov['situacao']) ?></span></td>
                        <td><?= e($mov['observacao'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$movimentacoes): ?><tr><td colspan="6">Nenhuma movimentação registrada para esta sala.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
