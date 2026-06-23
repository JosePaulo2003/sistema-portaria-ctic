<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

// Solicitações vindas de formulários externos antes da aprovação do desenvolvedor.
class SolicitacaoUsuario extends Model
{
    protected string $table = 'solicitacoes_usuarios';

    public function ensureTable(): void
    {
        $this->db()->exec(
            'CREATE TABLE IF NOT EXISTS solicitacoes_usuarios (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(150) NOT NULL,
                email VARCHAR(190) NOT NULL,
                perfil_solicitado VARCHAR(100) NOT NULL,
                telefone VARCHAR(40) NULL,
                matricula VARCHAR(80) NULL,
                observacao TEXT NULL,
                origem VARCHAR(80) NOT NULL DEFAULT "google_forms",
                payload_json JSON NULL,
                situacao ENUM("pendente","aprovada","recusada") NOT NULL DEFAULT "pendente",
                aprovado_por INT NULL,
                aprovado_em DATETIME NULL,
                usuario_id INT NULL,
                criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_sol_usuario_aprovador FOREIGN KEY (aprovado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
                CONSTRAINT fk_sol_usuario_criado FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function receber(array $dados): int
    {
        $this->ensureTable();
        return $this->create([
            'nome' => trim((string) ($dados['nome'] ?? '')),
            'email' => mb_strtolower(trim((string) ($dados['email'] ?? ''))),
            'perfil_solicitado' => trim((string) ($dados['perfil'] ?? $dados['perfil_solicitado'] ?? 'Visitante')),
            'telefone' => $dados['telefone'] ?? null,
            'matricula' => $dados['matricula'] ?? null,
            'observacao' => $dados['observacao'] ?? null,
            'origem' => $dados['origem'] ?? 'google_forms',
            'payload_json' => json_encode($dados, JSON_UNESCAPED_UNICODE),
            'situacao' => 'pendente',
        ]);
    }

    public function find(int $id): ?array
    {
        $this->ensureTable();
        return parent::find($id);
    }

    public function pendentes(): array
    {
        $this->ensureTable();
        return $this->db()->query('SELECT * FROM solicitacoes_usuarios WHERE situacao = "pendente" ORDER BY criado_em ASC')->fetchAll();
    }

    public function recentes(): array
    {
        $this->ensureTable();
        return $this->db()->query('SELECT * FROM solicitacoes_usuarios ORDER BY criado_em DESC LIMIT 100')->fetchAll();
    }

    public function emailPendenteOuUsuarioExiste(string $email): bool
    {
        $this->ensureTable();
        $stmt = $this->db()->prepare(
            'SELECT
                (SELECT COUNT(*) FROM usuarios WHERE email = ?) +
                (SELECT COUNT(*) FROM solicitacoes_usuarios WHERE email = ? AND situacao = "pendente")'
        );
        $stmt->execute([$email, $email]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function aprovar(int $id, int $usuarioId, int $aprovadorId): void
    {
        $this->update($id, [
            'situacao' => 'aprovada',
            'usuario_id' => $usuarioId,
            'aprovado_por' => $aprovadorId,
            'aprovado_em' => date('Y-m-d H:i:s'),
        ]);
    }

    public function recusar(int $id, int $aprovadorId): void
    {
        $this->update($id, [
            'situacao' => 'recusada',
            'aprovado_por' => $aprovadorId,
            'aprovado_em' => date('Y-m-d H:i:s'),
        ]);
    }
}
