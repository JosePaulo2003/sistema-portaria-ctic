<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Movimentacao;
use App\Models\Reserva;
use App\Models\Sala;

// Painel de acompanhamento da direÃ§Ã£o: chaves, reservas e fluxo da portaria.
class DiretorController extends Controller
{
    public function index(): void
    {
        $this->salasHome('Diretor');
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
            flash('error', 'Status de reserva invalido.');
            redirect('/diretor/reservas');
        }

        $reservaModel = new Reserva();
        $reserva = $reservaModel->find((int) ($_POST['id'] ?? 0));
        if (!$reserva) {
            flash('error', 'Reserva nao encontrada.');
            redirect('/diretor/reservas');
        }
        if ($status === 'confirmada' && !$reservaModel->podeAprovar($reserva)) {
            flash('error', 'Nao foi possivel confirmar: existe conflito ou a sala nao esta disponivel.');
            redirect('/diretor/reservas');
        }

        $reservaModel->update((int) $reserva['id'], ['situacao' => $status]);
        audit('Direcao', 'atualizacao_status_reserva', 'Status de reserva atualizado pela direcao.', [
            'reserva_id' => $reserva['id'],
            'situacao' => $status,
        ]);
        flash('success', 'Status da reserva atualizado.');
        redirect('/diretor/reservas');
    }

    public function movimentacoes(): void
    {
        requireProfile('Diretor');

        $this->view('diretor/movimentacoes', [
            'title' => 'MovimentaÃ§Ãµes',
            'movimentacoes' => (new Movimentacao())->historico(200),
        ]);
    }

    public function disponibilidade(): void
    {
        requireProfile('Diretor');

        $this->view('diretor/disponibilidade', [
            'title' => 'Disponibilidade',
            'salas' => (new Sala())->listDisponibilidade($_GET),
        ]);
    }
}

