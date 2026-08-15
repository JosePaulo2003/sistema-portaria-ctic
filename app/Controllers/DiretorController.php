<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Movimentacao;
use App\Models\Reserva;
use App\Models\Sala;

// Painel executivo da Direção: operação atual, relatórios e exportação segura.
class DiretorController extends Controller
{
    public function index(): void
    {
        requireProfile('Diretor');

        $agora = new \DateTimeImmutable();
        $inicioHoje = $agora->setTime(0, 0);
        $inicioSemana = $inicioHoje->modify('monday this week');
        $inicioMes = $inicioHoje->modify('first day of this month');
        $model = new Movimentacao();
        $abertas = $model->abertas();

        $this->view('diretor/index', [
            'title' => 'Resumo da Direção',
            'resumoHoje' => $this->resumirMovimentacoes($model->relatorioDirecao(
                $inicioHoje->format('Y-m-d H:i:s'),
                $inicioHoje->modify('+1 day')->format('Y-m-d H:i:s')
            )),
            'resumoSemana' => $this->resumirMovimentacoes($model->relatorioDirecao(
                $inicioSemana->format('Y-m-d H:i:s'),
                $inicioSemana->modify('+7 days')->format('Y-m-d H:i:s')
            )),
            'resumoMes' => $this->resumirMovimentacoes($model->relatorioDirecao(
                $inicioMes->format('Y-m-d H:i:s'),
                $inicioMes->modify('+1 month')->format('Y-m-d H:i:s')
            )),
            'abertas' => $abertas,
            'chavesAbertas' => count(array_filter($abertas, static fn (array $mov): bool => !empty($mov['sala_id']))),
            'itensAbertos' => count(array_filter($abertas, static fn (array $mov): bool => !empty($mov['item_portaria_id']))),
            'movimentacoesRecentes' => $model->historico(8),
        ]);
    }

    public function chaves(): void
    {
        requireProfile('Diretor');
        $this->view('diretor/chaves', [
            'title' => 'Chaves',
            'salas' => (new Sala())->chavesParaRetirada(currentUser()),
        ]);
    }

    public function reservas(): void
    {
        requireProfile('Diretor');
        $this->view('diretor/reservas', [
            'title' => 'Reservas',
            'reservas' => (new Reserva())->withDetails(),
        ]);
    }

    public function atualizarReservaStatus(): void
    {
        requireProfile('Diretor');
        verifyCsrf();

        $status = (string) ($_POST['situacao'] ?? '');
        if (!in_array($status, ['pendente', 'confirmada', 'cancelada', 'encerrada'], true)) {
            flash('error', 'Status de reserva inválido.');
            redirect('/diretor/reservas');
        }

        $reservaModel = new Reserva();
        $reserva = $reservaModel->find((int) ($_POST['id'] ?? 0));
        if (!$reserva) {
            flash('error', 'Reserva não encontrada.');
            redirect('/diretor/reservas');
        }
        if ($status === 'confirmada' && !$reservaModel->podeAprovar($reserva)) {
            flash('error', 'Não foi possível confirmar: existe conflito ou a sala não está disponível.');
            redirect('/diretor/reservas');
        }

        $reservaModel->update((int) $reserva['id'], ['situacao' => $status]);
        audit('Direcao', 'atualizacao_status_reserva', 'Status de reserva atualizado pela direção.', [
            'reserva_id' => $reserva['id'],
            'situacao' => $status,
        ]);
        flash('success', 'Status da reserva atualizado.');
        redirect('/diretor/reservas');
    }

    public function movimentacoes(): void
    {
        $this->relatorios();
    }

    public function relatorios(): void
    {
        requireProfile('Diretor');

        $filtros = $this->filtrosRelatorio($_GET);
        $model = new Movimentacao();
        $movimentacoes = $model->relatorioDirecao(
            $filtros['inicio']->format('Y-m-d H:i:s'),
            $filtros['fim']->format('Y-m-d H:i:s'),
            $filtros
        );
        [$inicioAnterior, $fimAnterior] = $this->intervaloAnterior($filtros);
        $movimentacoesAnteriores = $model->relatorioDirecao(
            $inicioAnterior->format('Y-m-d H:i:s'),
            $fimAnterior->format('Y-m-d H:i:s'),
            $filtros
        );

        $this->view('diretor/relatorios', [
            'title' => 'Relatórios da Direção',
            'filtros' => $filtros,
            'resumo' => $this->resumirMovimentacoes($movimentacoes),
            'resumoAnterior' => $this->resumirMovimentacoes($movimentacoesAnteriores),
            'serie' => $this->seriePizzaMovimentacoes($movimentacoes, $filtros),
            'distribuicaoPerfis' => $this->distribuicaoPerfis($movimentacoes),
            'rankingRecursos' => $this->rankingMovimentacoes($movimentacoes, 'recurso'),
            'rankingUsuarios' => $this->rankingMovimentacoes($movimentacoes, 'usuario'),
            'movimentacoes' => array_slice($movimentacoes, 0, 300),
            'totalEncontrado' => count($movimentacoes),
            'limiteTabela' => 300,
            'sugestoesBusca' => $model->sugestoesRelatorioDirecao(),
            'exportarUrl' => baseUrl('/diretor/relatorios/exportar?' . http_build_query($this->parametrosPublicosRelatorio($filtros))),
        ]);
    }

    public function exportarRelatorio(): void
    {
        requireProfile('Diretor');

        $filtros = $this->filtrosRelatorio($_GET);
        $movimentacoes = (new Movimentacao())->relatorioDirecao(
            $filtros['inicio']->format('Y-m-d H:i:s'),
            $filtros['fim']->format('Y-m-d H:i:s'),
            $filtros
        );

        audit('Direcao', 'exportacao_relatorio_movimentacoes', 'Relatório de movimentações exportado pela direção.', [
            'periodicidade' => $filtros['periodicidade'],
            'inicio' => $filtros['inicio']->format('Y-m-d'),
            'fim_exclusivo' => $filtros['fim']->format('Y-m-d'),
            'quantidade' => count($movimentacoes),
        ]);

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $nomeArquivo = sprintf('sgrp-movimentacoes-%s-%s.csv', $filtros['periodicidade'], $filtros['referencia']);
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store, max-age=0');

        $saida = fopen('php://output', 'wb');
        if ($saida === false) {
            http_response_code(500);
            return;
        }
        fwrite($saida, "\xEF\xBB\xBF");

        $seguroParaPlanilha = static function ($valor): string {
            $texto = trim((string) $valor);
            return $texto !== '' && preg_match('/^[=+\-@]/u', $texto) ? "'" . $texto : $texto;
        };
        $dataHora = static function ($valor): string {
            $timestamp = $valor ? strtotime((string) $valor) : false;
            return $timestamp ? date('d/m/Y H:i:s', $timestamp) : '';
        };

        fputcsv($saida, [
            'ID', 'Usuário', 'Perfil', 'Tipo de recurso', 'Recurso', 'Código',
            'Movimentação', 'Situação', 'Retirada', 'Devolução prevista',
            'Devolução real', 'Devolvido por', 'Registrado por', 'Observação',
            'Criado em', 'Atualizado em',
        ], ';', '"', '\\');

        foreach ($movimentacoes as $movimentacao) {
            $tipoRecurso = !empty($movimentacao['sala_id']) ? 'Chave'
                : (!empty($movimentacao['item_portaria_id']) ? 'Item' : 'Outro recurso');
            $recurso = $movimentacao['sala_nome'] ?? $movimentacao['item_nome'] ?? 'Não identificado';
            $codigo = $movimentacao['sala_codigo'] ?? $movimentacao['item_codigo'] ?? '';
            $devolvidoPor = $movimentacao['devolvido_por_nome'] ?? '';
            if ($devolvidoPor === '' && ($movimentacao['situacao'] ?? '') === 'finalizada') {
                $devolvidoPor = 'Pessoa não cadastrada ou não informada';
            }

            $linha = [
                $movimentacao['id'] ?? '',
                $movimentacao['usuario_nome'] ?? '',
                $movimentacao['usuario_perfil'] ?? '',
                $tipoRecurso,
                $recurso,
                $codigo,
                str_replace('_', ' ', (string) ($movimentacao['tipo_movimentacao'] ?? '')),
                $movimentacao['situacao'] ?? '',
                $dataHora($movimentacao['retirada_em'] ?? null),
                $dataHora($movimentacao['devolucao_prevista_em'] ?? null),
                $dataHora($movimentacao['devolucao_real_em'] ?? null),
                $devolvidoPor,
                $movimentacao['registrado_por_nome'] ?? '',
                $movimentacao['observacao'] ?? '',
                $dataHora($movimentacao['criado_em'] ?? null),
                $dataHora($movimentacao['atualizado_em'] ?? null),
            ];
            fputcsv($saida, array_map($seguroParaPlanilha, $linha), ';', '"', '\\');
        }

        fclose($saida);
        exit;
    }

    public function disponibilidade(): void
    {
        requireProfile('Diretor');
        $this->view('diretor/disponibilidade', [
            'title' => 'Disponibilidade',
            'salas' => (new Sala())->listDisponibilidade($_GET),
        ]);
    }

    private function filtrosRelatorio(array $entrada): array
    {
        $periodicidade = (string) ($entrada['periodicidade'] ?? 'mensal');
        if (!in_array($periodicidade, ['diario', 'semanal', 'mensal'], true)) {
            $periodicidade = 'mensal';
        }

        $referenciaTexto = trim((string) ($entrada['referencia'] ?? date('Y-m-d')));
        $referencia = \DateTimeImmutable::createFromFormat('!Y-m-d', $referenciaTexto);
        if (!$referencia || $referencia->format('Y-m-d') !== $referenciaTexto) {
            $referencia = new \DateTimeImmutable('today');
            $referenciaTexto = $referencia->format('Y-m-d');
        }

        if ($periodicidade === 'diario') {
            $inicio = $referencia;
            $fim = $inicio->modify('+1 day');
            $rotulo = 'Dia ' . $inicio->format('d/m/Y');
        } elseif ($periodicidade === 'semanal') {
            $inicio = $referencia->modify('monday this week');
            $fim = $inicio->modify('+7 days');
            $rotulo = 'Semana de ' . $inicio->format('d/m/Y') . ' a ' . $fim->modify('-1 day')->format('d/m/Y');
        } else {
            $inicio = $referencia->modify('first day of this month');
            $fim = $inicio->modify('+1 month');
            $meses = [1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril', 5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'];
            $rotulo = $meses[(int) $inicio->format('n')] . ' de ' . $inicio->format('Y');
        }

        $situacao = trim((string) ($entrada['situacao'] ?? ''));
        if (!in_array($situacao, ['', 'aberta', 'finalizada', 'cancelada'], true)) {
            $situacao = '';
        }
        $tipoRecurso = trim((string) ($entrada['tipo_recurso'] ?? ''));
        if (!in_array($tipoRecurso, ['', 'chave', 'item', 'outro'], true)) {
            $tipoRecurso = '';
        }
        $tiposPermitidos = ['', 'retirada_chave', 'devolucao_chave', 'retirada_item', 'devolucao_item', 'retirada_recurso', 'devolucao_recurso'];
        $tipoMovimentacao = trim((string) ($entrada['tipo_movimentacao'] ?? ''));
        if (!in_array($tipoMovimentacao, $tiposPermitidos, true)) {
            $tipoMovimentacao = '';
        }

        return [
            'periodicidade' => $periodicidade,
            'referencia' => $referenciaTexto,
            'busca' => mb_substr(trim((string) ($entrada['busca'] ?? '')), 0, 120),
            'situacao' => $situacao,
            'tipo_recurso' => $tipoRecurso,
            'tipo_movimentacao' => $tipoMovimentacao,
            'inicio' => $inicio,
            'fim' => $fim,
            'rotulo' => $rotulo,
        ];
    }

    private function parametrosPublicosRelatorio(array $filtros): array
    {
        return array_filter([
            'periodicidade' => $filtros['periodicidade'],
            'referencia' => $filtros['referencia'],
            'busca' => $filtros['busca'],
            'situacao' => $filtros['situacao'],
            'tipo_recurso' => $filtros['tipo_recurso'],
            'tipo_movimentacao' => $filtros['tipo_movimentacao'],
        ], static fn ($valor): bool => $valor !== '');
    }

    private function intervaloAnterior(array $filtros): array
    {
        $fimAnterior = $filtros['inicio'];
        $inicioAnterior = match ($filtros['periodicidade']) {
            'diario' => $fimAnterior->modify('-1 day'),
            'semanal' => $fimAnterior->modify('-7 days'),
            default => $fimAnterior->modify('-1 month'),
        };
        return [$inicioAnterior, $fimAnterior];
    }

    private function resumirMovimentacoes(array $movimentacoes): array
    {
        $usuarios = [];
        $totalMinutos = 0;
        $devolucoesComTempo = 0;
        $resumo = [
            'total' => count($movimentacoes), 'chaves' => 0, 'itens' => 0, 'outros' => 0,
            'abertas' => 0, 'finalizadas' => 0, 'canceladas' => 0, 'usuarios' => 0,
            'taxa_devolucao' => 0, 'tempo_medio_minutos' => 0,
        ];

        foreach ($movimentacoes as $movimentacao) {
            if (!empty($movimentacao['sala_id'])) {
                $resumo['chaves']++;
            } elseif (!empty($movimentacao['item_portaria_id'])) {
                $resumo['itens']++;
            } else {
                $resumo['outros']++;
            }

            $situacao = (string) ($movimentacao['situacao'] ?? '');
            if (in_array($situacao, ['aberta', 'finalizada', 'cancelada'], true)) {
                $resumo[$situacao . 's']++;
            }
            if (!empty($movimentacao['usuario_id'])) {
                $usuarios[(int) $movimentacao['usuario_id']] = true;
            }
            if (!empty($movimentacao['retirada_em']) && !empty($movimentacao['devolucao_real_em'])) {
                $retirada = strtotime((string) $movimentacao['retirada_em']);
                $devolucao = strtotime((string) $movimentacao['devolucao_real_em']);
                if ($retirada !== false && $devolucao !== false && $devolucao >= $retirada) {
                    $totalMinutos += (int) floor(($devolucao - $retirada) / 60);
                    $devolucoesComTempo++;
                }
            }
        }

        $resumo['usuarios'] = count($usuarios);
        $resumo['taxa_devolucao'] = $resumo['total'] > 0 ? (int) round(($resumo['finalizadas'] / $resumo['total']) * 100) : 0;
        $resumo['tempo_medio_minutos'] = $devolucoesComTempo > 0 ? (int) round($totalMinutos / $devolucoesComTempo) : 0;
        return $resumo;
    }

    private function seriePizzaMovimentacoes(array $movimentacoes, array $filtros): array
    {
        $serie = [];
        if ($filtros['periodicidade'] === 'diario') {
            foreach (['00h–05h', '06h–11h', '12h–17h', '18h–23h'] as $indice => $rotulo) {
                $serie[(string) $indice] = ['rotulo' => $rotulo, 'valor' => 0];
            }
        } elseif ($filtros['periodicidade'] === 'semanal') {
            for ($dia = $filtros['inicio']; $dia < $filtros['fim']; $dia = $dia->modify('+1 day')) {
                $chave = $dia->format('Y-m-d');
                $serie[$chave] = [
                    'rotulo' => ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'][(int) $dia->format('w')],
                    'valor' => 0,
                ];
            }
        } else {
            $ultimoDia = (int) $filtros['fim']->modify('-1 day')->format('d');
            for ($inicioFaixa = 1, $indice = 0; $inicioFaixa <= $ultimoDia; $inicioFaixa += 7, $indice++) {
                $fimFaixa = min($inicioFaixa + 6, $ultimoDia);
                $serie[(string) $indice] = [
                    'rotulo' => str_pad((string) $inicioFaixa, 2, '0', STR_PAD_LEFT)
                        . '–'
                        . str_pad((string) $fimFaixa, 2, '0', STR_PAD_LEFT),
                    'valor' => 0,
                ];
            }
        }

        foreach ($movimentacoes as $movimentacao) {
            $timestamp = strtotime((string) ($movimentacao['momento_relatorio'] ?? $movimentacao['retirada_em'] ?? $movimentacao['criado_em'] ?? ''));
            if ($timestamp === false) {
                continue;
            }
            if ($filtros['periodicidade'] === 'diario') {
                $chave = (string) intdiv((int) date('G', $timestamp), 6);
            } elseif ($filtros['periodicidade'] === 'semanal') {
                $chave = date('Y-m-d', $timestamp);
            } else {
                $chave = (string) intdiv(((int) date('j', $timestamp)) - 1, 7);
            }
            if (isset($serie[$chave])) {
                $serie[$chave]['valor']++;
            }
        }

        $total = array_sum(array_column($serie, 'valor'));
        foreach ($serie as &$item) {
            $item['percentual'] = $total > 0 ? round(($item['valor'] / $total) * 100, 1) : 0.0;
        }
        unset($item);
        return array_values($serie);
    }

    private function distribuicaoPerfis(array $movimentacoes): array
    {
        $contagens = [];
        foreach ($movimentacoes as $movimentacao) {
            $perfil = trim(fixMojibakeText((string) ($movimentacao['usuario_perfil'] ?? '')));
            if ($perfil === '') {
                $perfil = 'Perfil não identificado';
            }
            $contagens[$perfil] = ($contagens[$perfil] ?? 0) + 1;
        }

        uksort($contagens, static function (string $perfilA, string $perfilB) use ($contagens): int {
            return $contagens[$perfilB] <=> $contagens[$perfilA] ?: strcasecmp($perfilA, $perfilB);
        });

        $principais = array_slice($contagens, 0, 5, true);
        $restantes = array_slice($contagens, 5, null, true);
        if ($restantes) {
            $principais['Outros perfis'] = array_sum($restantes);
        }

        $resultado = [];
        foreach ($principais as $perfil => $quantidade) {
            $resultado[] = ['rotulo' => $perfil, 'valor' => (int) $quantidade];
        }
        return $resultado;
    }

    private function rankingMovimentacoes(array $movimentacoes, string $tipo): array
    {
        $ranking = [];
        foreach ($movimentacoes as $movimentacao) {
            if ($tipo === 'usuario') {
                $chave = 'usuario-' . (int) ($movimentacao['usuario_id'] ?? 0);
                $rotulo = (string) ($movimentacao['usuario_nome'] ?? 'Usuário não identificado');
            } elseif (!empty($movimentacao['sala_id'])) {
                $chave = 'sala-' . (int) $movimentacao['sala_id'];
                $rotulo = 'Chave · ' . (string) ($movimentacao['sala_nome'] ?? 'Sala não identificada');
            } elseif (!empty($movimentacao['item_portaria_id'])) {
                $chave = 'item-' . (int) $movimentacao['item_portaria_id'];
                $rotulo = 'Item · ' . (string) ($movimentacao['item_nome'] ?? 'Item não identificado');
            } else {
                $chave = 'outro';
                $rotulo = 'Outro recurso';
            }

            if (!isset($ranking[$chave])) {
                $ranking[$chave] = ['rotulo' => $rotulo, 'valor' => 0];
            }
            $ranking[$chave]['valor']++;
        }

        usort($ranking, static fn (array $a, array $b): int => $b['valor'] <=> $a['valor'] ?: strcasecmp($a['rotulo'], $b['rotulo']));
        $ranking = array_slice($ranking, 0, 5);
        $maximo = max(1, ...array_column($ranking ?: [['valor' => 1]], 'valor'));
        foreach ($ranking as &$item) {
            $item['percentual'] = (int) round(($item['valor'] / $maximo) * 100);
        }
        unset($item);
        return $ranking;
    }
}
