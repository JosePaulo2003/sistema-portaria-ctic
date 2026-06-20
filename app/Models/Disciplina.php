<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

// Disciplinas vinculadas a cursos e professores.
class Disciplina extends Model
{
    protected string $table = 'disciplinas';

    public function withDetails(): array
    {
        return $this->db()->query(
            'SELECT d.*, c.nome AS curso_nome, u.nome AS professor_nome
             FROM disciplinas d
             JOIN cursos c ON c.id = d.curso_id
             LEFT JOIN usuarios u ON u.id = d.professor_id
             ORDER BY d.nome'
        )->fetchAll();
    }
}
