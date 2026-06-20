<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

// Permissões de chave por usuário, sala, dias permitidos e validade.
class PermissaoSala extends Model
{
    protected string $table = 'permissoes_salas';

    public function withDetails(): array
    {
        return $this->db()->query(
            'SELECT p.*, u.nome AS usuario_nome, s.nome AS sala_nome, a.nome AS autorizador_nome
             FROM permissoes_salas p
             JOIN usuarios u ON u.id = p.usuario_id
             LEFT JOIN salas s ON s.id = p.sala_id
             JOIN usuarios a ON a.id = p.autorizado_por
             ORDER BY p.criado_em DESC, p.id DESC'
        )->fetchAll();
    }

    public function usuarioTemAcesso(int $usuarioId, int $salaId): bool
    {
        $stmt = $this->db()->prepare(
            'SELECT COUNT(*)
             FROM permissoes_salas
             WHERE usuario_id = ?
               AND situacao = "ativa"
               AND (sala_id = ? OR acesso_total = 1)
               AND (inicio_autorizacao IS NULL OR inicio_autorizacao <= NOW())
               AND (expira_em IS NULL OR expira_em >= NOW())'
        );
        $stmt->execute([$usuarioId, $salaId]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
