<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

// Salas e ambientes, incluindo regras de disponibilidade pública e retirada de chave.
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
        $salas = $this->listDisponibilidade(['status' => 'Fechada']);
        if (!$user || isDeveloper() || in_array($user['perfil_nome'] ?? '', ['Serviços Gerais', 'Administrativo'], true)) {
            return $salas;
        }
        return array_values(array_filter($salas, fn (array $s): bool => $this->chavePodeSerRetirada((int) $s['id'], $user)));
    }

    public function chavePodeSerRetirada(int $salaId, ?array $user): bool
    {
        if (!$user) {
            return false;
        }
        if (isDeveloper() || in_array($user['perfil_nome'] ?? '', ['Serviços Gerais', 'Agente de Portaria', 'Administrativo'], true)) {
            return true;
        }
        return (new PermissaoSala())->usuarioTemAcesso((int) $user['id'], $salaId);
    }

    public function detalhes(int $id): ?array
    {
        return $this->find($id);
    }

    public function reservasDaSala(int $salaId): array
    {
        $stmt = $this->db()->prepare(
            'SELECT r.*, u.nome AS usuario_nome
             FROM reservas r
             JOIN usuarios u ON u.id = r.usuario_id
             WHERE r.sala_id = ?
             ORDER BY r.inicio_em DESC'
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
