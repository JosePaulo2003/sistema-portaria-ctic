<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Movimentacao;
use App\Models\Reserva;
use App\Models\Sala;

// Área administrativa de consulta e acompanhamento geral.
class AdministrativoController extends Controller
{
    public function index(): void { requireProfile('Administrativo'); $this->view('administrativo/index', ['title' => 'Administrativo']); }
    public function reservasSalas(): void { requireProfile('Administrativo'); $this->view('administrativo/reservas-salas', ['title' => 'Reservas', 'reservas' => (new Reserva())->withDetails()]); }
    public function emprestimosPortaria(): void { requireProfile('Administrativo'); $this->view('administrativo/emprestimos-portaria', ['title' => 'Empréstimos', 'movimentacoes' => (new Movimentacao())->abertas()]); }
    public function retiradas(): void { requireProfile('Administrativo'); $this->view('administrativo/retiradas', ['title' => 'Retiradas', 'movimentacoes' => (new Movimentacao())->abertas()]); }
    public function historico(): void { requireProfile('Administrativo'); $this->view('administrativo/historico', ['title' => 'Histórico', 'movimentacoes' => (new Movimentacao())->historico()]); }
    public function disponibilidadeSalas(): void { requireProfile('Administrativo'); $this->view('administrativo/disponibilidade-salas', ['title' => 'Disponibilidade', 'salas' => (new Sala())->listDisponibilidade($_GET)]); }
}
