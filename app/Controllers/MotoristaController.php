<?php
declare(strict_types=1);

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
