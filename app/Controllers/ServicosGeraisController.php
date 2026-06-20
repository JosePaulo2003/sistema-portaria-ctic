<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ItemPortaria;
use App\Models\Sala;

// Serviços Gerais pode retirar qualquer chave disponível e itens da portaria.
class ServicosGeraisController extends Controller
{
    public function index(): void { requireProfile('Serviços Gerais'); $this->view('servicos-gerais/index', ['title' => 'Serviços Gerais']); }
    public function retiradas(): void
    {
        requireProfile('Serviços Gerais');
        $this->view('servicos-gerais/retiradas', [
            'title' => 'Retiradas',
            'salas' => (new Sala())->chavesDisponiveisParaRetirada(currentUser()),
            'itens' => (new ItemPortaria())->disponiveisParaRetirada(),
        ]);
    }
}
