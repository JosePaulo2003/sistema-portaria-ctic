<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

// Bloqueios impedem novas retiradas até o fim do prazo configurado.
class BloqueioChave extends Model
{
    protected string $table = 'bloqueios_chaves';

    public function ativoParaUsuario(int $userId): ?array
    {
        $stmt = $this->db()->prepare(
            'SELECT * FROM bloqueios_chaves
             WHERE usuario_id = ? AND situacao = "ativo" AND fim_em > NOW()
             ORDER BY fim_em DESC LIMIT 1'
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function latestAdvertenciaIdByUser(int $userId): ?int
    {
        $stmt = $this->db()->prepare('SELECT advertencia_id FROM bloqueios_chaves WHERE usuario_id = ? AND advertencia_id IS NOT NULL ORDER BY id DESC LIMIT 1');
        $stmt->execute([$userId]);
        $id = $stmt->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    public function withDetails(): array
    {
        return $this->db()->query(
            'SELECT b.*, u.nome AS usuario_nome, u.email AS usuario_email, a.motivo AS advertencia_motivo
             FROM bloqueios_chaves b
             JOIN usuarios u ON u.id = b.usuario_id
             LEFT JOIN advertencias_chaves a ON a.id = b.advertencia_id
             JOIN (
                 SELECT usuario_id, MAX(id) AS id
                 FROM bloqueios_chaves
                 GROUP BY usuario_id
             ) ult ON ult.id = b.id
             ORDER BY FIELD(b.situacao, "ativo", "encerrado", "cancelado"), b.fim_em DESC'
        )->fetchAll();
    }
}
