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
            'SELECT r.*, COALESCE(NULLIF(r.solicitante_nome_manual, ""), u.nome) AS usuario_nome, u.nome AS usuario_cadastrado_nome, s.nome AS sala_nome
             FROM reservas r
             JOIN usuarios u ON u.id = r.usuario_id
             LEFT JOIN salas s ON s.id = r.sala_id
             ORDER BY
                CASE WHEN r.fim_em >= NOW() THEN 0 ELSE 1 END,
                CASE WHEN r.fim_em >= NOW() THEN r.inicio_em END ASC,
                CASE WHEN r.fim_em < NOW() THEN r.inicio_em END DESC,
                r.id ASC'
        )->fetchAll();
    }

    public function byUserWithDetails(int $userId): array
    {
        $stmt = $this->db()->prepare(
            'SELECT r.*, COALESCE(NULLIF(r.solicitante_nome_manual, ""), u.nome) AS usuario_nome, u.nome AS usuario_cadastrado_nome, s.nome AS sala_nome
             FROM reservas r
             JOIN usuarios u ON u.id = r.usuario_id
             LEFT JOIN salas s ON s.id = r.sala_id
             WHERE r.usuario_id = ?
             ORDER BY
                CASE WHEN r.fim_em >= NOW() THEN 0 ELSE 1 END,
                CASE WHEN r.fim_em >= NOW() THEN r.inicio_em END ASC,
                CASE WHEN r.fim_em < NOW() THEN r.inicio_em END DESC,
                r.id ASC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function belongsToUser(int $reservaId, int $userId): bool
    {
        $stmt = $this->db()->prepare('SELECT COUNT(*) FROM reservas WHERE id = ? AND usuario_id = ?');
        $stmt->execute([$reservaId, $userId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function hasConflict(int $salaId, string $inicio, string $fim, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM reservas
                WHERE sala_id = ?
                  AND situacao IN ("pendente", "confirmada")
                  AND inicio_em < ? AND fim_em > ?';
        $params = [$salaId, $fim, $inicio];
        if ($ignoreId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $ignoreId;
        }
        $stmt = $this->db()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function pendentes(): array
    {
        return $this->db()->query(
            'SELECT r.*, COALESCE(NULLIF(r.solicitante_nome_manual, ""), u.nome) AS usuario_nome, u.nome AS usuario_cadastrado_nome, s.nome AS sala_nome
             FROM reservas r
             JOIN usuarios u ON u.id = r.usuario_id
             LEFT JOIN salas s ON s.id = r.sala_id
             WHERE r.situacao = "pendente"
             ORDER BY r.inicio_em ASC, r.id ASC'
        )->fetchAll();
    }

    public function podeAprovar(array $reserva): bool
    {
        $stmt = $this->db()->prepare('SELECT situacao FROM salas WHERE id = ? LIMIT 1');
        $stmt->execute([(int) $reserva['sala_id']]);
        if ($stmt->fetchColumn() !== 'disponivel') {
            return false;
        }

        $stmt = $this->db()->prepare('SELECT COUNT(*) FROM movimentacoes WHERE sala_id = ? AND situacao = "aberta"');
        $stmt->execute([(int) $reserva['sala_id']]);
        if ((int) $stmt->fetchColumn() > 0) {
            return false;
        }

        $stmt = $this->db()->prepare(
            'SELECT COUNT(*) FROM reservas
             WHERE id <> ? AND sala_id = ? AND situacao = "confirmada"
               AND inicio_em < ? AND fim_em > ?'
        );
        $stmt->execute([
            (int) $reserva['id'],
            (int) $reserva['sala_id'],
            $reserva['fim_em'],
            $reserva['inicio_em'],
        ]);
        return (int) $stmt->fetchColumn() === 0;
    }
}
