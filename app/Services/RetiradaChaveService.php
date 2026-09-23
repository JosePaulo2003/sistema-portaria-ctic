<?php
declare(strict_types=1);

/**
 * GUIA DE MANUTENCAO: Este arquivo implementa a máquina de estados da solicitação de chave e as confirmações da Portaria.
 *
 * Ponto de atencao: Preserve transações, validações e efeitos colaterais na ordem atual. Reordenar por estética é uma forma criativa de fabricar inconsistência.
 */

namespace App\Services;

use App\Core\Database;
use App\Models\Movimentacao;
use App\Models\NotificacaoPortaria;
use App\Models\BloqueioChave;
use App\Models\Sala;
use App\Models\User;
use PDO;
use Throwable;

final class RetiradaChaveService
{
    private const TIPO_NOTIFICACAO = 'retirada_chave_pendente';
    private const VALIDADE_MINUTOS = 10;
    private const LIMITE_TENTATIVAS = 5;
    private const BLOQUEIO_SEGUNDOS = 60;

    public function exigeConfirmacaoPortaria(array $usuario): bool
    {
        return $this->exigeCodigoTemporario($usuario);
    }

    public function exigeCodigoTemporario(array $usuario): bool
    {
        // comparableProfile() elimina diferencas de acento/caixa. Nao compare o
        // texto cru do banco: "Estagiario" e "Estagiário" ja protagonizaram
        // bugs suficientes para merecer aposentadoria compulsoria.
        $perfil = comparableProfile((string) ($usuario['perfil_nome'] ?? ''));
        return in_array($perfil, array_map('comparableProfile', [
            'Estagiário',
            'Aluno Bolsista',
            'Aluno',
        ]), true);
    }

    public function solicitar(array $usuario, int $salaId, ?string $observacao = null): array
    {
        $usuarioId = (int) ($usuario['id'] ?? 0);
        $usuarioCompleto = (new User())->findWithProfile($usuarioId);
        $sala = (new Sala())->find($salaId);

        if (!$usuarioCompleto || ($usuarioCompleto['situacao'] ?? '') !== 'ativo') {
            throw new \RuntimeException('Usuário inválido ou inativo.');
        }
        if (!$sala || !(new Sala())->chavePodeSerRetirada($salaId, $usuarioCompleto)) {
            throw new \RuntimeException('Esta chave não está disponível ou autorizada para retirada.');
        }

        $notificacoes = new NotificacaoPortaria();
        $pendenteSala = $notificacoes->pendenteParaSala($salaId);
        if ($pendenteSala && (int) ($pendenteSala['contexto']['usuario_id'] ?? 0) !== $usuarioId) {
            throw new \RuntimeException('Esta chave já possui uma solicitação aguardando confirmação na Portaria.');
        }

        $exigeCodigo = $this->exigeCodigoTemporario($usuarioCompleto);
        $codigo = $exigeCodigo ? (string) random_int(1000, 9999) : null;
        $agora = new \DateTimeImmutable();
        $expiraEm = $agora->modify('+' . self::VALIDADE_MINUTOS . ' minutes');
        // O contexto JSON e o pequeno estado da solicitacao. Campos novos devem
        // ser opcionais na leitura, pois alertas antigos continuam no banco e
        // nao recebem atualizacao por telepatia.
        $contexto = [
            'usuario_id' => $usuarioId,
            'usuario_nome' => (string) $usuarioCompleto['nome'],
            'perfil_nome' => fixMojibakeText((string) ($usuarioCompleto['perfil_nome'] ?? '')),
            'grupo_usuario' => $this->grupoUsuario($usuarioCompleto),
            'sala_id' => $salaId,
            'sala_nome' => (string) $sala['nome'],
            'observacao' => trim((string) $observacao) ?: null,
            'exige_codigo_temporario' => $exigeCodigo,
            // Regra atual: usuario e agente veem o mesmo codigo e o agente
            // aceita/recusa. O hash abaixo aparece apenas em alertas legados.
            'codigo_exibicao' => $codigo,
            'expira_em' => $expiraEm->format('Y-m-d H:i:s'),
            'tentativas' => 0,
            'bloqueado_ate' => null,
            'situacao' => 'aguardando_confirmacao',
        ];

        $existente = $notificacoes->pendenteParaUsuarioSala($usuarioId, $salaId);
        $dados = [
            'tipo' => self::TIPO_NOTIFICACAO,
            'mensagem' => sprintf('%s aguarda a entrega da chave %s.', $usuarioCompleto['nome'], $sala['nome']),
            'contexto_json' => $this->encodeContexto($contexto),
            'lida_em' => null,
            'criado_em' => $agora->format('Y-m-d H:i:s'),
        ];

        if ($existente) {
            $notificacaoId = (int) $existente['id'];
            $notificacoes->update($notificacaoId, $dados);
        } else {
            $notificacaoId = $notificacoes->create($dados);
        }

        audit('Chaves', 'solicitacao_retirada', 'Solicitação de retirada enviada para confirmação da Portaria.', [
            'notificacao_id' => $notificacaoId,
            'usuario_id' => $usuarioId,
            'sala_id' => $salaId,
        ]);

        return [
            'notificacao_id' => $notificacaoId,
            'codigo' => $codigo,
            'exige_codigo_temporario' => $exigeCodigo,
            'expira_em' => $expiraEm->format('Y-m-d H:i:s'),
            'sala_nome' => (string) $sala['nome'],
        ];
    }

    public function confirmar(int $notificacaoId, string $codigo, array $agente): array
    {
        $pdo = Database::pdo();
        // A confirmacao e atomica: alerta, disponibilidade e movimentacao devem
        // mudar juntos. Metade de uma entrega e apenas uma chave desaparecida
        // com documentacao sofisticada.
        $pdo->beginTransaction();

        try {
            $notificacao = $this->buscarPendenteParaAtualizacao($pdo, $notificacaoId);
            if (!$notificacao) {
                throw new \RuntimeException('A solicitação não está mais pendente.');
            }

            $contexto = $this->decodeContexto((string) ($notificacao['contexto_json'] ?? ''));
            $agora = new \DateTimeImmutable();
            $bloqueadoAte = $this->dataContexto($contexto['bloqueado_ate'] ?? null);
            if ($bloqueadoAte && $bloqueadoAte > $agora) {
                throw new \RuntimeException('Muitas tentativas incorretas. Aguarde até ' . $bloqueadoAte->format('H:i:s') . '.');
            }

            $expiraEm = $this->dataContexto($contexto['expira_em'] ?? null);
            if (!$expiraEm || $expiraEm < $agora) {
                $contexto['situacao'] = 'codigo_expirado';
                $this->atualizarContexto($pdo, $notificacaoId, $contexto);
                $pdo->commit();
                return ['ok' => false, 'mensagem' => 'O código expirou. Peça ao usuário para gerar um novo código; o alerta continuará aberto.'];
            }

            // Compatibilidade com solicitacoes criadas antes do codigo visivel.
            // Remova este ramo somente depois de provar que nao ha alertas
            // legados pendentes em producao — "acho que nao" nao e uma query.
            $codigoLegado = !array_key_exists('codigo_exibicao', $contexto) && !empty($contexto['codigo_hash']);
            $codigo = trim($codigo);
            if ($codigoLegado && ($codigo === '' || !password_verify($codigo, (string) $contexto['codigo_hash']))) {
                $tentativas = ((int) ($contexto['tentativas'] ?? 0)) + 1;
                $contexto['tentativas'] = $tentativas;
                $contexto['situacao'] = 'codigo_incorreto';
                if ($tentativas % self::LIMITE_TENTATIVAS === 0) {
                    $contexto['bloqueado_ate'] = $agora->modify('+' . self::BLOQUEIO_SEGUNDOS . ' seconds')->format('Y-m-d H:i:s');
                }
                $this->atualizarContexto($pdo, $notificacaoId, $contexto);
                $pdo->commit();
                return ['ok' => false, 'mensagem' => 'Código temporário incorreto. A solicitação permanece aberta.'];
            }

            $usuarioId = (int) ($contexto['usuario_id'] ?? 0);
            $salaId = (int) ($contexto['sala_id'] ?? 0);
            $usuario = (new User())->findWithProfile($usuarioId);
            if (!$usuario || ($usuario['situacao'] ?? '') !== 'ativo') {
                throw new \RuntimeException('O usuário não está mais ativo.');
            }
            $bloqueio = (new BloqueioChave())->ativoParaUsuario($usuarioId);
            if ($bloqueio) {
                throw new \RuntimeException('O usuário está temporariamente bloqueado para retirar chaves.');
            }
            $solicitacaoLegada = !array_key_exists('exige_codigo_temporario', $contexto);
            if (!$this->exigeConfirmacaoPortaria($usuario) && !$solicitacaoLegada) {
                throw new \RuntimeException('Este perfil utiliza retirada automática e não precisa de confirmação da Portaria.');
            }

            // FOR UPDATE serializa duas confirmacoes simultaneas. Sem o lock,
            // dois cliques rapidos podem entregar a mesma chave duas vezes,
            // um feito impressionante para um objeto fisico e pessimo para nos.
            $salaLock = $pdo->prepare('SELECT id FROM salas WHERE id = ? LIMIT 1 FOR UPDATE');
            $salaLock->execute([$salaId]);
            if (!$salaLock->fetchColumn()) {
                throw new \RuntimeException('A sala vinculada à solicitação não existe mais.');
            }
            $movimentacaoAberta = $pdo->prepare(
                'SELECT id FROM movimentacoes WHERE sala_id = ? AND situacao = ? LIMIT 1 FOR UPDATE'
            );
            $movimentacaoAberta->execute([$salaId, 'aberta']);
            if ($movimentacaoAberta->fetchColumn()) {
                throw new \RuntimeException('A chave já foi entregue em outra confirmação.');
            }
            if (!(new Sala())->chavePodeSerRetirada($salaId, $usuario)) {
                throw new \RuntimeException('A chave não está mais disponível ou autorizada para este usuário.');
            }

            $movimentacaoId = (new Movimentacao())->create([
                'usuario_id' => $usuarioId,
                'sala_id' => $salaId,
                'tipo_movimentacao' => 'retirada_chave',
                'situacao' => 'aberta',
                'retirada_em' => $agora->format('Y-m-d H:i:s'),
                'devolucao_prevista_em' => null,
                'registrado_por_usuario_id' => (int) ($agente['id'] ?? 0),
                'observacao' => $contexto['observacao'] ?? null,
            ]);

            // Codigo cumpriu sua funcao; nao o transforme em souvenir no banco.
            unset($contexto['codigo_hash'], $contexto['codigo_exibicao']);
            $contexto['situacao'] = 'confirmada';
            $contexto['confirmada_em'] = $agora->format('Y-m-d H:i:s');
            $contexto['agente_portaria_id'] = (int) ($agente['id'] ?? 0);
            $contexto['movimentacao_id'] = $movimentacaoId;
            $stmt = $pdo->prepare('UPDATE notificacoes_portaria SET contexto_json = ?, lida_em = ? WHERE id = ?');
            $stmt->execute([$this->encodeContexto($contexto), $agora->format('Y-m-d H:i:s'), $notificacaoId]);
            $pdo->commit();

            audit('Portaria', 'confirmacao_retirada_chave', 'Entrega de chave confirmada com código temporário.', [
                'notificacao_id' => $notificacaoId,
                'movimentacao_id' => $movimentacaoId,
                'usuario_id' => $usuarioId,
                'sala_id' => $salaId,
            ]);

            return ['ok' => true, 'mensagem' => 'Solicitação aceita. A entrega da chave foi registrada.'];
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $error;
        }
    }

    public function fechar(int $notificacaoId, array $agente): bool
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $notificacao = $this->buscarPendenteParaAtualizacao($pdo, $notificacaoId);
            if (!$notificacao) {
                $pdo->rollBack();
                return false;
            }

            $agora = date('Y-m-d H:i:s');
            $contexto = $this->decodeContexto((string) ($notificacao['contexto_json'] ?? ''));
            unset($contexto['codigo_hash'], $contexto['codigo_exibicao']);
            $contexto['situacao'] = 'recusada_pela_portaria';
            $contexto['fechada_em'] = $agora;
            $contexto['agente_portaria_id'] = (int) ($agente['id'] ?? 0);
            $stmt = $pdo->prepare('UPDATE notificacoes_portaria SET contexto_json = ?, lida_em = ? WHERE id = ? AND lida_em IS NULL');
            $stmt->execute([$this->encodeContexto($contexto), $agora, $notificacaoId]);
            $pdo->commit();
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $error;
        }

        audit('Portaria', 'recusa_solicitacao_retirada', 'Solicitação de retirada recusada sem entrega da chave.', [
            'notificacao_id' => $notificacaoId,
            'usuario_id' => (int) ($contexto['usuario_id'] ?? 0),
            'sala_id' => (int) ($contexto['sala_id'] ?? 0),
        ]);
        return true;
    }

    private function grupoUsuario(array $usuario): string
    {
        $perfil = comparableProfile((string) ($usuario['perfil_nome'] ?? ''));
        $grupos = [
            'Alunos' => ['Aluno'],
            'Bolsistas' => ['Aluno Bolsista'],
            'Estagiários' => ['Estagiário'],
            'Professores' => ['Professor'],
            'Administração' => ['Administrativo', 'Diretor', 'Secretário de Curso', 'Coordenador de Curso', 'Desenvolvedor'],
            'Portaria' => ['Agente de Portaria'],
            'Serviços Gerais' => ['Serviços Gerais'],
            'Visitantes' => ['Visitante'],
            'Motoristas' => ['Motorista'],
        ];
        foreach ($grupos as $grupo => $perfis) {
            if (in_array($perfil, array_map('comparableProfile', $perfis), true)) {
                return $grupo;
            }
        }
        return 'Outros';
    }

    private function buscarPendenteParaAtualizacao(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM notificacoes_portaria WHERE id = ? AND tipo = ? AND lida_em IS NULL LIMIT 1 FOR UPDATE');
        $stmt->execute([$id, self::TIPO_NOTIFICACAO]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function atualizarContexto(PDO $pdo, int $id, array $contexto): void
    {
        $stmt = $pdo->prepare('UPDATE notificacoes_portaria SET contexto_json = ? WHERE id = ? AND lida_em IS NULL');
        $stmt->execute([$this->encodeContexto($contexto), $id]);
    }

    private function encodeContexto(array $contexto): string
    {
        return (string) json_encode($contexto, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    private function decodeContexto(string $contexto): array
    {
        if ($contexto === '') {
            return [];
        }
        $decoded = json_decode($contexto, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function dataContexto(mixed $valor): ?\DateTimeImmutable
    {
        if (!is_string($valor) || trim($valor) === '') {
            return null;
        }
        try {
            return new \DateTimeImmutable($valor);
        } catch (Throwable) {
            return null;
        }
    }
}
