<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Sala;

// Área limitada do visitante para retirada de chave autorizada.
class VisitanteController extends Controller
{
    public function index(): void { requireProfile('Visitante'); $this->view('visitante/index', ['title' => 'Visitante']); }
    public function chave(): void { requireProfile('Visitante'); $this->view('visitante/chave', ['title' => 'Chave', 'salas' => (new Sala())->chavesDisponiveisParaRetirada(currentUser())]); }
}
