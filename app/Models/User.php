<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDOException;

// Modelo de usuários: centraliza login, filtros por perfil e exclusão segura.
class User extends Model
{
    protected string $table = 'usuarios';

    public function allWithProfile(): array
    {
        return $this->db()->query(
            'SELECT u.*, p.nome AS perfil_nome
             FROM usuarios u
             JOIN perfis p ON p.id = u.perfil_id
             ORDER BY u.nome'
        )->fetchAll();
    }

    public function findWithProfile(int $id): ?array
    {
        $stmt = $this->db()->prepare(
            'SELECT u.*, p.nome AS perfil_nome
             FROM usuarios u
             JOIN perfis p ON p.id = u.perfil_id
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
            'foto_perfil_url' => null,
            'professor_indicador_id' => null,
            'projeto_pesquisa' => null,
        ]);
    }
}
