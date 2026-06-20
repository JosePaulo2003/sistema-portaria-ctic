<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

// Reservas de sala solicitadas por usuários e avaliadas pela equipe responsável.
class Reserva extends Model
{
    protected string $table = 'reservas';

    public function withDetails(): array
    {
        return $this->db()->query(
            'SELECT r.*, u.nome AS usuario_nome, s.nome AS sala_nome
             FROM reservas r
             JOIN usuarios u ON u.id = r.usuario_id
             LEFT JOIN salas s ON s.id = r.sala_id
             ORDER BY r.inicio_em DESC, r.id DESC'
        )->fetchAll();
    }
}
