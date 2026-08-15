<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Movimentacao;
use App\Models\Reserva;
use App\Models\ReservaAula;
use App\Models\Sala;

// Detalhe de sala com reservas, aulas e movimentações recentes.
class SalaController extends Controller
{
    public function calendario(): void
    {
        requireAuth();

        $inicioMes = $this->inicioMesCalendario((string) ($_GET['mes'] ?? ''));
        $fimMes = $inicioMes->modify('+1 month');
        $salaModel = new Sala();
        $salas = $salaModel->all('nome');
        $salaFiltro = max(0, (int) ($_GET['sala_id'] ?? 0));

        if ($salaFiltro > 0 && !array_filter($salas, static fn (array $sala): bool => (int) $sala['id'] === $salaFiltro)) {
            $salaFiltro = 0;
        }

        $reservaModel = new Reserva();
        $reservas = $reservaModel->paraCalendario(
            $inicioMes->format('Y-m-d H:i:s'),
            $fimMes->format('Y-m-d H:i:s')
        );
        $aulas = $reservaModel->aulasRecorrentesParaCalendario();
        $movimentacoes = (new Movimentacao())->paraCalendario(
            $inicioMes->format('Y-m-d H:i:s'),
            $fimMes->format('Y-m-d H:i:s')
        );
        $eventosPorData = $this->eventosCalendario($inicioMes, $fimMes, $reservas, $aulas, $movimentacoes);

        $this->view('salas/calendario', [
            'title' => 'Calendário de Salas',
            'inicioMes' => $inicioMes,
            'mesAnterior' => $inicioMes->modify('-1 month')->format('Y-m'),
            'mesSeguinte' => $inicioMes->modify('+1 month')->format('Y-m'),
            'mesAtual' => (new \DateTimeImmutable('first day of this month'))->format('Y-m'),
            'rotuloMes' => $this->rotuloMes($inicioMes),
            'semanas' => $this->semanasCalendario($inicioMes, $fimMes, $eventosPorData),
            'salas' => $salas,
            'salaFiltro' => $salaFiltro,
            'totalReservas' => count($reservas),
            'totalAulasMes' => count(array_filter(
                array_merge(...array_values($eventosPorData ?: [[]])),
                static fn (array $evento): bool => ($evento['tipo'] ?? '') === 'aula'
            )),
            'totalRetiradas' => count($movimentacoes),
        ]);
    }

    public function detalhes(): void
    {
        requireAuth();
        $model = new Sala();
        $sala = $model->detalhes((int) ($_GET['id'] ?? 0));
        if (!$sala) {
            http_response_code(404);
            echo 'Sala não encontrada.';
            return;
        }
        $status = null;
        foreach ($model->listDisponibilidade() as $salaComStatus) {
            if ((int) $salaComStatus['id'] === (int) $sala['id']) {
                $status = $salaComStatus;
                break;
            }
        }
        $inicioMes = $this->inicioMesCalendario((string) ($_GET['mes'] ?? ''));
        $fimMes = $inicioMes->modify('+1 month');
        $reservaModel = new Reserva();
        $reservas = $reservaModel->paraCalendario(
            $inicioMes->format('Y-m-d H:i:s'),
            $fimMes->format('Y-m-d H:i:s'),
            (int) $sala['id']
        );
        $aulas = $reservaModel->aulasRecorrentesParaCalendario((string) $sala['nome']);
        $movimentacoes = (new Movimentacao())->paraCalendario(
            $inicioMes->format('Y-m-d H:i:s'),
            $fimMes->format('Y-m-d H:i:s'),
            (int) $sala['id']
        );
        $eventosPorData = $this->eventosCalendario($inicioMes, $fimMes, $reservas, $aulas, $movimentacoes);

        $this->view('salas/detalhes', [
            'title' => $sala['nome'],
            'sala' => $sala,
            'status' => $status,
            'inicioMes' => $inicioMes,
            'mesAnterior' => $inicioMes->modify('-1 month')->format('Y-m'),
            'mesSeguinte' => $inicioMes->modify('+1 month')->format('Y-m'),
            'mesAtual' => (new \DateTimeImmutable('first day of this month'))->format('Y-m'),
            'rotuloMes' => $this->rotuloMes($inicioMes),
            'semanas' => $this->semanasCalendario($inicioMes, $fimMes, $eventosPorData),
            'totalReservas' => count($reservas),
            'totalAulasMes' => count(array_filter(
                array_merge(...array_values($eventosPorData ?: [[]])),
                static fn (array $evento): bool => ($evento['tipo'] ?? '') === 'aula'
            )),
            'totalRetiradas' => count($movimentacoes),
        ]);
    }

    public function atividade(): void
    {
        requireAuth();

        $tipo = trim((string) ($_GET['tipo'] ?? ''));
        $id = max(0, (int) ($_GET['id'] ?? 0));
        $atividade = match ($tipo) {
            'reserva' => (new Reserva())->detalhesCalendario($id),
            'aula' => (new ReservaAula())->detalhesCalendario($id),
            'retirada' => (new Movimentacao())->detalhesCalendario($id),
            default => null,
        };

        if (!$atividade) {
            http_response_code(404);
            echo 'Atividade não encontrada.';
            return;
        }

        $mes = trim((string) ($_GET['mes'] ?? ''));
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $mes)) {
            $referencia = match ($tipo) {
                'reserva' => (string) ($atividade['inicio_em'] ?? ''),
                'retirada' => (string) ($atividade['retirada_em'] ?? ''),
                default => date('Y-m-d'),
            };
            $mes = preg_match('/^\d{4}-\d{2}/', $referencia) ? substr($referencia, 0, 7) : date('Y-m');
        }

        $dataSelecionada = trim((string) ($_GET['data'] ?? ''));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataSelecionada)) {
            $dataSelecionada = '';
        }

        $titulo = match ($tipo) {
            'reserva' => 'Detalhes da reserva',
            'aula' => 'Detalhes da aula',
            default => 'Detalhes da retirada de chave',
        };

        $this->view('salas/atividade', [
            'title' => $titulo,
            'tituloAtividade' => $titulo,
            'tipoAtividade' => $tipo,
            'atividade' => $atividade,
            'salaId' => (int) ($atividade['sala_id'] ?? 0),
            'mes' => $mes,
            'dataSelecionada' => $dataSelecionada,
        ]);
    }

    private function inicioMesCalendario(string $valor): \DateTimeImmutable
    {
        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $valor)) {
            $data = \DateTimeImmutable::createFromFormat('!Y-m', $valor);
            if ($data instanceof \DateTimeImmutable) {
                return $data;
            }
        }

        return new \DateTimeImmutable('first day of this month midnight');
    }

    private function rotuloMes(\DateTimeImmutable $inicioMes): string
    {
        $meses = [
            1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
            5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
            9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
        ];

        return $meses[(int) $inicioMes->format('n')] . ' de ' . $inicioMes->format('Y');
    }

    private function eventosCalendario(
        \DateTimeImmutable $inicioMes,
        \DateTimeImmutable $fimMes,
        array $reservas,
        array $aulas,
        array $movimentacoes = []
    ): array {
        $eventos = [];
        $ultimoInstanteMes = $fimMes->modify('-1 second');

        foreach ($reservas as $reserva) {
            try {
                $inicio = new \DateTimeImmutable((string) $reserva['inicio_em']);
                $fim = new \DateTimeImmutable((string) $reserva['fim_em']);
            } catch (\Throwable) {
                continue;
            }
            if ($fim <= $inicio) {
                continue;
            }

            $primeiroDia = $inicio > $inicioMes ? $inicio->setTime(0, 0) : $inicioMes;
            $ultimoInstante = $fim->modify('-1 second');
            $ultimoDia = ($ultimoInstante < $ultimoInstanteMes ? $ultimoInstante : $ultimoInstanteMes)->setTime(0, 0);

            for ($dia = $primeiroDia; $dia <= $ultimoDia; $dia = $dia->modify('+1 day')) {
                $mesmoDia = $inicio->format('Y-m-d') === $fim->format('Y-m-d');
                if ($mesmoDia) {
                    $horario = $inicio->format('H:i') . '–' . $fim->format('H:i');
                } elseif ($dia->format('Y-m-d') === $inicio->format('Y-m-d')) {
                    $horario = 'A partir de ' . $inicio->format('H:i');
                } elseif ($dia->format('Y-m-d') === $ultimoInstante->format('Y-m-d')) {
                    $horario = 'Até ' . $fim->format('H:i');
                } else {
                    $horario = 'Continuação';
                }

                $eventos[$dia->format('Y-m-d')][] = [
                    'tipo' => 'reserva',
                    'atividade_tipo' => 'reserva',
                    'atividade_id' => (int) ($reserva['id'] ?? 0),
                    'situacao' => (string) ($reserva['situacao'] ?? 'pendente'),
                    'sala_id' => (int) ($reserva['sala_id'] ?? 0),
                    'sala_nome' => (string) ($reserva['sala_nome'] ?? 'Sala'),
                    'sala_codigo' => (string) ($reserva['sala_codigo'] ?? ''),
                    'titulo' => (string) ($reserva['titulo'] ?? 'Reserva de sala'),
                    'horario' => $horario,
                    'ordem' => $dia->format('Y-m-d') === $inicio->format('Y-m-d') ? $inicio->format('H:i:s') : '00:00:00',
                ];
            }
        }

        $diasSemana = [
            1 => 'segunda', 2 => 'terca', 3 => 'quarta', 4 => 'quinta',
            5 => 'sexta', 6 => 'sabado', 7 => 'domingo',
        ];
        $aulasPorDia = [];
        foreach ($aulas as $aula) {
            $diaNormalizado = comparableProfile((string) ($aula['dia_semana'] ?? ''));
            if ($diaNormalizado !== '') {
                $aulasPorDia[$diaNormalizado][] = $aula;
            }
        }

        for ($dia = $inicioMes; $dia < $fimMes; $dia = $dia->modify('+1 day')) {
            foreach ($aulasPorDia[$diasSemana[(int) $dia->format('N')]] ?? [] as $aula) {
                $inicio = substr((string) ($aula['horario_inicio'] ?? ''), 0, 5);
                $fim = substr((string) ($aula['horario_fim'] ?? ''), 0, 5);
                $eventos[$dia->format('Y-m-d')][] = [
                    'tipo' => 'aula',
                    'atividade_tipo' => 'aula',
                    'atividade_id' => (int) ($aula['id'] ?? 0),
                    'situacao' => 'aula',
                    'sala_id' => (int) ($aula['sala_id'] ?? 0),
                    'sala_nome' => (string) ($aula['sala_nome'] ?? 'Sala'),
                    'sala_codigo' => (string) ($aula['sala_codigo'] ?? ''),
                    'titulo' => (string) ($aula['disciplina'] ?? 'Aula'),
                    'horario' => trim($inicio . ($fim !== '' ? '–' . $fim : '')),
                    'ordem' => ($inicio !== '' ? $inicio : '00:00') . ':00',
                ];
            }
        }

        foreach ($movimentacoes as $movimentacao) {
            try {
                $retirada = new \DateTimeImmutable((string) ($movimentacao['retirada_em'] ?? ''));
            } catch (\Throwable) {
                continue;
            }
            if ($retirada < $inicioMes || $retirada >= $fimMes) {
                continue;
            }

            $horario = $retirada->format('H:i');
            $devolucaoTexto = 'em aberto';
            if (!empty($movimentacao['devolucao_real_em'])) {
                try {
                    $devolucao = new \DateTimeImmutable((string) $movimentacao['devolucao_real_em']);
                    $devolucaoTexto = $devolucao->format('Y-m-d') === $retirada->format('Y-m-d')
                        ? 'devolvida ' . $devolucao->format('H:i')
                        : 'devolvida ' . $devolucao->format('d/m H:i');
                } catch (\Throwable) {
                    $devolucaoTexto = 'devolvida';
                }
            }

            $eventos[$retirada->format('Y-m-d')][] = [
                'tipo' => 'retirada',
                'atividade_tipo' => 'retirada',
                'atividade_id' => (int) ($movimentacao['id'] ?? 0),
                'situacao' => 'retirada',
                'sala_id' => (int) ($movimentacao['sala_id'] ?? 0),
                'sala_nome' => (string) ($movimentacao['sala_nome'] ?? 'Sala'),
                'sala_codigo' => (string) ($movimentacao['sala_codigo'] ?? ''),
                'titulo' => 'Chave retirada por ' . (string) ($movimentacao['usuario_nome'] ?? 'Usuário'),
                'horario' => $horario . ' · ' . $devolucaoTexto,
                'ordem' => $retirada->format('H:i:s'),
            ];
        }

        foreach ($eventos as &$eventosDia) {
            usort($eventosDia, static fn (array $a, array $b): int =>
                strcmp((string) $a['ordem'], (string) $b['ordem'])
                ?: strcasecmp((string) $a['sala_nome'], (string) $b['sala_nome'])
            );
        }
        unset($eventosDia);

        return $eventos;
    }

    private function semanasCalendario(
        \DateTimeImmutable $inicioMes,
        \DateTimeImmutable $fimMes,
        array $eventosPorData
    ): array {
        $inicioGrade = $inicioMes->modify('-' . ((int) $inicioMes->format('N') - 1) . ' days');
        $ultimoDiaMes = $fimMes->modify('-1 day');
        $fimGrade = $ultimoDiaMes->modify('+' . (7 - (int) $ultimoDiaMes->format('N')) . ' days');
        $nomesDias = [1 => 'Segunda', 2 => 'Terça', 3 => 'Quarta', 4 => 'Quinta', 5 => 'Sexta', 6 => 'Sábado', 7 => 'Domingo'];
        $semanas = [];
        $semana = [];

        for ($dia = $inicioGrade; $dia <= $fimGrade; $dia = $dia->modify('+1 day')) {
            $chave = $dia->format('Y-m-d');
            $semana[] = [
                'data' => $chave,
                'dia' => $dia->format('j'),
                'dia_semana' => $nomesDias[(int) $dia->format('N')],
                'dentro_mes' => $dia >= $inicioMes && $dia < $fimMes,
                'hoje' => $chave === date('Y-m-d'),
                'eventos' => $eventosPorData[$chave] ?? [],
            ];

            if (count($semana) === 7) {
                $semanas[] = $semana;
                $semana = [];
            }
        }

        return $semanas;
    }
}
