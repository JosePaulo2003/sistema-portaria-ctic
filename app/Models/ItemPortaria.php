<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

// Itens emprestáveis pela portaria, com disponibilidade calculada pela quantidade em aberto.
class ItemPortaria extends Model
{
    protected string $table = 'itens_portaria';

    public function disponiveisParaRetirada(): array
    {
        return $this->db()->query(
            'SELECT i.*,
                    GREATEST(i.quantidade - COALESCE(abertas.total, 0), 0) AS quantidade_disponivel
             FROM itens_portaria i
             LEFT JOIN (
                SELECT item_portaria_id, COUNT(*) AS total
                FROM movimentacoes
                WHERE item_portaria_id IS NOT NULL AND situacao = "aberta"
                GROUP BY item_portaria_id
             ) abertas ON abertas.item_portaria_id = i.id
             WHERE i.situacao = "disponivel"
             HAVING quantidade_disponivel > 0
             ORDER BY i.nome'
        )->fetchAll();
    }

    public function podeSerRetirado(int $id): bool
    {
        $stmt = $this->db()->prepare(
            'SELECT i.quantidade - COALESCE(abertas.total, 0) AS disponivel
             FROM itens_portaria i
             LEFT JOIN (
                SELECT item_portaria_id, COUNT(*) AS total
                FROM movimentacoes
                WHERE item_portaria_id = ? AND situacao = "aberta"
                GROUP BY item_portaria_id
             ) abertas ON abertas.item_portaria_id = i.id
             WHERE i.id = ? AND i.situacao = "disponivel"'
        );
        $stmt->execute([$id, $id]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
