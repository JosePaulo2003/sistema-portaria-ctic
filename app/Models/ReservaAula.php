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

    public function detalhesCalendario(int $id): ?array
    {
        $stmt = $this->db()->prepare(
            'SELECT ra.*,
                    prof.nome AS professor_nome,
                    prof.email AS professor_email,
                    bols.nome AS bolsista_nome,
                    cadastrador.nome AS cadastrado_por_nome,
                    d.nome AS disciplina_nome,
                    c.nome AS curso_nome,
                    s.id AS sala_id,
                    s.codigo AS sala_codigo,
                    s.bloco AS sala_bloco,
                    s.tipo_ambiente AS sala_tipo
             FROM reservas_aula ra
             JOIN usuarios prof ON prof.id = ra.professor_id
             JOIN usuarios cadastrador ON cadastrador.id = ra.usuario_id
             LEFT JOIN disciplinas d ON d.id = ra.disciplina_id
             LEFT JOIN cursos c ON c.id = d.curso_id
             LEFT JOIN usuarios bols ON bols.id = ra.aluno_bolsista_id
             LEFT JOIN (SELECT MIN(id) AS id, nome FROM salas GROUP BY nome) sala_ref ON sala_ref.nome = ra.sala_nome
             LEFT JOIN salas s ON s.id = sala_ref.id
             WHERE ra.id = ?
             LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
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
