<?php
declare(strict_types=1);

/**
 * GUIA DE MANUTENCAO: Este arquivo oferece ao Motorista somente as operações previstas para seu perfil.
 *
 * Ponto de atencao: Controller não é depósito de regra de negócio. Se ele começar a prever o futuro, extraia um Service e devolva a bola de cristal.
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Sala;

// Area restrita: consulta de salas e retirada das chaves autorizadas ao usuario.
class MotoristaController extends Controller
{
    public function index(): void
    {
        $this->salasHome('Motorista');
    }

    public function retiradas(): void
    {
        requireProfile('Motorista');
        $this->view('motorista/retiradas', [
            'title' => 'Retirada de Chave',
            'salas' => (new Sala())->chavesParaRetirada(currentUser()),
        ]);
    }
}
