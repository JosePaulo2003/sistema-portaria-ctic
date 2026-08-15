<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDOException;

// Modelo de usuários: centraliza login, filtros por perfil e exclusão segura.
class User extends Model
{
    protected string $table = 'usuarios';

    // Perfis que possuem uma rota de criacao de reserva comum ou recorrente.
    private const PERFIS_SOLICITANTES_RESERVA = [
        'Desenvolvedor',
        'Administrativo',
        'Secretário de Curso',
        'Coordenador de Curso',
        'Agente de Portaria',
        'Professor',
    ];

    public function allWithProfile(): array
    {
        return $this->db()->query(
            'SELECT u.*, p.nome AS perfil_nome, c.nome AS curso_nome
             FROM usuarios u
             JOIN perfis p ON p.id = u.perfil_id
             LEFT JOIN cursos c ON c.id = u.curso_id
             ORDER BY u.nome'
        )->fetchAll();
    }

    public function ativosComPermissaoSolicitarReserva(): array
    {
        $placeholders = implode(', ', array_fill(0, count(self::PERFIS_SOLICITANTES_RESERVA), '?'));
        $stmt = $this->db()->prepare(
            'SELECT u.id, u.nome, u.email, p.nome AS perfil_nome
             FROM usuarios u
             JOIN perfis p ON p.id = u.perfil_id
             WHERE u.situacao = ?
               AND p.nome IN (' . $placeholders . ')
             ORDER BY u.nome, u.id'
        );
        $stmt->execute(array_merge(['ativo'], self::PERFIS_SOLICITANTES_RESERVA));
        return $stmt->fetchAll();
    }

    public function podeSolicitarReserva(int $usuarioId): bool
    {
        if ($usuarioId <= 0) {
            return false;
        }

        $placeholders = implode(', ', array_fill(0, count(self::PERFIS_SOLICITANTES_RESERVA), '?'));
        $stmt = $this->db()->prepare(
            'SELECT COUNT(*)
             FROM usuarios u
             JOIN perfis p ON p.id = u.perfil_id
             WHERE u.id = ?
               AND u.situacao = ?
               AND p.nome IN (' . $placeholders . ')'
        );
        $stmt->execute(array_merge([$usuarioId, 'ativo'], self::PERFIS_SOLICITANTES_RESERVA));
        return (int) $stmt->fetchColumn() > 0;
    }

    public function findWithProfile(int $id): ?array
    {
        $stmt = $this->db()->prepare(
            'SELECT u.*, p.nome AS perfil_nome, c.nome AS curso_nome
             FROM usuarios u
             JOIN perfis p ON p.id = u.perfil_id
             LEFT JOIN cursos c ON c.id = u.curso_id
             WHERE u.id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db()->prepare(
            'SELECT u.*, p.nome AS perfil_nome, p.nivel AS perfil_nivel
             FROM usuarios u
             JOIN perfis p ON p.id = u.perfil_id
             WHERE u.email = ? LIMIT 1'
        );
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByLoginIdentifier(string $identifier): array
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return [];
        }

        $stmt = $this->db()->prepare(
            'SELECT u.*, p.nome AS perfil_nome, p.nivel AS perfil_nivel
             FROM usuarios u
             JOIN perfis p ON p.id = u.perfil_id
             WHERE u.email = ? OR u.nome = ?
             ORDER BY u.situacao = "ativo" DESC, u.id
             LIMIT 2'
        );
        $stmt->execute([$identifier, $identifier]);
        return $stmt->fetchAll();
    }

    public function activePortariaAgents(): array
    {
        $stmt = $this->db()->prepare(
            'SELECT u.id, u.nome
             FROM usuarios u
             JOIN perfis p ON p.id = u.perfil_id
             WHERE u.situacao = ?
               AND p.nome = ?
             ORDER BY u.nome, u.id'
        );
        $stmt->execute(['ativo', 'Agente de Portaria']);
        return $stmt->fetchAll();
    }

    public function activePortariaAgentById(int $id): ?array
    {
        $stmt = $this->db()->prepare(
            'SELECT u.*, p.nome AS perfil_nome, p.nivel AS perfil_nivel
             FROM usuarios u
             JOIN perfis p ON p.id = u.perfil_id
             WHERE u.id = ?
               AND u.situacao = ?
               AND p.nome = ?
             LIMIT 1'
        );
        $stmt->execute([$id, 'ativo', 'Agente de Portaria']);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function byProfile(string $profile): array
    {
        $stmt = $this->db()->prepare(
            'SELECT u.*, p.nome AS perfil_nome, prof.nome AS professor_nome
             FROM usuarios u
             JOIN perfis p ON p.id = u.perfil_id
             LEFT JOIN usuarios prof ON prof.id = u.professor_indicador_id
             WHERE p.nome = ?
             ORDER BY u.nome'
        );
        $stmt->execute([$profile]);
        return $stmt->fetchAll();
    }

    public function temporaryVisitors(): array
    {
        return $this->db()->query(
            "SELECT u.*, p.nome AS perfil_nome
             FROM usuarios u
             JOIN perfis p ON p.id = u.perfil_id
             WHERE p.nome = 'Visitante'
               AND NOT (u.situacao = 'inativo' AND u.email LIKE 'removido_%@sgrp.local')
             ORDER BY
                u.acesso_expira_em IS NULL,
                u.acesso_expira_em ASC,
                u.nome ASC"
        )->fetchAll();
    }

    public function isVisitor(int $id): bool
    {
        $stmt = $this->db()->prepare(
            "SELECT COUNT(*)
             FROM usuarios u
             JOIN perfis p ON p.id = u.perfil_id
             WHERE u.id = ? AND p.nome = 'Visitante'"
        );
        $stmt->execute([$id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function purgeExpiredVisitors(): int
    {
        $ids = $this->db()->query(
            "SELECT u.id
             FROM usuarios u
             JOIN perfis p ON p.id = u.perfil_id
             WHERE p.nome = 'Visitante'
               AND u.acesso_expira_em IS NOT NULL
               AND u.acesso_expira_em <= NOW()"
        )->fetchAll(\PDO::FETCH_COLUMN);

        foreach ($ids as $id) {
            $this->removeTemporaryVisitor((int) $id);
        }

        return count($ids);
    }

    public function removeTemporaryVisitor(int $id): bool
    {
        if (!$this->isVisitor($id)) {
            return false;
        }

        $pdo = $this->db();
        $pdo->prepare('DELETE FROM permissoes_salas WHERE usuario_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM permissoes_itens WHERE usuario_id = ?')->execute([$id]);

        if ($this->deleteSafely($id)) {
            return true;
        }

        $this->anonymize($id);
        return true;
    }

    public function professoresAtivos(): array
    {
        $stmt = $this->db()->query(
            "SELECT u.*, p.nome AS perfil_nome
             FROM usuarios u
             JOIN perfis p ON p.id = u.perfil_id
             WHERE p.nome = 'Professor' AND u.situacao = 'ativo'
             ORDER BY u.nome"
        );
        return $stmt->fetchAll();
    }

    public function bolsistasComProfessor(): array
    {
        $stmt = $this->db()->query(
            "SELECT u.*, p.nome AS perfil_nome, prof.nome AS professor_nome
             FROM usuarios u
             JOIN perfis p ON p.id = u.perfil_id
             LEFT JOIN usuarios prof ON prof.id = u.professor_indicador_id
             WHERE p.nome IN ('Aluno Bolsista', 'Estagiario', 'Estagiário')
             ORDER BY u.nome"
        );
        return $stmt->fetchAll();
    }

    public function byProfileForProfessor(string $profile, int $professorId): array
    {
        $stmt = $this->db()->prepare(
            'SELECT u.*, p.nome AS perfil_nome, prof.nome AS professor_nome
             FROM usuarios u
             JOIN perfis p ON p.id = u.perfil_id
             LEFT JOIN usuarios prof ON prof.id = u.professor_indicador_id
             WHERE p.nome = ? AND u.professor_indicador_id = ?
             ORDER BY u.nome'
        );
        $stmt->execute([$profile, $professorId]);
        return $stmt->fetchAll();
    }

    public function belongsToProfessor(int $userId, int $professorId, string $profile = 'Aluno Bolsista'): bool
    {
        $stmt = $this->db()->prepare(
            'SELECT COUNT(*)
             FROM usuarios u
             JOIN perfis p ON p.id = u.perfil_id
             WHERE u.id = ? AND u.professor_indicador_id = ? AND p.nome = ?'
        );
        $stmt->execute([$userId, $professorId, $profile]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function touchLogin(int $id): void
    {
        $stmt = $this->db()->prepare('UPDATE usuarios SET ultimo_login_em = NOW() WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function deleteSafely(int $id): bool
    {
        try {
            return $this->delete($id);
        } catch (PDOException) {
            return false;
        }
    }

    public function anonymize(int $id): void
    {
        $this->update($id, [
            'nome' => 'Usuário removido #' . $id,
            'email' => 'removido_' . $id . '@sgrp.local',
            'senha_hash' => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
            'situacao' => 'inativo',
            'acesso_expira_em' => null,
            'foto_perfil_url' => null,
            'professor_indicador_id' => null,
            'projeto_pesquisa' => null,
            'curso_id' => null,
        ]);
    }
}
