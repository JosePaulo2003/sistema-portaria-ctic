<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Sala;

// Detalhe de sala com reservas, aulas e movimentações recentes.
class SalaController extends Controller
{
    public function detalhes(): void
    {
        requireAuth();
        $model = new Sala();
        $sala = $model->detalhes((int) ($_GET['id'] ?? 0));
        if (!$sala) {
            http_response_code(404);
            echo 'Sala não encontrada.';
            return;
        }
        $status = null;
        foreach ($model->listDisponibilidade() as $salaComStatus) {
            if ((int) $salaComStatus['id'] === (int) $sala['id']) {
                $status = $salaComStatus;
                break;
            }
        }
        $this->view('salas/detalhes', [
            'title' => $sala['nome'],
            'sala' => $sala,
            'status' => $status,
            'reservas' => $model->reservasDaSala((int) $sala['id']),
            'aulas' => $model->aulasDaSala($sala['nome']),
            'movimentacoes' => $model->movimentacoesDaSala((int) $sala['id']),
        ]);
    }
}
