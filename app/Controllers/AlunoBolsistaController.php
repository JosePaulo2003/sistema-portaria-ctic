<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ItemPortaria;
use App\Models\Sala;

// Área do bolsista, com consulta de sala de pesquisa e retiradas autorizadas.
class AlunoBolsistaController extends Controller
{
    public function index(): void { requireProfile('Aluno Bolsista'); $this->view('aluno-bolsista/index', ['title' => 'Bolsista']); }
    public function salaPesquisa(): void { requireProfile('Aluno Bolsista'); $this->view('aluno-bolsista/sala-pesquisa', ['title' => 'Sala de Pesquisa', 'user' => currentUser()]); }
    public function retiradas(): void
    {
        requireProfile('Aluno Bolsista');
        $this->view('aluno-bolsista/retiradas', [
            'title' => 'Retiradas',
            'salas' => (new Sala())->chavesDisponiveisParaRetirada(currentUser()),
            'itens' => (new ItemPortaria())->disponiveisParaRetirada(),
        ]);
    }
}
