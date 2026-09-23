<?php
declare(strict_types=1);

/**
 * GUIA DE MANUTENCAO: Este arquivo limita o visitante às operações temporárias explicitamente liberadas.
 *
 * Ponto de atencao: Controller não é depósito de regra de negócio. Se ele começar a prever o futuro, extraia um Service e devolva a bola de cristal.
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Sala;

// Ãrea limitada do visitante para retirada de chave autorizada.
class VisitanteController extends Controller
{
    public function index(): void { $this->salasHome('Visitante'); }
    public function chave(): void { requireProfile('Visitante'); $this->view('visitante/chave', ['title' => 'Chave', 'salas' => (new Sala())->chavesParaRetirada(currentUser())]); }
}

