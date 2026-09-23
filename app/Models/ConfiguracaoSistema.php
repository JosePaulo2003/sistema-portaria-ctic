<?php
declare(strict_types=1);

/**
 * GUIA DE MANUTENCAO: Este arquivo lê parâmetros operacionais armazenados no banco.
 *
 * Ponto de atencao: Mantenha parâmetros preparados e regras de consulta explícitas. O banco executa SQL, não boas intenções.
 */

namespace App\Models;

use App\Core\Model;

// Acesso às configurações ajustáveis sem mudar código.
class ConfiguracaoSistema extends Model
{
    protected string $table = 'configuracoes_sistema';

    public function getValue(string $key, string $default = ''): string
    {
        $stmt = $this->db()->prepare('SELECT valor FROM configuracoes_sistema WHERE chave = ? LIMIT 1');
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();
        return $value === false ? $default : (string) $value;
    }

    public function setValue(string $key, string $value, ?string $descricao = null): void
    {
        $stmt = $this->db()->prepare(
            'INSERT INTO configuracoes_sistema (chave, valor, descricao)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE valor = VALUES(valor), descricao = COALESCE(VALUES(descricao), descricao)'
        );
        $stmt->execute([$key, $value, $descricao]);
    }
}
