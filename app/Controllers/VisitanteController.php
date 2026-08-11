<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Sala;

// Ãrea limitada do visitante para retirada de chave autorizada.
class VisitanteController extends Controller
{
    public function index(): void { $this->salasHome('Visitante'); }
    public function chave(): void { requireProfile('Visitante'); $this->view('visitante/chave', ['title' => 'Chave', 'salas' => (new Sala())->chavesParaRetirada(currentUser())]); }
}

