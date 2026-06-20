<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

// Movimentações representam retirada/devolução de chaves e itens.
class Movimentacao extends Model
{
    protected string $table = 'movimentacoes';

    public function abertas(): array
    {
        return $this->db()->query(
            'SELECT m.*, u.nome AS usuario_nome, u.foto_perfil_url, s.nome AS sala_nome, i.nome AS item_nome
             FROM movimentacoes m
             JOIN usuarios u ON u.id = m.usuario_id
             LEFT JOIN salas s ON s.id = m.sala_id
             LEFT JOIN itens_portaria i ON i.id = m.item_portaria_id
             WHERE m.situacao = "aberta"
             ORDER BY m.retirada_em DESC, m.id DESC'
        )->fetchAll();
    }

    public function historico(int $limit = 100): array
    {
        $stmt = $this->db()->prepare(
            'SELECT m.*, u.nome AS usuario_nome, u.foto_perfil_url, s.nome AS sala_nome, i.nome AS item_nome
             FROM movimentacoes m
             JOIN usuarios u ON u.id = m.usuario_id
             LEFT JOIN salas s ON s.id = m.sala_id
             LEFT JOIN itens_portaria i ON i.id = m.item_portaria_id
             ORDER BY COALESCE(m.devolucao_real_em, m.retirada_em, m.criado_em) DESC, m.id DESC
             LIMIT ?'
        );
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function abertasPorSala(int $salaId): array
    {
        $stmt = $this->db()->prepare('SELECT * FROM movimentacoes WHERE sala_id = ? AND situacao = "aberta" ORDER BY retirada_em DESC');
        $stmt->execute([$salaId]);
        return $stmt->fetchAll();
    }

    public function abertasPorItem(int $itemId): array
    {
        $stmt = $this->db()->prepare('SELECT * FROM movimentacoes WHERE item_portaria_id = ? AND situacao = "aberta" ORDER BY retirada_em DESC');
        $stmt->execute([$itemId]);
        return $stmt->fetchAll();
    }
}
