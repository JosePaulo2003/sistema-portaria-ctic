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

    public function relatorioDirecao(string $inicio, string $fim, array $filtros = []): array
    {
        $where = [
            'COALESCE(m.retirada_em, m.criado_em) >= ?',
            'COALESCE(m.retirada_em, m.criado_em) < ?',
        ];
        $params = [$inicio, $fim];

        $busca = trim((string) ($filtros['busca'] ?? ''));
        if ($busca !== '') {
            $where[] = '(u.nome LIKE ? OR s.nome LIKE ? OR s.codigo LIKE ? OR i.nome LIKE ? OR i.codigo LIKE ? OR m.observacao LIKE ? OR registrador.nome LIKE ?)';
            $like = '%' . $busca . '%';
            array_push($params, $like, $like, $like, $like, $like, $like, $like);
        }

        $situacao = trim((string) ($filtros['situacao'] ?? ''));
        if (in_array($situacao, ['aberta', 'finalizada', 'cancelada'], true)) {
            $where[] = 'm.situacao = ?';
            $params[] = $situacao;
        }

        $tipoMovimentacao = trim((string) ($filtros['tipo_movimentacao'] ?? ''));
        $tiposPermitidos = [
            'retirada_chave', 'devolucao_chave',
            'retirada_item', 'devolucao_item',
            'retirada_recurso', 'devolucao_recurso',
        ];
        if (in_array($tipoMovimentacao, $tiposPermitidos, true)) {
            $where[] = 'm.tipo_movimentacao = ?';
            $params[] = $tipoMovimentacao;
        }

        $tipoRecurso = trim((string) ($filtros['tipo_recurso'] ?? ''));
        if ($tipoRecurso === 'chave') {
            $where[] = 'm.sala_id IS NOT NULL';
        } elseif ($tipoRecurso === 'item') {
            $where[] = 'm.item_portaria_id IS NOT NULL';
        } elseif ($tipoRecurso === 'outro') {
            $where[] = 'm.sala_id IS NULL AND m.item_portaria_id IS NULL';
        }

        $sql = 'SELECT m.*,
                       u.nome AS usuario_nome,
                       p.nome AS usuario_perfil,
                       s.nome AS sala_nome,
                       s.codigo AS sala_codigo,
                       i.nome AS item_nome,
                       i.codigo AS item_codigo,
                       devolvedor.nome AS devolvido_por_nome,
                       registrador.nome AS registrado_por_nome,
                       COALESCE(m.retirada_em, m.criado_em) AS momento_relatorio
                FROM movimentacoes m
                JOIN usuarios u ON u.id = m.usuario_id
                LEFT JOIN perfis p ON p.id = u.perfil_id
                LEFT JOIN salas s ON s.id = m.sala_id
                LEFT JOIN itens_portaria i ON i.id = m.item_portaria_id
                LEFT JOIN usuarios devolvedor ON devolvedor.id = m.devolvido_por_usuario_id
                LEFT JOIN usuarios registrador ON registrador.id = m.registrado_por_usuario_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY COALESCE(m.retirada_em, m.criado_em) DESC, m.id DESC';

        $stmt = $this->db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function sugestoesRelatorioDirecao(): array
    {
        return $this->db()->query(
            "SELECT MIN(fonte.ordem) AS ordem,
                    fonte.valor,
                    SUBSTRING_INDEX(GROUP_CONCAT(fonte.tipo ORDER BY fonte.ordem SEPARATOR ', '), ', ', 1) AS tipo
             FROM (
                SELECT 1 AS ordem, 'Usuário' AS tipo, TRIM(u.nome) AS valor
                FROM usuarios u
                WHERE u.situacao = 'ativo'
                UNION ALL
                SELECT 2 AS ordem, 'Sala' AS tipo, TRIM(s.nome) AS valor
                FROM salas s
                UNION ALL
                SELECT 3 AS ordem, 'Código de sala' AS tipo, TRIM(s.codigo) AS valor
                FROM salas s
                WHERE s.codigo IS NOT NULL
                UNION ALL
                SELECT 4 AS ordem, 'Item' AS tipo, TRIM(i.nome) AS valor
                FROM itens_portaria i
                UNION ALL
                SELECT 5 AS ordem, 'Código de item' AS tipo, TRIM(i.codigo) AS valor
                FROM itens_portaria i
                WHERE i.codigo IS NOT NULL
             ) fonte
             WHERE fonte.valor <> ''
             GROUP BY fonte.valor
             ORDER BY ordem, fonte.valor
             LIMIT 800"
        )->fetchAll();
    }

    public function relatorioSalasPortaria(string $inicio, string $fim, ?int $salaId = null): array
    {
        $where = [
            'm.sala_id IS NOT NULL',
            '((m.retirada_em >= ? AND m.retirada_em <= ?)
              OR (m.devolucao_real_em >= ? AND m.devolucao_real_em <= ?)
              OR (m.retirada_em IS NULL AND m.criado_em >= ? AND m.criado_em <= ?))',
        ];
        $params = [$inicio, $fim, $inicio, $fim, $inicio, $fim];

        if ($salaId !== null && $salaId > 0) {
            $where[] = 'm.sala_id = ?';
            $params[] = $salaId;
        }

        $stmt = $this->db()->prepare(
            'SELECT m.*,
                    u.nome AS usuario_nome,
                    p.nome AS usuario_perfil,
                    s.nome AS sala_nome,
                    s.codigo AS sala_codigo,
                    s.bloco AS sala_bloco,
                    devolvedor.nome AS devolvido_por_nome,
                    registrador.nome AS registrado_por_nome
             FROM movimentacoes m
             JOIN usuarios u ON u.id = m.usuario_id
             LEFT JOIN perfis p ON p.id = u.perfil_id
             JOIN salas s ON s.id = m.sala_id
             LEFT JOIN usuarios devolvedor ON devolvedor.id = m.devolvido_por_usuario_id
             LEFT JOIN usuarios registrador ON registrador.id = m.registrado_por_usuario_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY COALESCE(m.retirada_em, m.criado_em) ASC, m.id ASC'
        );
        $stmt->execute($params);
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

    public function paraCalendario(string $inicio, string $fim, ?int $salaId = null): array
    {
        $sql = 'SELECT m.*, u.nome AS usuario_nome, s.nome AS sala_nome, s.codigo AS sala_codigo
                FROM movimentacoes m
                JOIN usuarios u ON u.id = m.usuario_id
                JOIN salas s ON s.id = m.sala_id
                WHERE m.sala_id IS NOT NULL
                  AND m.retirada_em IS NOT NULL
                  AND m.retirada_em >= ?
                  AND m.retirada_em < ?';
        $params = [$inicio, $fim];
        if ($salaId !== null && $salaId > 0) {
            $sql .= ' AND m.sala_id = ?';
            $params[] = $salaId;
        }
        $sql .= ' ORDER BY m.retirada_em, s.nome, m.id';

        $stmt = $this->db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function detalhesCalendario(int $id): ?array
    {
        $stmt = $this->db()->prepare(
            'SELECT m.*,
                    u.nome AS usuario_nome,
                    u.email AS usuario_email,
                    devolvedor.nome AS devolvido_por_nome,
                    registrador.nome AS registrado_por_nome,
                    s.nome AS sala_nome,
                    s.codigo AS sala_codigo,
                    s.bloco AS sala_bloco,
                    s.tipo_ambiente AS sala_tipo
             FROM movimentacoes m
             JOIN usuarios u ON u.id = m.usuario_id
             LEFT JOIN usuarios devolvedor ON devolvedor.id = m.devolvido_por_usuario_id
             LEFT JOIN usuarios registrador ON registrador.id = m.registrado_por_usuario_id
             LEFT JOIN salas s ON s.id = m.sala_id
             WHERE m.id = ? AND m.sala_id IS NOT NULL
             LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
