<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

// Reservas recorrentes/organizacionais de aulas cadastradas pela secretaria.
class ReservaAula extends Model
{
    protected string $table = 'reservas_aula';

    public function withDetails(): array
    {
        return $this->db()->query(
            'SELECT ra.*, prof.nome AS professor_nome, bols.nome AS bolsista_nome, d.nome AS disciplina_nome
             FROM reservas_aula ra
             JOIN usuarios prof ON prof.id = ra.professor_id
             JOIN disciplinas d ON d.id = ra.disciplina_id
             LEFT JOIN usuarios bols ON bols.id = ra.aluno_bolsista_id
             ORDER BY ra.criado_em DESC, ra.id DESC'
        )->fetchAll();
    }
}
