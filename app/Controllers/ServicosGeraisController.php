<?php
declare(strict_types=1);

/**
 * GUIA DE MANUTENCAO: Este arquivo mantém o fluxo próprio de retirada usado por Serviços Gerais.
 *
 * Ponto de atencao: Controller não é depósito de regra de negócio. Se ele começar a prever o futuro, extraia um Service e devolva a bola de cristal.
 */

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

