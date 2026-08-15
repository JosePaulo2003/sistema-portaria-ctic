<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

// Registra eventos técnicos quando for necessário investigar comportamento interno.
class LogSistema extends Model
{
    protected string $table = 'logs_sistema';

    public function withUser(int $limit = 100): array
    {
        $stmt = $this->db()->prepare(
            'SELECT l.*, u.nome AS usuario_nome
             FROM logs_sistema l
             LEFT JOIN usuarios u ON u.id = l.usuario_id
             ORDER BY l.criado_em DESC, l.id DESC
             LIMIT ?'
        );
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function registrar(string $nivel, string $origem, string $mensagem, array $context = []): void
    {
        $this->create([
            'usuario_id' => currentUser()['id'] ?? null,
            'nivel' => $nivel,
            'origem' => $origem,
            'mensagem' => $mensagem,
            'contexto_json' => $context ? json_encode($context, JSON_UNESCAPED_UNICODE) : null,
            'criado_em' => \appTimestamp(),
        ]);
    }
}
