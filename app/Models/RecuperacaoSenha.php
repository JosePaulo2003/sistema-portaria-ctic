<?php
declare(strict_types=1);

/**
 * GUIA DE MANUTENCAO: Este arquivo mantém códigos de recuperação com hash, expiração e limite de tentativas.
 *
 * Ponto de atencao: Mantenha parâmetros preparados e regras de consulta explícitas. O banco executa SQL, não boas intenções.
 */

namespace App\Models;

use App\Core\Model;
use PDO;
use Throwable;

final class RecuperacaoSenha extends Model
{
    protected string $table = 'recuperacoes_senha';

    public function podeSolicitar(int $usuarioId, string $ipHash): bool
    {
        $this->limparAntigas();
        $stmt = $this->db()->prepare(
            'SELECT
                SUM(usuario_id = ?) AS total_usuario,
                SUM(solicitado_ip_hash = ?) AS total_ip
             FROM recuperacoes_senha
             WHERE criado_em >= DATE_SUB(NOW(), INTERVAL 1 HOUR)'
        );
        $stmt->execute([$usuarioId, $ipHash]);
        $limites = $stmt->fetch() ?: [];

        return (int) ($limites['total_usuario'] ?? 0) < 3
            && (int) ($limites['total_ip'] ?? 0) < 10;
    }

    public function criar(int $usuarioId, string $codigo, string $ipHash): int
    {
        $pdo = $this->db();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'UPDATE recuperacoes_senha
                 SET usado_em = NOW()
                 WHERE usuario_id = ? AND usado_em IS NULL'
            );
            $stmt->execute([$usuarioId]);

            $id = $this->create([
                'usuario_id' => $usuarioId,
                'token_hash' => hash('sha256', $codigo),
                'expira_em' => date('Y-m-d H:i:s', time() + 1800),
                'solicitado_ip_hash' => $ipHash,
            ]);
            $pdo->commit();
            return $id;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function invalidar(int $id): void
    {
        $stmt = $this->db()->prepare('UPDATE recuperacoes_senha SET usado_em = NOW() WHERE id = ? AND usado_em IS NULL');
        $stmt->execute([$id]);
    }

    public function redefinir(string $email, string $codigo, string $senhaHash): bool
    {
        $pdo = $this->db();
        $pdo->beginTransaction();
        try {
            // O lock impede duas abas de consumirem o mesmo codigo ao mesmo
            // tempo. Sem ele, "uso unico" seria mais uma sugestao editorial.
            $stmt = $pdo->prepare(
                "SELECT r.id, r.usuario_id, r.token_hash, r.tentativas, r.expira_em
                 FROM recuperacoes_senha r
                 JOIN usuarios u ON u.id = r.usuario_id
                 WHERE u.email = ?
                   AND u.situacao = 'ativo'
                   AND r.usado_em IS NULL
                 ORDER BY r.id DESC
                 LIMIT 1 FOR UPDATE"
            );
            $stmt->execute([$email]);
            $recuperacao = $stmt->fetch();
            if (
                !$recuperacao
                || (int) $recuperacao['tentativas'] >= 5
                || strtotime((string) $recuperacao['expira_em']) <= time()
            ) {
                $pdo->rollBack();
                return false;
            }

            // hash_equals evita comparacao vulneravel a timing. O codigo nunca
            // e salvo em texto puro; suporte tecnico tambem nao precisa ve-lo.
            if (!hash_equals((string) $recuperacao['token_hash'], hash('sha256', $codigo))) {
                $tentativas = (int) $recuperacao['tentativas'] + 1;
                $pdo->prepare(
                    'UPDATE recuperacoes_senha
                     SET tentativas = ?, usado_em = IF(? >= 5, NOW(), usado_em)
                     WHERE id = ?'
                )->execute([$tentativas, $tentativas, (int) $recuperacao['id']]);
                $pdo->commit();
                return false;
            }

            $pdo->prepare('UPDATE usuarios SET senha_hash = ? WHERE id = ?')
                ->execute([$senhaHash, (int) $recuperacao['usuario_id']]);
            // Ao trocar a senha, invalidamos todos os codigos ainda abertos do
            // usuario. Deixar um codigo antigo vivo seria instalar uma porta
            // nova e guardar a chave velha embaixo do tapete.
            $pdo->prepare(
                'UPDATE recuperacoes_senha SET usado_em = NOW()
                 WHERE usuario_id = ? AND usado_em IS NULL'
            )->execute([(int) $recuperacao['usuario_id']]);
            $pdo->commit();
            return true;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    private function limparAntigas(): void
    {
        $this->db()->exec(
            'DELETE FROM recuperacoes_senha
             WHERE criado_em < DATE_SUB(NOW(), INTERVAL 7 DAY)'
        );
    }
}
