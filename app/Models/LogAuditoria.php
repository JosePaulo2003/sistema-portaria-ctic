<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

// Guarda rastros das ações importantes feitas pelos usuários.
class LogAuditoria extends Model
{
    protected string $table = 'logs_auditoria';

    public function withUser(): array
    {
        return $this->db()->query(
            'SELECT l.*, u.nome AS usuario_nome
             FROM logs_auditoria l
             LEFT JOIN usuarios u ON u.id = l.usuario_id
             ORDER BY l.criado_em DESC, l.id DESC'
        )->fetchAll();
    }

    public function clear(): void
    {
        $this->db()->exec('DELETE FROM logs_auditoria');
    }
}
