<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Movimentacao;
use App\Models\Reserva;
use App\Models\Sala;

// Painel de acompanhamento da direção: chaves, reservas e fluxo da portaria.
class DiretorController extends Controller
{
    public function index(): void
    {
        requireProfile('Diretor');

        $movimentacao = new Movimentacao();
        $abertas = $movimentacao->abertas();

        $this->view('diretor/index', [
            'title' => 'Direção',
            'abertas' => $abertas,
            'reservas' => (new Reserva())->withDetails(),
            'movimentacoes' => $movimentacao->historico(20),
            'chavesAbertas' => count(array_filter($abertas, fn (array $m): bool => !empty($m['sala_id']))),
            'itensAbertos' => count(array_filter($abertas, fn (array $m): bool => !empty($m['item_portaria_id']))),
        ]);
    }

    public function chaves(): void
    {
        requireProfile('Diretor');

        $this->view('diretor/chaves', [
            'title' => 'Chaves',
            'salas' => (new Sala())->chavesDisponiveisParaRetirada(currentUser()),
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

    public function movimentacoes(): void
    {
        requireProfile('Diretor');

        $this->view('diretor/movimentacoes', [
            'title' => 'Movimentações',
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
