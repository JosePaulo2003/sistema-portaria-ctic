<?php
declare(strict_types=1);

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

