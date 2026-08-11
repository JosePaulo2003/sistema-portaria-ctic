<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ItemPortaria;
use App\Models\Sala;

// ServiÃ§os Gerais pode retirar qualquer chave disponÃ­vel e itens da portaria.
class ServicosGeraisController extends Controller
{
    public function index(): void { $this->salasHome('ServiÃ§os Gerais'); }
    public function retiradas(): void
    {
        requireProfile('ServiÃ§os Gerais');
        $this->view('servicos-gerais/retiradas', [
            'title' => 'Retiradas',
            'salas' => (new Sala())->chavesParaRetirada(currentUser()),
            'itens' => (new ItemPortaria())->disponiveisParaRetirada(),
        ]);
    }
}

