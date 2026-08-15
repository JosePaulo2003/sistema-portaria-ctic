<?php
$statusRotulos = [
    'confirmada' => 'Confirmada',
    'pendente' => 'Pendente',
    'encerrada' => 'Encerrada',
    'aula' => 'Aula',
    'retirada' => 'Chave retirada',
];
$salaId = (int) $sala['id'];
?>

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
    <div class="section-header room-calendar-heading">
        <div>
            <h2>Agenda mensal da sala</h2>
            <p>Reservas, aulas recorrentes e pessoas que retiraram a chave, organizadas por dia.</p>
        </div>
    </div>

    <section class="card room-calendar-toolbar room-calendar-toolbar--room" aria-label="Controles do calendário">
        <nav class="room-calendar-navigation" aria-label="Navegação entre meses">
            <a
                class="button button--secondary room-calendar-navigation__arrow"
                href="<?= e(baseUrl('/salas/detalhes?id=' . $salaId . '&mes=' . $mesAnterior)) ?>"
                data-calendar-month-link
                aria-label="Mês anterior"
            >‹</a>
            <a
                class="button button--secondary"
                href="<?= e(baseUrl('/salas/detalhes?id=' . $salaId . '&mes=' . $mesAtual)) ?>"
                data-calendar-month-link
            >Hoje</a>
            <a
                class="button button--secondary room-calendar-navigation__arrow"
                href="<?= e(baseUrl('/salas/detalhes?id=' . $salaId . '&mes=' . $mesSeguinte)) ?>"
                data-calendar-month-link
                aria-label="Próximo mês"
            >›</a>
        </nav>

        <div class="room-calendar-toolbar__month" aria-live="polite">
            <strong><?= e($rotuloMes) ?></strong>
            <span><?= e($totalReservas) ?> reservas · <?= e($totalAulasMes) ?> aulas · <?= e($totalRetiradas) ?> retiradas</span>
        </div>
    </section>

    <div class="room-calendar-legend" aria-label="Legenda do calendário">
        <span><i class="room-calendar-legend__color is-confirmada"></i>Reserva confirmada</span>
        <span><i class="room-calendar-legend__color is-pendente"></i>Reserva pendente</span>
        <span><i class="room-calendar-legend__color is-encerrada"></i>Reserva encerrada</span>
        <span><i class="room-calendar-legend__color is-aula"></i>Aula recorrente</span>
        <span><i class="room-calendar-legend__color is-retirada"></i>Retirada de chave</span>
        <strong data-calendar-visible-count></strong>
    </div>

    <section class="card room-calendar" data-room-calendar>
        <div class="room-calendar__weekdays" aria-hidden="true">
            <?php foreach (['Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado', 'Domingo'] as $diaSemana): ?>
                <span><?= e($diaSemana) ?></span>
            <?php endforeach; ?>
        </div>

        <div class="room-calendar__grid">
            <?php foreach ($semanas as $semana): ?>
                <?php foreach ($semana as $dia): ?>
                    <article
                        class="room-calendar__day<?= !$dia['dentro_mes'] ? ' is-outside' : '' ?><?= $dia['hoje'] ? ' is-today' : '' ?>"
                        data-calendar-day
                        aria-label="<?= e($dia['dia_semana'] . ', ' . formatDateBr($dia['data'])) ?>"
                    >
                        <header class="room-calendar__day-header">
                            <span class="room-calendar__day-weekday"><?= e($dia['dia_semana']) ?></span>
                            <time datetime="<?= e($dia['data']) ?>"><?= e($dia['dia']) ?></time>
                            <?php if ($dia['hoje']): ?><span>Hoje</span><?php endif; ?>
                        </header>

                        <div class="room-calendar__events">
                            <?php foreach ($dia['eventos'] as $evento): ?>
                                <?php
                                $situacao = array_key_exists((string) $evento['situacao'], $statusRotulos)
                                    ? (string) $evento['situacao']
                                    : 'pendente';
                                $tipoPrincipal = match ((string) ($evento['tipo'] ?? '')) {
                                    'aula' => 'Aula',
                                    'retirada' => 'Retirada de chave',
                                    default => 'Reserva',
                                };
                                ?>
                                <a
                                    class="room-calendar-event is-<?= e($situacao) ?>"
                                    href="<?= e(baseUrl('/salas/atividade?tipo=' . urlencode((string) $evento['atividade_tipo']) . '&id=' . (int) $evento['atividade_id'] . '&mes=' . $inicioMes->format('Y-m') . '&data=' . $dia['data'])) ?>"
                                    data-calendar-event
                                    data-room-id="<?= e($salaId) ?>"
                                    title="<?= e($evento['horario'] . ' · ' . $evento['titulo']) ?>"
                                >
                                    <span class="room-calendar-event__time"><?= e($evento['horario']) ?></span>
                                    <strong><?= e($tipoPrincipal) ?></strong>
                                    <span class="room-calendar-event__title"><?= e($evento['titulo']) ?></span>
                                    <small><?= e($statusRotulos[$situacao]) ?></small>
                                </a>
                            <?php endforeach; ?>

                            <span class="room-calendar__empty" data-calendar-day-empty <?= $dia['eventos'] ? 'hidden' : '' ?>>Sem atividades</span>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    </section>

    <p class="muted room-calendar-tip">Clique em uma atividade para abrir todas as informações em uma página. Use as setas para navegar pelos meses.</p>
</section>
