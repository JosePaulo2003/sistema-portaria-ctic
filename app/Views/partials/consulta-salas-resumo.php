<section class="section-header">
    <h1><?= e($title ?? 'Consulta de Salas') ?></h1>
    <p>Disponibilidade pública por chave retirada, reserva ativa, manutenção ou bloqueio.</p>
</section>

<?php
$salasCadastradas = (new \App\Models\Sala())->all('nome');
$sugestoesBuscaSala = [];

foreach ($salasCadastradas as $salaCadastrada) {
    $nomeSala = trim((string) ($salaCadastrada['nome'] ?? ''));
    if ($nomeSala !== '') {
        $sugestoesBuscaSala[mb_strtolower($nomeSala)] = $nomeSala;
    }
}

natcasesort($sugestoesBuscaSala);
?>

<form method="get" class="card form-grid filters">
    <div class="room-autocomplete" data-room-autocomplete>
        <label for="salas-filtro-busca">Busca</label>
        <div class="room-autocomplete__field">
            <input
                id="salas-filtro-busca"
                name="busca"
                value="<?= e($_GET['busca'] ?? '') ?>"
                placeholder="Digite ou selecione uma sala"
                autocomplete="off"
                aria-autocomplete="list"
                aria-controls="salas-filtro-sugestoes"
                aria-expanded="false"
                data-room-autocomplete-input
            >
            <div
                id="salas-filtro-sugestoes"
                class="room-autocomplete__list"
                role="listbox"
                data-room-autocomplete-list
                hidden
            >
            <?php foreach ($sugestoesBuscaSala as $sugestaoBuscaSala): ?>
                <button
                    type="button"
                    class="room-autocomplete__option"
                    role="option"
                    tabindex="-1"
                    data-room-autocomplete-option
                    data-value="<?= e($sugestaoBuscaSala) ?>"
                ><?= e($sugestaoBuscaSala) ?></button>
            <?php endforeach; ?>
                <p class="room-autocomplete__empty" data-room-autocomplete-empty hidden>Nenhuma sala encontrada.</p>
            </div>
        </div>
    </div>
    <label>Status
        <select name="status">
            <option value="">Todos</option>
            <?php foreach (['Aberta','Reservada','Fechada','Manutenção','Bloqueada'] as $status): ?>
                <option value="<?= e($status) ?>" <?= ($_GET['status'] ?? '') === $status ? 'selected' : '' ?>><?= e($status) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Tipo
        <select name="tipo_ambiente">
            <option value="">Todos</option>
            <?php foreach (['laboratorio','institucional','administrativo','setor'] as $tipo): ?>
                <option value="<?= e($tipo) ?>" <?= ($_GET['tipo_ambiente'] ?? '') === $tipo ? 'selected' : '' ?>><?= e(ucfirst($tipo)) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Data
        <input type="date" name="data" value="<?= e($_GET['data'] ?? date('Y-m-d')) ?>">
    </label>
    <label>Horário
        <input type="time" name="horario" value="<?= e($_GET['horario'] ?? date('H:i')) ?>">
    </label>
    <div class="form-actions">
        <button class="button" type="submit">Filtrar</button>
    </div>
</form>

<?php
$podeSolicitarSala = isProfile('Professor') || isDeveloper();
$dataFiltro = (string) ($_GET['data'] ?? date('Y-m-d'));
$horarioFiltro = (string) ($_GET['horario'] ?? date('H:i'));
$inicioSolicitacao = DateTime::createFromFormat('Y-m-d H:i', $dataFiltro . ' ' . $horarioFiltro) ?: new DateTime();
$fimSolicitacao = (clone $inicioSolicitacao)->modify('+1 hour');
$inicioSolicitacaoValor = $inicioSolicitacao->format('Y-m-d\TH:i');
$fimSolicitacaoValor = $fimSolicitacao->format('Y-m-d\TH:i');
?>

<div class="room-grid">
    <?php foreach ($salas as $sala): ?>
        <?php $statusClass = mb_strtolower(str_replace(['ç','ã','á','é','í','ó','ú'], ['c','a','a','e','i','o','u'], $sala['status_consulta_publica'])); ?>
        <article class="card room-card">
            <div class="room-card__header">
                <h2 title="<?= e($sala['nome']) ?>"><?= e($sala['nome']) ?></h2>
                <span class="status-badge status-<?= e($statusClass) ?>"><?= e($sala['status_consulta_publica']) ?></span>
            </div>
            <p class="room-card__meta">
                <?= e(ucfirst($sala['tipo_ambiente'])) ?><?= $sala['capacidade'] ? ' · ' . e($sala['capacidade']) . ' lugares' : '' ?>
            </p>
            <p class="room-card__reason"><?= e($sala['motivo_status']) ?></p>
            <div class="room-card__actions">
                <a class="button button--secondary room-card__link" href="<?= e(baseUrl('/salas/detalhes?id=' . $sala['id'])) ?>">Ver sala</a>
                <?php if ($podeSolicitarSala && ($sala['status_consulta_publica'] ?? '') === 'Fechada'): ?>
                    <form method="post" action="<?= e(baseUrl('/professor/reservas-salas')) ?>" class="room-card__request">
                        <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>" data-csrf-token>
                        <input type="hidden" name="sala_id" value="<?= e($sala['id']) ?>">
                        <input type="hidden" name="titulo" value="<?= e('Solicitação de sala - ' . $sala['nome']) ?>">
                        <input type="hidden" name="inicio_em" value="<?= e($inicioSolicitacaoValor) ?>">
                        <input type="hidden" name="fim_em" value="<?= e($fimSolicitacaoValor) ?>">
                        <input type="hidden" name="finalidade" value="Solicitação feita pela consulta de disponibilidade.">
                        <button class="button" type="submit">Solicitar sala</button>
                    </form>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
    <?php if (!$salas): ?>
        <div class="card">Nenhuma sala encontrada.</div>
    <?php endif; ?>
</div>
