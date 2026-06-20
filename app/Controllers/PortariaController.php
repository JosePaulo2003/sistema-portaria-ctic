<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\AdvertenciaChave;
use App\Models\BloqueioChave;
use App\Models\ConfiguracaoSistema;
use App\Models\Movimentacao;
use App\Models\PermissaoSala;
use App\Models\Sala;
use App\Models\User;

// Operações da portaria: fila de devolução, visitantes, permissões e histórico.
class PortariaController extends Controller
{
    public function index(): void
    {
        requireProfile('Agente de Portaria');
        $mov = new Movimentacao();
        $salas = (new Sala())->listDisponibilidade(['status' => 'Fechada']);
        $abertas = $mov->abertas();
        $this->view('portaria/index', [
            'title' => 'Portaria',
            'salas' => $salas,
            'abertas' => $abertas,
            'recentes' => $mov->historico(20),
            'chavesPendentes' => count(array_filter($abertas, fn ($m) => !empty($m['sala_id']))),
            'itensPendentes' => count(array_filter($abertas, fn ($m) => !empty($m['item_portaria_id']))),
        ]);
    }

    public function retiradas(): void
    {
        requireProfile('Agente de Portaria');
        $this->view('portaria/retiradas', [
            'title' => 'Retiradas',
            'movimentacoes' => (new Movimentacao())->abertas(),
            'usuarios' => (new User())->all('nome'),
        ]);
    }

    public function devolverChave(): void
    {
        requireProfile('Agente de Portaria');
        verifyCsrf();
        $this->devolver('devolucao_chave');
        redirect('/portaria/retiradas');
    }

    public function devolverItem(): void
    {
        requireProfile('Agente de Portaria');
        verifyCsrf();
        $this->devolver('devolucao_item');
        redirect('/portaria/retiradas');
    }

    public function permissoes(): void
    {
        requireProfile('Agente de Portaria');
        $this->view('portaria/permissoes', ['title' => 'Permissões', 'permissoes' => (new PermissaoSala())->withDetails()]);
    }

    public function visitantes(): void
    {
        requireProfile('Agente de Portaria');
        $this->view('portaria/visitantes', ['title' => 'Visitantes', 'visitantes' => (new User())->byProfile('Visitante')]);
    }

    public function salvarVisitante(): void
    {
        requireProfile('Agente de Portaria');
        verifyCsrf();
        $perfilId = \App\Core\Database::pdo()->query("SELECT id FROM perfis WHERE nome = 'Visitante'")->fetchColumn();
        (new User())->create([
            'nome' => trim((string) $_POST['nome']),
            'email' => trim((string) $_POST['email']),
            'senha_hash' => password_hash((string) ($_POST['senha'] ?: '12345678'), PASSWORD_DEFAULT),
            'perfil_id' => (int) $perfilId,
            'situacao' => $_POST['situacao'] ?? 'ativo',
        ]);
        flash('success', 'Visitante cadastrado.');
        redirect('/portaria/visitantes');
    }

    public function atualizarVisitante(): void
    {
        requireProfile('Agente de Portaria');
        verifyCsrf();
        $data = ['nome' => trim((string) $_POST['nome']), 'email' => trim((string) $_POST['email']), 'situacao' => $_POST['situacao'] ?? 'ativo'];
        if (!empty($_POST['senha'])) {
            $data['senha_hash'] = password_hash((string) $_POST['senha'], PASSWORD_DEFAULT);
        }
        (new User())->update((int) $_POST['id'], $data);
        flash('success', 'Visitante atualizado.');
        redirect('/portaria/visitantes');
    }

    public function excluirVisitante(): void
    {
        requireProfile('Agente de Portaria');
        verifyCsrf();
        $user = new User();
        if (!$user->deleteSafely((int) $_POST['id'])) {
            $user->anonymize((int) $_POST['id']);
        }
        flash('success', 'Visitante removido.');
        redirect('/portaria/visitantes');
    }

    public function salasHoje(): void
    {
        requireProfile('Agente de Portaria');
        $this->view('portaria/salas-hoje', ['title' => 'Salas Hoje', 'salas' => (new Sala())->listDisponibilidade($_GET)]);
    }

    public function historico(): void
    {
        requireProfile('Agente de Portaria');
        $this->view('portaria/historico', ['title' => 'Histórico', 'movimentacoes' => (new Movimentacao())->historico()]);
    }

    private function devolver(string $tipoDevolucao): void
    {
        $movModel = new Movimentacao();
        $mov = $movModel->find((int) $_POST['movimentacao_id']);
        if (!$mov || $mov['situacao'] !== 'aberta') {
            flash('error', 'Movimentação não encontrada ou já finalizada.');
            return;
        }

        $devolvidoPor = $_POST['devolvido_por_usuario_id'] ?? $mov['usuario_id'];
        $pessoaDiferente = $devolvidoPor === 'nao_cadastrada' || (int) $devolvidoPor !== (int) $mov['usuario_id'];
        $movModel->update((int) $mov['id'], [
            'tipo_movimentacao' => $tipoDevolucao,
            'situacao' => 'finalizada',
            'devolucao_real_em' => date('Y-m-d H:i:s'),
            'devolvido_por_usuario_id' => $devolvidoPor === 'nao_cadastrada' ? null : (int) $devolvidoPor,
            'registrado_por_usuario_id' => currentUser()['id'],
            'observacao' => $_POST['observacao'] ?? $mov['observacao'],
        ]);

        if ($pessoaDiferente && !empty($mov['sala_id'])) {
            $motivo = $devolvidoPor === 'nao_cadastrada' ? 'Devolução realizada por pessoa não cadastrada.' : 'Devolução realizada por pessoa diferente.';
            $adv = new AdvertenciaChave();
            $adv->create([
                'usuario_id' => $mov['usuario_id'],
                'movimentacao_id' => $mov['id'],
                'agente_portaria_id' => currentUser()['id'],
                'motivo' => $motivo,
                'observacao' => $_POST['observacao'] ?? null,
            ]);
            $this->criarBloqueioSeNecessario((int) $mov['usuario_id']);
        }

        flash('success', 'Devolução registrada.');
    }

    private function criarBloqueioSeNecessario(int $usuarioId): void
    {
        $bloqueios = new BloqueioChave();
        if ($bloqueios->ativoParaUsuario($usuarioId)) {
            return;
        }
        $advertencias = new AdvertenciaChave();
        if (!$advertencias->shouldCreateBlock($usuarioId, $bloqueios->latestAdvertenciaIdByUser($usuarioId))) {
            return;
        }
        $dias = max(1, (int) (new ConfiguracaoSistema())->getValue('dias_bloqueio_advertencia', '7'));
        $bloqueios->create([
            'usuario_id' => $usuarioId,
            'advertencia_id' => $advertencias->latestIdByUser($usuarioId),
            'inicio_em' => date('Y-m-d H:i:s'),
            'fim_em' => date('Y-m-d H:i:s', strtotime("+{$dias} days")),
            'situacao' => 'ativo',
        ]);
    }
}
