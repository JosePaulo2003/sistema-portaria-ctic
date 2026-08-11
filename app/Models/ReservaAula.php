<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

// Reservas recorrentes/organizacionais de aulas vinculadas aos cursos.
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

    public function withDetailsByCourse(int $cursoId): array
    {
        $stmt = $this->db()->prepare(
            'SELECT ra.*, prof.nome AS professor_nome, bols.nome AS bolsista_nome, d.nome AS disciplina_nome, c.nome AS curso_nome
             FROM reservas_aula ra
             JOIN usuarios prof ON prof.id = ra.professor_id
             JOIN disciplinas d ON d.id = ra.disciplina_id
             JOIN cursos c ON c.id = d.curso_id
             LEFT JOIN usuarios bols ON bols.id = ra.aluno_bolsista_id
             WHERE d.curso_id = ?
             ORDER BY ra.dia_semana, ra.horario_inicio, ra.id DESC'
        );
        $stmt->execute([$cursoId]);
        return $stmt->fetchAll();
    }

    public function belongsToCourse(int $reservaId, int $cursoId): bool
    {
        $stmt = $this->db()->prepare(
            'SELECT COUNT(*)
             FROM reservas_aula ra
             JOIN disciplinas d ON d.id = ra.disciplina_id
             WHERE ra.id = ? AND d.curso_id = ?'
        );
        $stmt->execute([$reservaId, $cursoId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function hasScheduleConflict(string $salaNome, string $diaSemana, string $inicio, string $fim, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT COUNT(*)
                FROM reservas_aula
                WHERE sala_nome = ?
                  AND dia_semana = ?
                  AND situacao = "ativa"
                  AND horario_inicio < ?
                  AND horario_fim > ?';
        $params = [$salaNome, $diaSemana, $fim, $inicio];

        if ($ignoreId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $ignoreId;
        }

        $stmt = $this->db()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }
}
