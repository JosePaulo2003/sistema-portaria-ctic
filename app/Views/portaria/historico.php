<section class="section-header">
    <h1>Historico da Portaria</h1>
</section>

<form method="get" class="card form-grid filters">
    <label>Pesquisar
        <input name="busca" value="<?= e($filtros['busca'] ?? '') ?>" placeholder="Usuario, sala, item ou observacao">
    </label>
    <label>Tipo
        <select name="tipo_movimentacao">
            <option value="">Todos</option>
            <?php foreach (['retirada_chave', 'devolucao_chave', 'retirada_item', 'devolucao_item', 'retirada_recurso', 'devolucao_recurso'] as $tipo): ?>
                <option value="<?= e($tipo) ?>" <?= ($filtros['tipo_movimentacao'] ?? '') === $tipo ? 'selected' : '' ?>><?= e($tipo) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Situacao
        <select name="situacao">
            <option value="">Todas</option>
            <?php foreach (['aberta', 'finalizada', 'cancelada'] as $situacao): ?>
                <option value="<?= e($situacao) ?>" <?= ($filtros['situacao'] ?? '') === $situacao ? 'selected' : '' ?>><?= e($situacao) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Inicio
        <input type="date" name="data_inicio" value="<?= e($filtros['data_inicio'] ?? '') ?>">
    </label>
    <label>Fim
        <input type="date" name="data_fim" value="<?= e($filtros['data_fim'] ?? '') ?>">
    </label>
    <div class="form-actions">
        <button class="button">Filtrar</button>
        <a class="button button--secondary" href="<?= e(baseUrl('/portaria/historico')) ?>">Limpar</a>
    </div>
</form>

<?php require __DIR__ . '/_movimentacoes.php'; ?>
