<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

// Permissões de chave por usuário, sala, dias permitidos e validade.
class PermissaoSala extends Model
{
    protected string $table = 'permissoes_salas';

    public function withDetails(?int $usuarioId = null): array
    {
        $sql = 'SELECT p.*, u.nome AS usuario_nome, u.email AS usuario_email, u.situacao AS usuario_situacao,
                    pf.nome AS usuario_perfil_nome, pf.nivel AS usuario_perfil_nivel,
                    s.nome AS sala_nome, a.nome AS autorizador_nome
             FROM permissoes_salas p
             JOIN usuarios u ON u.id = p.usuario_id
             JOIN perfis pf ON pf.id = u.perfil_id
             LEFT JOIN salas s ON s.id = p.sala_id
             JOIN usuarios a ON a.id = p.autorizado_por';
        $conditions = ['p.situacao <> ?'];
        $params = ['revogada'];

        if ($usuarioId !== null && $usuarioId > 0) {
            $conditions[] = 'p.usuario_id = ?';
            $params[] = $usuarioId;
        }

        $sql .= ' WHERE ' . implode(' AND ', $conditions);
        $sql .= ' ORDER BY
                pf.nivel DESC,
                pf.nome,
                u.nome,
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

    public function findWithDetails(int $id): ?array
    {
        $stmt = $this->db()->prepare(
            'SELECT p.*, u.nome AS usuario_nome, u.email AS usuario_email,
                    pf.nome AS usuario_perfil_nome, pf.nivel AS usuario_perfil_nivel,
                    s.nome AS sala_nome, a.nome AS autorizador_nome
             FROM permissoes_salas p
             JOIN usuarios u ON u.id = p.usuario_id
             JOIN perfis pf ON pf.id = u.perfil_id
             LEFT JOIN salas s ON s.id = p.sala_id
             JOIN usuarios a ON a.id = p.autorizado_por
             WHERE p.id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function forProfessorOrientandos(int $professorId): array
    {
        $stmt = $this->db()->prepare(
            'SELECT p.*, u.nome AS usuario_nome, s.nome AS sala_nome, a.nome AS autorizador_nome
             FROM permissoes_salas p
             JOIN usuarios u ON u.id = p.usuario_id
             LEFT JOIN salas s ON s.id = p.sala_id
             JOIN usuarios a ON a.id = p.autorizado_por
             WHERE u.professor_indicador_id = ?
             ORDER BY p.criado_em DESC, p.id DESC'
        );
        $stmt->execute([$professorId]);
        return $stmt->fetchAll();
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
               AND (expira_em IS NULL OR expira_em >= NOW())
               AND (
                    horario_inicio IS NULL
                    OR horario_fim IS NULL
                    OR (horario_inicio <= horario_fim AND CURTIME() BETWEEN horario_inicio AND horario_fim)
                    OR (horario_inicio > horario_fim AND (CURTIME() >= horario_inicio OR CURTIME() <= horario_fim))
               )
               AND (dias_semana IS NULL OR dias_semana = "" OR FIND_IN_SET(?, REPLACE(dias_semana, ", ", ",")) > 0)'
        );
        $stmt->execute([$usuarioId, $salaId, $this->diaSemanaAtual()]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function usuarioTemAlgumAcesso(int $usuarioId): bool
    {
        $stmt = $this->db()->prepare(
            'SELECT COUNT(*)
             FROM permissoes_salas
             WHERE usuario_id = ?
               AND situacao = "ativa"
               AND (inicio_autorizacao IS NULL OR inicio_autorizacao <= NOW())
               AND (expira_em IS NULL OR expira_em >= NOW())
               AND (
                    horario_inicio IS NULL
                    OR horario_fim IS NULL
                    OR (horario_inicio <= horario_fim AND CURTIME() BETWEEN horario_inicio AND horario_fim)
                    OR (horario_inicio > horario_fim AND (CURTIME() >= horario_inicio OR CURTIME() <= horario_fim))
               )
               AND (dias_semana IS NULL OR dias_semana = "" OR FIND_IN_SET(?, REPLACE(dias_semana, ", ", ",")) > 0)'
        );
        $stmt->execute([$usuarioId, $this->diaSemanaAtual()]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Controla apenas a visibilidade da area de retirada do aluno. A permissao
     * precisa estar atribuida ao usuario e ativa; validade, dia e horario
     * continuam sendo verificados no momento de listar e retirar a chave.
     */
    public function usuarioTemChaveAtribuida(int $usuarioId): bool
    {
        $stmt = $this->db()->prepare(
            'SELECT COUNT(*)
             FROM permissoes_salas
             WHERE usuario_id = ?
               AND situacao = "ativa"
               AND (sala_id IS NOT NULL OR acesso_total = 1)'
        );
        $stmt->execute([$usuarioId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function excluirRevogadas(?int $usuarioId = null): int
    {
        $sql = 'DELETE FROM permissoes_salas WHERE situacao = "revogada"';
        $params = [];

        if ($usuarioId !== null && $usuarioId > 0) {
            $sql .= ' AND usuario_id = ?';
            $params[] = $usuarioId;
        }

        $stmt = $this->db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    private function diaSemanaAtual(): string
    {
        return [
            1 => 'segunda',
            2 => 'terca',
            3 => 'quarta',
            4 => 'quinta',
            5 => 'sexta',
            6 => 'sabado',
            7 => 'domingo',
        ][(int) date('N')];
    }
}
