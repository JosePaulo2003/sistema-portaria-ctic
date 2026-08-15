<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

// Permissões específicas para retirada de itens.
class PermissaoItem extends Model
{
    protected string $table = 'permissoes_itens';

    public function withDetails(?int $usuarioId = null): array
    {
        $sql = 'SELECT p.*, u.nome AS usuario_nome, i.nome AS item_nome, a.nome AS autorizador_nome
             FROM permissoes_itens p
             JOIN usuarios u ON u.id = p.usuario_id
             LEFT JOIN itens_portaria i ON i.id = p.item_portaria_id
             JOIN usuarios a ON a.id = p.autorizado_por';
        $params = [];
        if ($usuarioId !== null && $usuarioId > 0) {
            $sql .= ' WHERE p.usuario_id = ?';
            $params[] = $usuarioId;
        }
        $sql .= ' ORDER BY
                p.inicio_autorizacao IS NULL,
                p.inicio_autorizacao DESC,
                p.expira_em IS NULL,
                p.expira_em DESC,
                p.criado_em DESC,
                p.id DESC';

        $stmt = $this->db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
