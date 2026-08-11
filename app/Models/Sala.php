<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

// Salas e ambientes, incluindo disponibilidade publica e retirada de chave.
class Sala extends Model
{
    protected string $table = 'salas';

    public function listDisponibilidade(array $filters = []): array
    {
        $salas = $this->all('nome');
        $data = $filters['data'] ?? date('Y-m-d');
        $horario = $filters['horario'] ?? date('H:i');
        $momento = $data . ' ' . $horario . ':00';

        foreach ($salas as &$sala) {
            [$status, $motivo] = $this->statusPublico((int) $sala['id'], $sala, $momento);
            $sala['status_consulta_publica'] = $status;
            $sala['motivo_status'] = $motivo;
        }
        unset($sala);

        if (!empty($filters['busca'])) {
            $busca = mb_strtolower((string) $filters['busca']);
            $salas = array_filter($salas, fn (array $s): bool =>
                str_contains(mb_strtolower((string) $s['nome']), $busca)
                || str_contains(mb_strtolower((string) ($s['codigo'] ?? '')), $busca)
                || str_contains(mb_strtolower((string) ($s['bloco'] ?? '')), $busca)
            );
        }
        if (!empty($filters['status'])) {
            $salas = array_filter($salas, fn (array $s): bool => $s['status_consulta_publica'] === $filters['status']);
        }
        if (!empty($filters['tipo_ambiente'])) {
            $salas = array_filter($salas, fn (array $s): bool => $s['tipo_ambiente'] === $filters['tipo_ambiente']);
        }

        return array_values($salas);
    }

    public function chavesDisponiveisParaRetirada(?array $user = null): array
    {
        return array_values(array_filter(
            $this->chavesParaRetirada($user),
            fn (array $sala): bool => !empty($sala['chave_retiravel'])
        ));
    }

    public function chavesParaRetirada(?array $user = null): array
    {
        $salas = $this->listDisponibilidade();
        $perfil = (string) ($user['perfil_nome'] ?? '');

        if ($user && !$this->perfilPodeVerTodasAsChaves($perfil)) {
            $salas = array_values(array_filter($salas, fn (array $s): bool => $this->usuarioAutorizadoParaChave((int) $s['id'], $user)));
        }

        foreach ($salas as &$sala) {
            $retiravel = ($sala['status_consulta_publica'] ?? '') === 'Fechada';
            $sala['chave_retiravel'] = $retiravel;
            $sala['chave_status'] = $retiravel ? 'disponivel' : 'indisponivel';
            $sala['chave_status_label'] = $retiravel ? 'disponivel' : 'indisponivel';
            $sala['chave_motivo'] = $retiravel ? 'Chave disponivel para retirada.' : ($sala['motivo_status'] ?? 'Chave indisponivel no momento.');
        }
        unset($sala);

        return comparableProfile($perfil) === comparableProfile('Diretor') ? $this->priorizarDiretoria($salas) : $salas;
    }

    public function chavePodeSerRetirada(int $salaId, ?array $user): bool
    {
        return $this->usuarioAutorizadoParaChave($salaId, $user) && $this->salaDisponivelParaRetirada($salaId);
    }

    private function usuarioAutorizadoParaChave(int $salaId, ?array $user): bool
    {
        if (!$user) {
            return false;
        }
        if ($this->perfilPodeVerTodasAsChaves((string) ($user['perfil_nome'] ?? ''))) {
            return true;
        }
        return (new PermissaoSala())->usuarioTemAcesso((int) $user['id'], $salaId);
    }

    private function perfilPodeVerTodasAsChaves(string $perfil): bool
    {
        $normalizado = comparableProfile($perfil);
        $perfis = array_map(fn (string $item): string => comparableProfile($item), [
            'Desenvolvedor',
            'Serviços Gerais',
            'Agente de Portaria',
            'Administrativo',
            'Diretor',
        ]);
        return in_array($normalizado, $perfis, true);
    }

    private function salaDisponivelParaRetirada(int $salaId): bool
    {
        $sala = $this->find($salaId);
        if (!$sala) {
            return false;
        }
        [$status] = $this->statusPublico($salaId, $sala, date('Y-m-d H:i:s'));
        return $status === 'Fechada';
    }

    private function priorizarDiretoria(array $salas): array
    {
        usort($salas, function (array $a, array $b): int {
            $prioridadeA = $this->ehDiretoria($a) ? 0 : 1;
            $prioridadeB = $this->ehDiretoria($b) ? 0 : 1;
            return $prioridadeA <=> $prioridadeB ?: strcasecmp((string) $a['nome'], (string) $b['nome']);
        });
        return $salas;
    }

    private function ehDiretoria(array $sala): bool
    {
        return mb_strtolower((string) ($sala['codigo'] ?? '')) === 'dir'
            || str_contains(mb_strtolower((string) ($sala['nome'] ?? '')), 'diretoria');
    }

    public function detalhes(int $id): ?array
    {
        return $this->find($id);
    }

    public function reservasDaSala(int $salaId): array
    {
        $stmt = $this->db()->prepare(
            'SELECT r.*, COALESCE(NULLIF(r.solicitante_nome_manual, ""), u.nome) AS usuario_nome, u.nome AS usuario_cadastrado_nome
             FROM reservas r
             JOIN usuarios u ON u.id = r.usuario_id
             WHERE r.sala_id = ?
             ORDER BY
                CASE WHEN r.fim_em >= NOW() THEN 0 ELSE 1 END,
                CASE WHEN r.fim_em >= NOW() THEN r.inicio_em END ASC,
                CASE WHEN r.fim_em < NOW() THEN r.inicio_em END DESC,
                r.id ASC'
        );
        $stmt->execute([$salaId]);
        return $stmt->fetchAll();
    }

    public function aulasDaSala(string $nomeSala): array
    {
        $stmt = $this->db()->prepare(
            'SELECT ra.*, u.nome AS professor_nome
             FROM reservas_aula ra
             JOIN usuarios u ON u.id = ra.professor_id
             WHERE ra.sala_nome = ?
             ORDER BY ra.dia_semana, ra.horario_inicio'
        );
        $stmt->execute([$nomeSala]);
        return $stmt->fetchAll();
    }

    public function movimentacoesDaSala(int $salaId): array
    {
        $stmt = $this->db()->prepare(
            'SELECT m.*, u.nome AS usuario_nome
             FROM movimentacoes m
             JOIN usuarios u ON u.id = m.usuario_id
             WHERE m.sala_id = ?
             ORDER BY COALESCE(m.devolucao_real_em, m.retirada_em, m.criado_em) DESC
             LIMIT 30'
        );
        $stmt->execute([$salaId]);
        return $stmt->fetchAll();
    }

    private function statusPublico(int $salaId, array $sala, string $momento): array
    {
        if ($sala['situacao'] === 'manutencao') {
            return ['Manutenção', 'Ambiente em manutenção.'];
        }
        if ($sala['situacao'] === 'bloqueada') {
            return ['Bloqueada', 'Ambiente bloqueado para uso.'];
        }

        $stmt = $this->db()->prepare('SELECT COUNT(*) FROM movimentacoes WHERE sala_id = ? AND situacao = "aberta"');
        $stmt->execute([$salaId]);
        if ((int) $stmt->fetchColumn() > 0) {
            return ['Aberta', 'Chave retirada no momento.'];
        }

        $stmt = $this->db()->prepare(
            'SELECT COUNT(*) FROM reservas
             WHERE sala_id = ? AND situacao IN ("pendente", "confirmada")
               AND inicio_em <= ? AND fim_em >= ?'
        );
        $stmt->execute([$salaId, $momento, $momento]);
        if ((int) $stmt->fetchColumn() > 0) {
            return ['Reservada', 'Reserva ativa para o horário consultado.'];
        }

        return ['Fechada', 'Sem chave retirada e sem reserva ativa.'];
    }
}
