<?php
declare(strict_types=1);

/**
 * GUIA DE MANUTENCAO: Este arquivo mantém alertas persistentes e seu contexto JSON.
 *
 * Ponto de atencao: Mantenha parâmetros preparados e regras de consulta explícitas. O banco executa SQL, não boas intenções.
 */

namespace App\Models;

use App\Core\Model;

// Notificações operacionais exibidas para a portaria.
class NotificacaoPortaria extends Model
{
    protected string $table = 'notificacoes_portaria';

    public function retiradasPendentes(): array
    {
        $stmt = $this->db()->prepare(
            'SELECT * FROM notificacoes_portaria
             WHERE tipo = ? AND lida_em IS NULL
             ORDER BY criado_em ASC, id ASC'
        );
        $stmt->execute(['retirada_chave_pendente']);

        return array_values(array_filter(array_map(function (array $row): ?array {
            $contexto = json_decode((string) ($row['contexto_json'] ?? ''), true);
            if (!is_array($contexto)) {
                return null;
            }
            $contexto['codigo_legado'] = !array_key_exists('codigo_exibicao', $contexto) && !empty($contexto['codigo_hash']);
            unset($contexto['codigo_hash']);
            return array_merge($row, ['contexto' => $contexto, 'contexto_json' => null]);
        }, $stmt->fetchAll())));
    }

    public function pendenteParaUsuarioSala(int $usuarioId, int $salaId): ?array
    {
        foreach ($this->retiradasPendentes() as $notificacao) {
            $contexto = $notificacao['contexto'] ?? [];
            if ((int) ($contexto['usuario_id'] ?? 0) === $usuarioId && (int) ($contexto['sala_id'] ?? 0) === $salaId) {
                return $notificacao;
            }
        }
        return null;
    }

    public function pendenteParaSala(int $salaId): ?array
    {
        foreach ($this->retiradasPendentes() as $notificacao) {
            if ((int) ($notificacao['contexto']['sala_id'] ?? 0) === $salaId) {
                return $notificacao;
            }
        }
        return null;
    }
}
