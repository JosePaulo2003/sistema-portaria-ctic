<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Movimentacao;
use App\Models\Reserva;
use App\Models\Sala;

// Área administrativa de consulta e acompanhamento geral.
class AdministrativoController extends Controller
{
    public function index(): void { requireProfile('Administrativo'); $this->view('administrativo/index', ['title' => 'Administrativo']); }
    public function reservasSalas(): void { requireProfile('Administrativo'); $this->view('administrativo/reservas-salas', ['title' => 'Reservas', 'reservas' => (new Reserva())->withDetails(), 'salas' => (new Sala())->all('nome')]); }
    public function salvarReservaSala(): void
    {
        requireProfile('Administrativo');
        verifyCsrf();
        $this->validarReservaAdministrativa();
        (new Reserva())->create([
            'usuario_id' => currentUser()['id'],
            'sala_id' => (int) $_POST['sala_id'],
            'titulo' => trim((string) $_POST['titulo']),
            'finalidade' => $_POST['finalidade'] ?? null,
            'tipo_reserva' => 'sala',
            'inicio_em' => $_POST['inicio_em'],
            'fim_em' => $_POST['fim_em'],
            'situacao' => 'pendente',
        ]);
        audit('Reservas', 'criacao', 'Reserva administrativa solicitada.');
        flash('success', 'Reserva solicitada. Aguarde a aprovação da Portaria.');
        redirect('/administrativo/reservas-salas');
    }
    public function retiradas(): void { requireProfile('Administrativo'); $this->view('administrativo/retiradas', ['title' => 'Retiradas', 'movimentacoes' => (new Movimentacao())->abertas()]); }
    public function disponibilidadeSalas(): void { requireProfile('Administrativo'); $this->view('administrativo/disponibilidade-salas', ['title' => 'Disponibilidade', 'salas' => (new Sala())->listDisponibilidade($_GET)]); }

    private function validarReservaAdministrativa(): void
    {
        $inicio = $this->criarDataHora((string) ($_POST['inicio_em'] ?? ''));
        $fim = $this->criarDataHora((string) ($_POST['fim_em'] ?? ''));
        $salaId = (int) ($_POST['sala_id'] ?? 0);

        if (!$inicio || !$fim || $salaId <= 0) {
            flash('error', 'Informe sala, data e horário válidos para solicitar a reserva.');
            redirect('/administrativo/reservas-salas');
        }
        if ($inicio < new \DateTimeImmutable()) {
            flash('error', 'Não é possível reservar sala com data ou horário anterior ao momento atual.');
            redirect('/administrativo/reservas-salas');
        }
        if ($fim <= $inicio) {
            flash('error', 'O fim da reserva precisa ser posterior ao início.');
            redirect('/administrativo/reservas-salas');
        }
        if (!$this->salaDisponivelParaReserva($salaId, $inicio, $fim)) {
            flash('error', 'Esta sala não está disponível no período informado.');
            redirect('/administrativo/reservas-salas');
        }
    }

    private function salaDisponivelParaReserva(int $salaId, \DateTimeImmutable $inicio, \DateTimeImmutable $fim): bool
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT situacao FROM salas WHERE id = ? LIMIT 1');
        $stmt->execute([$salaId]);
        $situacao = $stmt->fetchColumn();
        if (!in_array($situacao, ['disponivel', 'fechada'], true)) {
            return false;
        }

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM movimentacoes WHERE sala_id = ? AND situacao = "aberta"');
        $stmt->execute([$salaId]);
        if ((int) $stmt->fetchColumn() > 0) {
            return false;
        }

        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM reservas
             WHERE sala_id = ? AND situacao IN ("pendente", "confirmada")
               AND inicio_em < ? AND fim_em > ?'
        );
        $stmt->execute([
            $salaId,
            $fim->format('Y-m-d H:i:s'),
            $inicio->format('Y-m-d H:i:s'),
        ]);
        return (int) $stmt->fetchColumn() === 0;
    }

    private function criarDataHora(string $valor): ?\DateTimeImmutable
    {
        $data = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $valor);
        return $data instanceof \DateTimeImmutable ? $data : null;
    }
}
