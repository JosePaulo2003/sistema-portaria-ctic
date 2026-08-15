<?php
$variacao = static function (int $atual, int $anterior): array {
    if ($anterior === 0) {
        return $atual === 0
            ? ['texto' => 'Sem alteração', 'classe' => 'is-neutral']
            : ['texto' => 'Novo movimento no período', 'classe' => 'is-up'];
    }
    $percentual = (int) round((($atual - $anterior) / $anterior) * 100);
    if ($percentual === 0) {
        return ['texto' => 'Igual ao período anterior', 'classe' => 'is-neutral'];
    }
    return [
        'texto' => ($percentual > 0 ? '+' : '') . $percentual . '% ante o período anterior',
        'classe' => $percentual > 0 ? 'is-up' : 'is-down',
    ];
};
$variacaoTotal = $variacao((int) $resumo['total'], (int) $resumoAnterior['total']);
$formatarDuracao = static function (int $minutos): string {
    if ($minutos <= 0) {
        return 'Sem dados';
    }
    $horas = intdiv($minutos, 60);
    $restante = $minutos % 60;
    return $horas > 0 ? $horas . 'h ' . $restante . 'min' : $restante . 'min';
};
$rotulosMovimentacao = [
    'retirada_chave' => 'Retirada de chave',
    'devolucao_chave' => 'Devolução de chave',
    'retirada_item' => 'Retirada de item',
    'devolucao_item' => 'Devolução de item',
    'retirada_recurso' => 'Retirada de recurso',
    'devolucao_recurso' => 'Devolução de recurso',
];
$paletaPizza = [
    ['cor' => '#287a57', 'classe' => 'is-series-1'],
    ['cor' => '#2f7fa5', 'classe' => 'is-series-2'],
    ['cor' => '#7a4aa0', 'classe' => 'is-series-3'],
    ['cor' => '#d18b16', 'classe' => 'is-series-4'],
    ['cor' => '#c75d43', 'classe' => 'is-series-5'],
    ['cor' => '#607d74', 'classe' => 'is-series-6'],
];
$pizzaPerfis = [];
foreach ($distribuicaoPerfis as $indicePerfil => $perfil) {
    $pizzaPerfis[] = array_merge($perfil, $paletaPizza[$indicePerfil % count($paletaPizza)]);
}
$totalPizza = array_sum(array_column($pizzaPerfis, 'valor'));
foreach ($pizzaPerfis as &$fatiaPizza) {
    $fatiaPizza['percentual'] = $totalPizza > 0
        ? (int) round(($fatiaPizza['valor'] / $totalPizza) * 100)
        : 0;
}
unset($fatiaPizza);
$fatiasPositivas = array_values(array_filter($pizzaPerfis, static fn (array $item): bool => $item['valor'] > 0));
$arcosPizza = [];
if (count($fatiasPositivas) > 1 && $totalPizza > 0) {
    $anguloInicial = -90.0;
    foreach ($fatiasPositivas as $itemPizza) {
        $amplitude = ($itemPizza['valor'] / $totalPizza) * 360;
        $anguloFinal = $anguloInicial + $amplitude;
        $inicioRad = deg2rad($anguloInicial);
        $fimRad = deg2rad($anguloFinal);
        $xInicial = 100 + 96 * cos($inicioRad);
        $yInicial = 100 + 96 * sin($inicioRad);
        $xFinal = 100 + 96 * cos($fimRad);
        $yFinal = 100 + 96 * sin($fimRad);
        $arcoGrande = $amplitude > 180 ? 1 : 0;
        $arcosPizza[] = [
            'cor' => $itemPizza['cor'],
            'descricao' => $itemPizza['rotulo'] . ': ' . $itemPizza['valor'],
            'caminho' => sprintf(
                'M 100 100 L %.4F %.4F A 96 96 0 %d 1 %.4F %.4F Z',
                $xInicial,
                $yInicial,
                $arcoGrande,
                $xFinal,
                $yFinal
            ),
        ];
        $anguloInicial = $anguloFinal;
    }
}
$descricaoPizza = $totalPizza > 0
    ? implode(', ', array_map(static fn (array $item): string => $item['rotulo'] . ': ' . $item['valor'], $pizzaPerfis))
    : 'Nenhum acesso registrado no período';
$maximoSerie = max(1, ...array_column($serie ?: [['valor' => 0]], 'valor'));
$larguraGrafico = max(520, count($serie) * 96);
$barrasSerie = [];
foreach ($serie as $indiceSerie => $pontoSerie) {
    $espaco = $larguraGrafico / max(1, count($serie));
    $larguraBarra = min(48, $espaco * .52);
    $alturaBarra = (int) $pontoSerie['valor'] > 0
        ? max(5.0, ((int) $pontoSerie['valor'] / $maximoSerie) * 150)
        : 0.0;
    $centro = ($indiceSerie * $espaco) + ($espaco / 2);
    $barrasSerie[] = $pontoSerie + [
        'x' => $centro - ($larguraBarra / 2),
        'centro' => $centro,
        'largura' => $larguraBarra,
        'altura' => $alturaBarra,
        'y' => 195 - $alturaBarra,
        'y_valor' => $alturaBarra > 0 ? max(18, 187 - $alturaBarra) : 187,
    ];
}
?>

<section class="section-header director-heading">
    <div>
        <p class="director-heading__eyebrow">Inteligência operacional</p>
        <h1>Relatórios da Direção</h1>
        <p>Histórico diário, semanal e mensal com indicadores e exportação.</p>
    </div>
    <a class="button button--secondary" href="<?= e(baseUrl('/diretor')) ?>">Voltar ao resumo</a>
</section>

<form method="get" action="<?= e(baseUrl('/diretor/relatorios')) ?>" class="card report-filters">
    <header class="report-filters__header">
        <div>
            <h2>Configurar relatório</h2>
            <p>Escolha o período e refine os registros que deseja analisar.</p>
        </div>
        <span>Dados atualizados ao abrir a página</span>
    </header>

    <fieldset class="report-period-selector">
        <legend>Período do relatório</legend>
        <?php foreach (['diario' => 'Diário', 'semanal' => 'Semanal', 'mensal' => 'Mensal'] as $valor => $rotulo): ?>
            <label>
                <input type="radio" name="periodicidade" value="<?= e($valor) ?>" <?= $filtros['periodicidade'] === $valor ? 'checked' : '' ?>>
                <span><?= e($rotulo) ?></span>
            </label>
        <?php endforeach; ?>
    </fieldset>

    <div class="report-filter-grid">
        <label>Data de referência
            <input type="date" name="referencia" value="<?= e($filtros['referencia']) ?>" required>
        </label>
        <label>Tipo de recurso
            <select name="tipo_recurso">
                <option value="">Todos</option>
                <option value="chave" <?= $filtros['tipo_recurso'] === 'chave' ? 'selected' : '' ?>>Chaves</option>
                <option value="item" <?= $filtros['tipo_recurso'] === 'item' ? 'selected' : '' ?>>Itens</option>
                <option value="outro" <?= $filtros['tipo_recurso'] === 'outro' ? 'selected' : '' ?>>Outros recursos</option>
            </select>
        </label>
        <label>Situação
            <select name="situacao">
                <option value="">Todas</option>
                <option value="aberta" <?= $filtros['situacao'] === 'aberta' ? 'selected' : '' ?>>Em aberto</option>
                <option value="finalizada" <?= $filtros['situacao'] === 'finalizada' ? 'selected' : '' ?>>Finalizada</option>
                <option value="cancelada" <?= $filtros['situacao'] === 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
            </select>
        </label>
        <label>Movimentação
            <select name="tipo_movimentacao">
                <option value="">Todas</option>
                <?php foreach ($rotulosMovimentacao as $valor => $rotulo): ?>
                    <option value="<?= e($valor) ?>" <?= $filtros['tipo_movimentacao'] === $valor ? 'selected' : '' ?>><?= e($rotulo) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <div class="report-search-field room-autocomplete report-autocomplete" data-room-autocomplete>
            <label for="relatorio-filtro-busca">Pesquisar nos registros</label>
            <div class="room-autocomplete__field">
                <input
                    id="relatorio-filtro-busca"
                    name="busca"
                    value="<?= e($filtros['busca']) ?>"
                    maxlength="120"
                    placeholder="Digite ou selecione usuário, sala, item ou código"
                    autocomplete="off"
                    aria-autocomplete="list"
                    aria-controls="relatorio-filtro-sugestoes"
                    aria-expanded="false"
                    data-room-autocomplete-input
                >
                <div
                    id="relatorio-filtro-sugestoes"
                    class="room-autocomplete__list report-autocomplete__list"
                    role="listbox"
                    data-room-autocomplete-list
                    hidden
                >
                    <?php foreach ($sugestoesBusca as $sugestao): ?>
                        <button
                            type="button"
                            class="room-autocomplete__option report-autocomplete__option"
                            role="option"
                            tabindex="-1"
                            data-room-autocomplete-option
                            data-value="<?= e($sugestao['valor']) ?>"
                        ><span><?= e($sugestao['valor']) ?></span><small><?= e($sugestao['tipo']) ?></small></button>
                    <?php endforeach; ?>
                    <p class="room-autocomplete__empty" data-room-autocomplete-empty hidden>Nenhum cadastro correspondente.</p>
                </div>
            </div>
            <small>As sugestões vêm dos usuários ativos, salas, itens e códigos cadastrados.</small>
        </div>
    </div>

    <div class="report-filter-actions">
        <button class="button" type="submit">Atualizar relatório</button>
        <a class="button button--secondary" href="<?= e(baseUrl('/diretor/relatorios')) ?>">Limpar filtros</a>
        <a class="button report-export-button" href="<?= e($exportarUrl) ?>">Exportar CSV</a>
    </div>
</form>

<section class="report-period-heading">
    <div>
        <span>Período analisado</span>
        <h2><?= e($filtros['rotulo']) ?></h2>
    </div>
    <p><?= e($totalEncontrado) ?> registro(s) encontrado(s)</p>
</section>

<section class="report-kpi-grid" aria-label="Indicadores do período">
    <article class="card report-kpi report-kpi--primary">
        <span>Total de movimentações</span>
        <strong><?= e($resumo['total']) ?></strong>
        <small class="<?= e($variacaoTotal['classe']) ?>"><?= e($variacaoTotal['texto']) ?></small>
    </article>
    <article class="card report-kpi">
        <span>Chaves movimentadas</span>
        <strong><?= e($resumo['chaves']) ?></strong>
        <small><?= e($resumo['itens']) ?> item(ns) no mesmo período</small>
    </article>
    <article class="card report-kpi">
        <span>Pessoas atendidas</span>
        <strong><?= e($resumo['usuarios']) ?></strong>
        <small>usuários distintos</small>
    </article>
    <article class="card report-kpi <?= $resumo['abertas'] > 0 ? 'report-kpi--warning' : '' ?>">
        <span>Em aberto</span>
        <strong><?= e($resumo['abertas']) ?></strong>
        <small>aguardando devolução</small>
    </article>
    <article class="card report-kpi">
        <span>Taxa de conclusão</span>
        <strong><?= e($resumo['taxa_devolucao']) ?>%</strong>
        <small><?= e($resumo['finalizadas']) ?> finalizada(s)</small>
    </article>
    <article class="card report-kpi">
        <span>Tempo médio de uso</span>
        <strong><?= e($formatarDuracao((int) $resumo['tempo_medio_minutos'])) ?></strong>
        <small>entre retirada e devolução</small>
    </article>
</section>

<section class="report-section-intro">
    <div>
        <span>Análise visual</span>
        <h2>Quem acessou os recursos</h2>
    </div>
    <p>Os gráficos respondem automaticamente aos filtros aplicados.</p>
</section>

<section class="report-visual-grid">
    <article class="card report-pie-card">
        <header>
            <h2>Acessos por perfil</h2>
            <p>Participação de cada perfil nas movimentações do período.</p>
        </header>
        <div class="report-pie-layout">
            <svg
                class="report-pie"
                viewBox="0 0 200 200"
                role="img"
                aria-label="<?= e($descricaoPizza) ?>"
            >
                <title><?= e($descricaoPizza) ?></title>
                <?php if ($totalPizza <= 0): ?>
                    <circle cx="100" cy="100" r="96" fill="#e4ebe7"></circle>
                <?php elseif (count($fatiasPositivas) === 1): ?>
                    <circle cx="100" cy="100" r="96" fill="<?= e($fatiasPositivas[0]['cor']) ?>"></circle>
                <?php else: ?>
                    <?php foreach ($arcosPizza as $arcoPizza): ?>
                        <path d="<?= e($arcoPizza['caminho']) ?>" fill="<?= e($arcoPizza['cor']) ?>" stroke="#ffffff" stroke-width="1">
                            <title><?= e($arcoPizza['descricao']) ?></title>
                        </path>
                    <?php endforeach; ?>
                <?php endif; ?>
            </svg>
            <div class="report-pie-summary">
                <div class="report-pie-total"><strong><?= e($totalPizza) ?></strong><span>acessos registrados</span></div>
                <ul class="report-pie-legend">
                    <?php foreach ($pizzaPerfis as $itemPizza): ?>
                        <li>
                            <i class="<?= e($itemPizza['classe']) ?>"></i>
                            <span><?= e($itemPizza['rotulo']) ?></span>
                            <strong><?= e($itemPizza['valor']) ?></strong>
                            <small><?= e($itemPizza['percentual']) ?>%</small>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </article>

    <article class="card report-timeline-card">
        <header>
            <h2>Movimentações ao longo do período</h2>
            <p><?= e($filtros['periodicidade'] === 'diario' ? 'Distribuição por hora.' : 'Distribuição por dia.') ?></p>
        </header>
        <div class="report-chart-scroll">
            <svg class="report-timeline-svg" viewBox="0 0 <?= e($larguraGrafico) ?> 240" role="img" aria-label="Gráfico de movimentações no período" preserveAspectRatio="xMidYMid meet">
                <title>Movimentações ao longo do período</title>
                <?php foreach ([45, 95, 145, 195] as $linhaY): ?>
                    <line class="report-svg-grid-line" x1="10" y1="<?= e($linhaY) ?>" x2="<?= e($larguraGrafico - 10) ?>" y2="<?= e($linhaY) ?>"></line>
                <?php endforeach; ?>
                <?php foreach ($barrasSerie as $barraSerie): ?>
                    <g>
                        <title><?= e($barraSerie['rotulo'] . ': ' . $barraSerie['valor'] . ' movimentação(ões)') ?></title>
                        <?php if ((int) $barraSerie['valor'] > 0): ?>
                            <rect class="report-svg-bar" x="<?= e(number_format($barraSerie['x'], 2, '.', '')) ?>" y="<?= e(number_format($barraSerie['y'], 2, '.', '')) ?>" width="<?= e(number_format($barraSerie['largura'], 2, '.', '')) ?>" height="<?= e(number_format($barraSerie['altura'], 2, '.', '')) ?>" rx="6"></rect>
                        <?php endif; ?>
                        <text class="report-svg-value" x="<?= e(number_format($barraSerie['centro'], 2, '.', '')) ?>" y="<?= e(number_format($barraSerie['y_valor'], 2, '.', '')) ?>" text-anchor="middle"><?= e($barraSerie['valor']) ?></text>
                        <text class="report-svg-label" x="<?= e(number_format($barraSerie['centro'], 2, '.', '')) ?>" y="222" text-anchor="middle"><?= e($barraSerie['rotulo']) ?></text>
                    </g>
                <?php endforeach; ?>
            </svg>
        </div>
    </article>
</section>

<section class="report-section-intro report-section-intro--compact">
    <div>
        <span>Destaques</span>
        <h2>Maiores volumes do período</h2>
    </div>
</section>

<section class="report-ranking-grid">
    <article class="card report-ranking">
        <header><h2>Recursos mais movimentados</h2><p>Chaves e itens com maior utilização.</p></header>
        <ol>
            <?php foreach ($rankingRecursos as $item): ?>
                <li>
                    <div><span><?= e($item['rotulo']) ?></span><strong><?= e($item['valor']) ?></strong></div>
                    <svg class="report-ranking-meter" viewBox="0 0 100 7" preserveAspectRatio="none" aria-hidden="true">
                        <rect class="report-ranking-meter__track" x="0" y="0" width="100" height="7" rx="3.5"></rect>
                        <rect class="report-ranking-meter__value" x="0" y="0" width="<?= e($item['percentual']) ?>" height="7" rx="3.5"></rect>
                    </svg>
                </li>
            <?php endforeach; ?>
            <?php if (!$rankingRecursos): ?><li class="report-ranking__empty">Nenhum recurso no período.</li><?php endif; ?>
        </ol>
    </article>

    <article class="card report-ranking">
        <header><h2>Usuários com mais movimentações</h2><p>Pessoas com maior número de registros.</p></header>
        <ol>
            <?php foreach ($rankingUsuarios as $item): ?>
                <li>
                    <div><span><?= e($item['rotulo']) ?></span><strong><?= e($item['valor']) ?></strong></div>
                    <svg class="report-ranking-meter" viewBox="0 0 100 7" preserveAspectRatio="none" aria-hidden="true">
                        <rect class="report-ranking-meter__track" x="0" y="0" width="100" height="7" rx="3.5"></rect>
                        <rect class="report-ranking-meter__value" x="0" y="0" width="<?= e($item['percentual']) ?>" height="7" rx="3.5"></rect>
                    </svg>
                </li>
            <?php endforeach; ?>
            <?php if (!$rankingUsuarios): ?><li class="report-ranking__empty">Nenhum usuário no período.</li><?php endif; ?>
        </ol>
    </article>
</section>

<section class="report-history-section">
    <div class="director-section-title">
        <div>
            <h2>Histórico detalhado</h2>
            <p>Registros que compõem os indicadores acima.</p>
        </div>
        <a class="button report-export-button" href="<?= e($exportarUrl) ?>">Exportar <?= e($totalEncontrado) ?> registro(s)</a>
    </div>

    <?php if ($totalEncontrado > $limiteTabela): ?>
        <p class="report-limit-note">A tela mostra os <?= e($limiteTabela) ?> registros mais recentes. O arquivo exportado contém todos os <?= e($totalEncontrado) ?> registros filtrados.</p>
    <?php endif; ?>

    <div class="card table-wrap report-history-table">
        <table>
            <thead>
                <tr>
                    <th>Usuário</th>
                    <th>Recurso</th>
                    <th>Movimentação</th>
                    <th>Retirada</th>
                    <th>Devolução</th>
                    <th>Registrado por</th>
                    <th>Situação</th>
                    <th>Observação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($movimentacoes as $movimentacao): ?>
                    <?php
                    $recurso = $movimentacao['sala_nome'] ?? $movimentacao['item_nome'] ?? 'Outro recurso';
                    $codigo = $movimentacao['sala_codigo'] ?? $movimentacao['item_codigo'] ?? '';
                    ?>
                    <tr>
                        <td data-label="Usuário"><strong><?= e($movimentacao['usuario_nome'] ?? '-') ?></strong><br><span class="muted"><?= e($movimentacao['usuario_perfil'] ?? '-') ?></span></td>
                        <td data-label="Recurso"><strong><?= e($recurso) ?></strong><?php if ($codigo !== ''): ?><br><span class="muted"><?= e($codigo) ?></span><?php endif; ?></td>
                        <td data-label="Movimentação"><?= e($rotulosMovimentacao[$movimentacao['tipo_movimentacao'] ?? ''] ?? str_replace('_', ' ', (string) ($movimentacao['tipo_movimentacao'] ?? '-'))) ?></td>
                        <td data-label="Retirada"><?= e(formatDateTimeBr($movimentacao['retirada_em'] ?? $movimentacao['criado_em'] ?? null)) ?></td>
                        <td data-label="Devolução"><?= e(formatDateTimeBr($movimentacao['devolucao_real_em'] ?? null)) ?></td>
                        <td data-label="Registrado por"><?= e($movimentacao['registrado_por_nome'] ?? '-') ?></td>
                        <td data-label="Situação"><span class="status-badge"><?= e($movimentacao['situacao'] ?? '-') ?></span></td>
                        <td data-label="Observação" class="report-note"><?= e($movimentacao['observacao'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$movimentacoes): ?>
                    <tr><td colspan="8">Nenhuma movimentação encontrada para os filtros selecionados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
