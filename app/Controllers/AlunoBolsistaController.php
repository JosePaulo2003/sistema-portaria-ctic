<?php
declare(strict_types=1);

/**
 * GUIA DE MANUTENCAO: Este arquivo entrega as telas e ações disponíveis ao bolsista.
 *
 * Ponto de atencao: Controller não é depósito de regra de negócio. Se ele começar a prever o futuro, extraia um Service e devolva a bola de cristal.
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ItemPortaria;
use App\Models\Sala;

// Ãrea do bolsista, com consulta de sala de pesquisa e retiradas autorizadas.
class AlunoBolsistaController extends Controller
{
    public function index(): void { $this->salasHome('Aluno Bolsista'); }
    public function salaPesquisa(): void
    {
        requireProfile('Aluno Bolsista');
        $this->view('aluno-bolsista/sala-pesquisa', [
            'title' => 'Retiradas',
            'user' => currentUser(),
            'salas' => (new Sala())->chavesParaRetirada(currentUser()),
            'itens' => (new ItemPortaria())->disponiveisParaRetirada(),
        ]);
    }
    public function retiradas(): void
    {
        requireProfile('Aluno Bolsista');
        $this->view('aluno-bolsista/retiradas', [
            'title' => 'Retiradas',
            'salas' => (new Sala())->chavesParaRetirada(currentUser()),
            'itens' => (new ItemPortaria())->disponiveisParaRetirada(),
        ]);
    }
}

