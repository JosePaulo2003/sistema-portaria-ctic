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

    public function historicoFiltrado(array $filters = [], int $limit = 200): array
    {
        $where = [];
        $params = [];

        $busca = trim((string) ($filters['busca'] ?? ''));
        if ($busca !== '') {
            $where[] = '(u.nome LIKE ? OR s.nome LIKE ? OR i.nome LIKE ? OR m.observacao LIKE ? OR m.tipo_movimentacao LIKE ?)';
            $like = '%' . $busca . '%';
            array_push($params, $like, $like, $like, $like, $like);
        }

        $tipo = trim((string) ($filters['tipo_movimentacao'] ?? ''));
        if ($tipo !== '') {
            $where[] = 'm.tipo_movimentacao = ?';
            $params[] = $tipo;
        }

        $situacao = trim((string) ($filters['situacao'] ?? ''));
        if ($situacao !== '') {
            $where[] = 'm.situacao = ?';
            $params[] = $situacao;
        }

        $inicio = trim((string) ($filters['data_inicio'] ?? ''));
        if ($inicio !== '') {
            $where[] = 'DATE(COALESCE(m.devolucao_real_em, m.retirada_em, m.criado_em)) >= ?';
            $params[] = $inicio;
        }

        $fim = trim((string) ($filters['data_fim'] ?? ''));
        if ($fim !== '') {
            $where[] = 'DATE(COALESCE(m.devolucao_real_em, m.retirada_em, m.criado_em)) <= ?';
            $params[] = $fim;
        }

        $sql = 'SELECT m.*, u.nome AS usuario_nome, u.foto_perfil_url, s.nome AS sala_nome, i.nome AS item_nome
                FROM movimentacoes m
                JOIN usuarios u ON u.id = m.usuario_id
                LEFT JOIN salas s ON s.id = m.sala_id
                LEFT JOIN itens_portaria i ON i.id = m.item_portaria_id';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY COALESCE(m.devolucao_real_em, m.retirada_em, m.criado_em) DESC, m.id DESC LIMIT ?';

        $stmt = $this->db()->prepare($sql);
        foreach ($params as $index => $value) {
            $stmt->bindValue($index + 1, $value);
        }
        $stmt->bindValue(count($params) + 1, $limit, \PDO::PARAM_INT);
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
