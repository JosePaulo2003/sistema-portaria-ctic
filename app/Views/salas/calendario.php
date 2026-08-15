<?php
$salaQuery = !empty($salaFiltro) ? '&sala_id=' . (int) $salaFiltro : '';
$statusRotulos = [
    'confirmada' => 'Confirmada',
    'pendente' => 'Pendente',
    'encerrada' => 'Encerrada',
    'aula' => 'Aula',
    'retirada' => 'Chave retirada',
];
?>

<section class="section-header room-calendar-heading">
    <div>
        <h1>Calendário de Salas</h1>
        <p>Visualize reservas, aulas e retiradas de chave distribuídas ao longo de cada mês.</p>
    </div>
</section>

<section class="card room-calendar-toolbar" aria-label="Controles do calendário">
    <nav class="room-calendar-navigation" aria-label="Navegação entre meses">
        <a
            class="button button--secondary room-calendar-navigation__arrow"
            href="<?= e(baseUrl('/calendario-salas?mes=' . $mesAnterior . $salaQuery)) ?>"
            data-calendar-month-link
            aria-label="Mês anterior"
        >‹</a>
        <a
            class="button button--secondary"
            href="<?= e(baseUrl('/calendario-salas?mes=' . $mesAtual . $salaQuery)) ?>"
            data-calendar-month-link
        >Hoje</a>
        <a
            class="button button--secondary room-calendar-navigation__arrow"
            href="<?= e(baseUrl('/calendario-salas?mes=' . $mesSeguinte . $salaQuery)) ?>"
            data-calendar-month-link
            aria-label="Próximo mês"
        >›</a>
    </nav>

    <div class="room-calendar-toolbar__month" aria-live="polite">
        <strong><?= e($rotuloMes) ?></strong>
        <span><?= e($totalReservas) ?> reservas · <?= e($totalAulasMes) ?> aulas · <?= e($totalRetiradas) ?> retiradas</span>
    </div>

    <label class="room-calendar-filter">Filtrar por sala
        <select data-calendar-room-filter>
            <option value="0">Todas as salas</option>
            <?php foreach ($salas as $sala): ?>
                <option value="<?= e((int) $sala['id']) ?>" <?= (int) $salaFiltro === (int) $sala['id'] ? 'selected' : '' ?>>
                    <?= e($sala['nome']) ?><?= !empty($sala['codigo']) ? ' - ' . e($sala['codigo']) : '' ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
</section>

<div class="room-calendar-legend" aria-label="Legenda do calendário">
    <span><i class="room-calendar-legend__color is-confirmada"></i>Confirmada</span>
    <span><i class="room-calendar-legend__color is-pendente"></i>Pendente</span>
    <span><i class="room-calendar-legend__color is-encerrada"></i>Encerrada</span>
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
                <?php
                $eventosVisiveis = array_filter(
                    $dia['eventos'],
                    static fn (array $evento): bool => empty($salaFiltro) || (int) ($evento['sala_id'] ?? 0) === (int) $salaFiltro
                );
                ?>
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
                            $eventoOculto = !empty($salaFiltro) && (int) ($evento['sala_id'] ?? 0) !== (int) $salaFiltro;
                            $conteudoEvento = trim($evento['sala_nome'] . ' ' . $evento['horario'] . ' ' . $evento['titulo']);
                            ?>
                            <?php if (!empty($evento['atividade_id'])): ?>
                                <a
                                    class="room-calendar-event is-<?= e($situacao) ?>"
                                    href="<?= e(baseUrl('/salas/atividade?tipo=' . urlencode((string) $evento['atividade_tipo']) . '&id=' . (int) $evento['atividade_id'] . '&mes=' . $inicioMes->format('Y-m') . '&data=' . $dia['data'])) ?>"
                                    data-calendar-event
                                    data-room-id="<?= e((int) $evento['sala_id']) ?>"
                                    title="<?= e($conteudoEvento) ?>"
                                    <?= $eventoOculto ? 'hidden' : '' ?>
                                >
                            <?php else: ?>
                                <div
                                    class="room-calendar-event is-<?= e($situacao) ?>"
                                    data-calendar-event
                                    data-room-id="0"
                                    title="<?= e($conteudoEvento) ?>"
                                    <?= $eventoOculto ? 'hidden' : '' ?>
                                >
                            <?php endif; ?>
                                    <span class="room-calendar-event__time"><?= e($evento['horario']) ?></span>
                                    <strong><?= e($evento['sala_nome']) ?></strong>
                                    <span class="room-calendar-event__title"><?= e($evento['titulo']) ?></span>
                                    <small><?= e($statusRotulos[$situacao]) ?></small>
                            <?php if (!empty($evento['atividade_id'])): ?>
                                </a>
                            <?php else: ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>

                        <span class="room-calendar__empty" data-calendar-day-empty <?= $eventosVisiveis ? 'hidden' : '' ?>>Sem atividades</span>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>
</section>

<p class="muted room-calendar-tip">Clique em uma atividade para abrir todas as informações em uma página. Reservas canceladas não são exibidas.</p>
