<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

// Permissões específicas para retirada de itens.
class PermissaoItem extends Model
{
    protected string $table = 'permissoes_itens';

    public function withDetails(): array
    {
        return $this->db()->query(
            'SELECT p.*, u.nome AS usuario_nome, i.nome AS item_nome, a.nome AS autorizador_nome
             FROM permissoes_itens p
             JOIN usuarios u ON u.id = p.usuario_id
             LEFT JOIN itens_portaria i ON i.id = p.item_portaria_id
             JOIN usuarios a ON a.id = p.autorizado_por
             ORDER BY p.criado_em DESC, p.id DESC'
        )->fetchAll();
    }
}
