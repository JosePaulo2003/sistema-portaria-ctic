<?php
$periodoLegivel = formatDateBr($filtros['data']) . ', de ' . $filtros['hora_inicio'] . ' às ' . $filtros['hora_fim'];
?>

<section class="section-header portaria-report-heading no-print">
    <div>
        <h1>Relatório de movimentações das salas</h1>
        <p>Consulte e imprima as retiradas e devoluções registradas em um período específico do dia.</p>
    </div>
    <div class="portaria-report-heading__actions">
        <a class="button button--secondary" href="<?= e(baseUrl('/portaria/historico')) ?>">Histórico geral</a>
        <button class="button" type="button" data-print-page <?= $erroPeriodo !== '' ? 'disabled' : '' ?>>Imprimir relatório</button>
    </div>
</section>

<form method="get" action="<?= e(baseUrl('/portaria/relatorio-movimentacoes')) ?>" class="card portaria-report-filters no-print">
    <header>
        <h2>Escolha o período</h2>
        <p>A consulta considera retiradas ou devoluções que aconteceram entre as horas informadas.</p>
    </header>
    <div class="portaria-report-filter-grid">
        <label>Data
            <input type="date" name="data" value="<?= e($filtros['data']) ?>" required>
        </label>
        <label>Hora inicial
            <input type="time" name="hora_inicio" value="<?= e($filtros['hora_inicio']) ?>" required>
        </label>
        <label>Hora final
            <input type="time" name="hora_fim" value="<?= e($filtros['hora_fim']) ?>" required>
        </label>
        <label>Sala
            <select name="sala_id">
                <option value="0">Todas as salas</option>
                <?php foreach ($salas as $sala): ?>
                    <option value="<?= e($sala['id']) ?>" <?= (int) $filtros['sala_id'] === (int) $sala['id'] ? 'selected' : '' ?>>
                        <?= e($sala['nome'] . (!empty($sala['codigo']) ? ' · ' . $sala['codigo'] : '')) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>
    <div class="portaria-report-filter-actions">
        <button class="button" type="submit">Gerar relatório</button>
        <a class="button button--secondary" href="<?= e(baseUrl('/portaria/relatorio-movimentacoes')) ?>">Usar o dia inteiro</a>
    </div>
</form>

<?php if ($erroPeriodo !== ''): ?>
    <div class="card portaria-report-error no-print" role="alert"><?= e($erroPeriodo) ?></div>
<?php endif; ?>

<header class="print-only portaria-print-document-header">
    <div>
        <strong>SGRP</strong>
        <span>Sistema de Gestão de Recursos Pedagógicos</span>
    </div>
    <div>
        <h1>Relatório de movimentações das salas</h1>
        <p>Emitido em <?= e(formatDateTimeBr($geradoEm)) ?></p>
    </div>
</header>

<section class="portaria-report-identification">
    <div>
        <span>Período consultado</span>
        <strong><?= e($periodoLegivel) ?></strong>
    </div>
    <div>
        <span>Ambiente</span>
        <strong><?= e($salaSelecionadaNome) ?></strong>
    </div>
</section>

<section class="portaria-report-summary" aria-label="Resumo do relatório">
    <article class="card"><span>Ações registradas</span><strong><?= e($quantidadeAcoes) ?></strong></article>
    <article class="card"><span>Registros encontrados</span><strong><?= e(count($movimentacoes)) ?></strong></article>
    <article class="card"><span>Salas movimentadas</span><strong><?= e($quantidadeSalas) ?></strong></article>
    <article class="card"><span>Pessoas atendidas</span><strong><?= e($quantidadeUsuarios) ?></strong></article>
</section>

<section class="portaria-report-results">
    <div class="director-section-title no-print">
        <div>
            <h2>Movimentações encontradas</h2>
            <p>O relatório abaixo está pronto para impressão.</p>
        </div>
        <button class="button" type="button" data-print-page <?= $erroPeriodo !== '' ? 'disabled' : '' ?>>Imprimir</button>
    </div>

    <div class="card table-wrap portaria-print-table">
        <table>
            <thead>
                <tr>
                    <th>Ocorrência</th>
                    <th>Sala</th>
                    <th>Usuário</th>
                    <th>Retirada</th>
                    <th>Devolução</th>
                    <th>Devolvido por</th>
                    <th>Registrado por</th>
                    <th>Situação</th>
                    <th>Observação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($movimentacoes as $movimentacao): ?>
                    <?php
                    $devolvidoPor = trim((string) ($movimentacao['devolvido_por_nome'] ?? ''));
                    if ($devolvidoPor === '' && ($movimentacao['situacao'] ?? '') === 'finalizada') {
                        $devolvidoPor = 'Não cadastrada ou não informada';
                    }
                    ?>
                    <tr>
                        <td data-label="Ocorrência"><strong><?= e($movimentacao['acoes_periodo']) ?></strong></td>
                        <td data-label="Sala"><strong><?= e($movimentacao['sala_nome'] ?? '-') ?></strong><br><span class="muted"><?= e($movimentacao['sala_codigo'] ?? '-') ?></span></td>
                        <td data-label="Usuário"><strong><?= e($movimentacao['usuario_nome'] ?? '-') ?></strong><br><span class="muted"><?= e($movimentacao['usuario_perfil'] ?? '-') ?></span></td>
                        <td data-label="Retirada"><?= e(formatDateTimeBr($movimentacao['retirada_em'] ?? $movimentacao['criado_em'] ?? null)) ?></td>
                        <td data-label="Devolução"><?= e(formatDateTimeBr($movimentacao['devolucao_real_em'] ?? null)) ?></td>
                        <td data-label="Devolvido por"><?= e($devolvidoPor !== '' ? $devolvidoPor : '-') ?></td>
                        <td data-label="Registrado por"><?= e($movimentacao['registrado_por_nome'] ?? '-') ?></td>
                        <td data-label="Situação"><span class="status-badge"><?= e($movimentacao['situacao'] ?? '-') ?></span></td>
                        <td data-label="Observação" class="report-note"><?= e($movimentacao['observacao'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$movimentacoes): ?>
                    <tr><td colspan="9">Nenhuma movimentação de sala foi encontrada nesse período.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<footer class="print-only portaria-print-signatures">
    <div><span>Responsável pela emissão</span></div>
    <div><span>Agente de Portaria</span></div>
</footer>
