<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

// Advertências alimentam o contador de bloqueio temporário para retirada de chaves.
class AdvertenciaChave extends Model
{
    protected string $table = 'advertencias_chaves';

    public function withDetails(): array
    {
        return $this->db()->query(
            'SELECT a.*, u.nome AS usuario_nome, ag.nome AS agente_nome
             FROM advertencias_chaves a
             JOIN usuarios u ON u.id = a.usuario_id
             JOIN usuarios ag ON ag.id = a.agente_portaria_id
             ORDER BY a.criado_em DESC, a.id DESC'
        )->fetchAll();
    }

    public function latestIdByUser(int $userId): ?int
    {
        $stmt = $this->db()->prepare('SELECT id FROM advertencias_chaves WHERE usuario_id = ? ORDER BY id DESC LIMIT 1');
        $stmt->execute([$userId]);
        $id = $stmt->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    public function shouldCreateBlock(int $userId, ?int $lastBlockedAdvertenciaId): bool
    {
        if ($lastBlockedAdvertenciaId === null) {
            $stmt = $this->db()->prepare('SELECT COUNT(*) FROM advertencias_chaves WHERE usuario_id = ?');
            $stmt->execute([$userId]);
            return (int) $stmt->fetchColumn() >= 3;
        }

        $stmt = $this->db()->prepare('SELECT COUNT(*) FROM advertencias_chaves WHERE usuario_id = ? AND id > ?');
        $stmt->execute([$userId, $lastBlockedAdvertenciaId]);
        return (int) $stmt->fetchColumn() >= 1;
    }
}
