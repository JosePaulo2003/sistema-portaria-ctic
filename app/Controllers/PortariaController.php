<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\AdvertenciaChave;
use App\Models\BloqueioChave;
use App\Models\ConfiguracaoSistema;
use App\Models\ItemPortaria;
use App\Models\Movimentacao;
use App\Models\PermissaoItem;
use App\Models\PermissaoSala;
use App\Models\Reserva;
use App\Models\Sala;
use App\Models\User;

// Operações da portaria: fila de devolução, visitantes, permissões e histórico.
class PortariaController extends Controller
{
    public function index(): void
    {
        $this->salasHome('Agente de Portaria');
    }

    public function salas(): void
    {
        $this->exigirGestaoSalasItens();
        $this->view('secretario/salas', [
            'title' => 'Salas',
            'salas' => (new Sala())->all('nome'),
            'actionPrefix' => '/portaria/salas',
        ]);
    }

    public function salvarSala(): void
    {
        $this->exigirGestaoSalasItens();
        verifyCsrf();
        (new Sala())->create($this->salaData());
        audit('Salas', 'criacao', 'Sala cadastrada pela Portaria ou pelo perfil Tecnico.');
        flash('success', 'Sala cadastrada.');
        redirect('/portaria/salas');
    }

    public function atualizarSala(): void
    {
        $this->exigirGestaoSalasItens();
        verifyCsrf();
        (new Sala())->update((int) $_POST['id'], $this->salaData());
        audit('Salas', 'atualizacao', 'Sala atualizada pela Portaria ou pelo perfil Tecnico.', ['sala_id' => (int) $_POST['id']]);
        flash('success', 'Sala atualizada.');
        redirect('/portaria/salas');
    }

    public function excluirSala(): void
    {
        $this->exigirGestaoSalasItens();
        verifyCsrf();
        $salaId = (int) $_POST['id'];
        try {
            (new Sala())->delete($salaId);
        } catch (\Throwable) {
            (new Sala())->update($salaId, ['situacao' => 'bloqueada']);
        }
        audit('Salas', 'exclusao_ou_bloqueio', 'Sala removida ou bloqueada pela Portaria ou pelo perfil Tecnico.', ['sala_id' => $salaId]);
        flash('success', 'Sala removida ou bloqueada.');
        redirect('/portaria/salas');
    }

    public function itens(): void
    {
        $this->exigirGestaoSalasItens();
        $this->view('secretario/itens', [
            'title' => 'Itens',
            'itens' => (new ItemPortaria())->all('nome'),
            'actionPrefix' => '/portaria/itens',
        ]);
    }

    public function salvarItem(): void
    {
        $this->exigirGestaoSalasItens();
        verifyCsrf();
        (new ItemPortaria())->create($this->itemData());
        audit('Itens', 'criacao', 'Item cadastrado pela Portaria ou pelo perfil Tecnico.');
        flash('success', 'Item cadastrado.');
        redirect('/portaria/itens');
    }

    public function atualizarItem(): void
    {
        $this->exigirGestaoSalasItens();
        verifyCsrf();
        (new ItemPortaria())->update((int) $_POST['id'], $this->itemData());
        audit('Itens', 'atualizacao', 'Item atualizado pela Portaria ou pelo perfil Tecnico.', ['item_id' => (int) $_POST['id']]);
        flash('success', 'Item atualizado.');
        redirect('/portaria/itens');
    }

    public function excluirItem(): void
    {
        $this->exigirGestaoSalasItens();
        verifyCsrf();
        $itemId = (int) $_POST['id'];
        try {
            (new ItemPortaria())->delete($itemId);
        } catch (\Throwable) {
            (new ItemPortaria())->update($itemId, ['situacao' => 'indisponivel']);
        }
        audit('Itens', 'exclusao_ou_indisponibilidade', 'Item removido ou indisponibilizado pela Portaria ou pelo perfil Tecnico.', ['item_id' => $itemId]);
        flash('success', 'Item removido ou indisponibilizado.');
        redirect('/portaria/itens');
    }

    public function retiradas(): void
    {
        requireProfile('Agente de Portaria');
        $this->view('portaria/retiradas', [
            'title' => 'Retiradas',
            'movimentacoes' => (new Movimentacao())->abertas(),
            'usuarios' => (new User())->all('nome'),
            'salas' => (new Sala())->chavesDisponiveisParaRetirada(currentUser()),
        ]);
    }

    public function registrarRetiradaChave(): void
    {
        requireProfile('Agente de Portaria');
        verifyCsrf();

        $usuarioId = (int) ($_POST['usuario_id'] ?? 0);
        $salaId = (int) ($_POST['sala_id'] ?? 0);
        $usuario = (new User())->find($usuarioId);

        if (!$usuario || ($usuario['situacao'] ?? '') !== 'ativo') {
            flash('error', 'Selecione um usuario ativo para registrar a retirada.');
            redirect('/portaria/retiradas');
        }
        $bloqueio = (new BloqueioChave())->ativoParaUsuario($usuarioId);
        if ($bloqueio) {
            flash('error', 'Este usuario esta temporariamente bloqueado para retirar chaves ate ' . date('d/m/Y H:i', strtotime($bloqueio['fim_em'])) . '.');
            redirect('/portaria/retiradas');
        }
        if ($salaId <= 0 || !(new Sala())->chavePodeSerRetirada($salaId, currentUser())) {
            flash('error', 'Esta chave nao esta disponivel para retirada.');
            redirect('/portaria/retiradas');
        }

        (new Movimentacao())->create([
            'usuario_id' => $usuarioId,
            'sala_id' => $salaId,
            'tipo_movimentacao' => 'retirada_chave',
            'situacao' => 'aberta',
            'retirada_em' => date('Y-m-d H:i:s'),
            'devolucao_prevista_em' => null,
            'registrado_por_usuario_id' => currentUser()['id'],
            'observacao' => $_POST['observacao'] ?? null,
        ]);

        audit('Portaria', 'retirada_chave_terceiro', 'Retirada de chave registrada pela portaria.', [
            'usuario_id' => $usuarioId,
            'sala_id' => $salaId,
        ]);
        flash('success', 'Retirada de chave registrada.');
        redirect('/portaria/retiradas');
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
        $usuarioId = max(0, (int) ($_GET['usuario_id'] ?? 0));
        $editarId = max(0, (int) ($_GET['editar_id'] ?? 0));
        $permissaoModel = new PermissaoSala();
        $userModel = new User();
        $userModel->purgeExpiredVisitors();
        $this->view('portaria/permissoes', [
            'title' => 'Permissões',
            'permissoesSalas' => $permissaoModel->withDetails($usuarioId ?: null),
            'permissoesItens' => (new PermissaoItem())->withDetails($usuarioId ?: null),
            'usuarios' => $userModel->allWithProfile(),
            'salas' => (new Sala())->all('nome'),
            'usuarioFiltro' => $usuarioId,
            'permissaoEdicao' => $editarId > 0 ? $permissaoModel->findWithDetails($editarId) : null,
        ]);
    }

    public function salvarPermissaoChave(): void
    {
        requireProfile('Agente de Portaria');
        verifyCsrf();

        $usuarioId = (int) ($_POST['usuario_id'] ?? 0);
        $salaIds = array_values(array_unique(array_filter(
            array_map('intval', (array) ($_POST['sala_ids'] ?? [])),
            static fn (int $salaId): bool => $salaId > 0
        )));
        $acessoTotal = !empty($_POST['acesso_total']);
        $nuncaExpirar = !empty($_POST['nunca_expirar']);
        $usuario = (new User())->find($usuarioId);

        if (!$usuario || ($usuario['situacao'] ?? '') !== 'ativo') {
            flash('error', 'Selecione um usuario ativo.');
            redirect('/portaria/permissoes');
        }
        if (!$acessoTotal && !$salaIds) {
            flash('error', 'Selecione pelo menos uma chave ou marque acesso total.');
            redirect('/portaria/permissoes');
        }
        $salasValidas = [];
        if (!$acessoTotal) {
            $salaModel = new Sala();
            foreach ($salaIds as $salaId) {
                if (!$salaModel->find($salaId)) {
                    flash('error', 'Uma das chaves selecionadas nao e valida.');
                    redirect('/portaria/permissoes');
                }
                $salasValidas[] = $salaId;
            }
        }

        $inicio = $this->criarDataHoraPermissao('inicio_autorizacao');
        $expira = $nuncaExpirar ? null : $this->criarDataHoraPermissao('expira_em');
        if (!$inicio) {
            flash('error', 'Informe um inicio de autorizacao valido.');
            redirect('/portaria/permissoes');
        }
        if (!$nuncaExpirar && !$expira) {
            flash('error', 'Informe uma data de expiracao valida.');
            redirect('/portaria/permissoes');
        }
        if ($expira && $expira <= new \DateTimeImmutable()) {
            flash('error', 'A expiracao da autorizacao precisa estar no futuro.');
            redirect('/portaria/permissoes');
        }
        if ($expira && $expira < $inicio) {
            flash('error', 'A expiracao nao pode ser anterior ao inicio.');
            redirect('/portaria/permissoes');
        }
        $this->validarLimitePermissaoUsuario($usuario, $expira, '/portaria/permissoes');

        $salasParaCadastrar = $acessoTotal ? [null] : $salasValidas;
        $permissaoModel = new PermissaoSala();
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            foreach ($salasParaCadastrar as $salaId) {
                $permissaoModel->create([
                    'usuario_id' => $usuarioId,
                    'sala_id' => $salaId,
                    'acesso_total' => $acessoTotal ? 1 : 0,
                    'autorizado_por' => currentUser()['id'],
                    'inicio_autorizacao' => $inicio->format('Y-m-d H:i:s'),
                    'expira_em' => $expira?->format('Y-m-d H:i:s'),
                    'dias_semana' => !empty($_POST['dias_semana']) ? implode(', ', (array) $_POST['dias_semana']) : null,
                    'observacao' => $_POST['observacao'] ?? null,
                    'situacao' => 'ativa',
                ]);
            }
            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            systemLog('error', 'Portaria', 'Falha ao cadastrar permissoes de varias salas.', [
                'usuario_id' => $usuarioId,
                'salas_ids' => $salasValidas,
                'erro' => $exception->getMessage(),
            ]);
            flash('error', 'Nao foi possivel cadastrar as permissoes. Nenhuma alteracao foi salva.');
            redirect('/portaria/permissoes');
        }

        audit('Portaria', 'permissao_chave', 'Permissao de chave cadastrada pela portaria.', [
            'usuario_id' => $usuarioId,
            'salas_ids' => $acessoTotal ? [] : $salasValidas,
            'quantidade_permissoes' => count($salasParaCadastrar),
            'acesso_total' => $acessoTotal,
            'nunca_expirar' => $nuncaExpirar,
        ]);
        flash('success', count($salasParaCadastrar) > 1
            ? count($salasParaCadastrar) . ' permissoes de chave cadastradas.'
            : 'Permissao de chave cadastrada.');
        redirect('/portaria/permissoes');
    }

    public function atualizarPermissaoChave(): void
    {
        requireProfile('Agente de Portaria');
        verifyCsrf();

        $permissaoId = (int) ($_POST['id'] ?? 0);
        $permissaoModel = new PermissaoSala();
        $permissaoAtual = $permissaoModel->find($permissaoId);
        if (!$permissaoAtual) {
            flash('error', 'Permissao nao encontrada.');
            redirect('/portaria/permissoes');
        }

        // Uma permissao em edicao continua pertencendo ao usuario original.
        $usuarioId = (int) $permissaoAtual['usuario_id'];
        $salaId = (int) ($_POST['sala_id'] ?? 0);
        $acessoTotal = !empty($_POST['acesso_total']);
        $nuncaExpirar = !empty($_POST['nunca_expirar']);
        $usuario = (new User())->find($usuarioId);
        if (!$usuario || ($usuario['situacao'] ?? '') !== 'ativo') {
            flash('error', 'Selecione um usuario ativo.');
            redirect('/portaria/permissoes?editar_id=' . $permissaoId);
        }
        if (!$acessoTotal && ($salaId <= 0 || !(new Sala())->find($salaId))) {
            flash('error', 'Selecione uma chave valida ou marque acesso total.');
            redirect('/portaria/permissoes?editar_id=' . $permissaoId);
        }

        $inicio = $this->criarDataHoraPermissao('inicio_autorizacao');
        $expira = $nuncaExpirar ? null : $this->criarDataHoraPermissao('expira_em');
        if (!$inicio || (!$nuncaExpirar && !$expira)) {
            flash('error', 'Informe inicio e expiracao validos ou marque Nunca expirar.');
            redirect('/portaria/permissoes?editar_id=' . $permissaoId);
        }
        if ($expira && $expira < new \DateTimeImmutable()) {
            flash('error', 'A expiracao da permissao precisa estar no futuro.');
            redirect('/portaria/permissoes?editar_id=' . $permissaoId);
        }
        if ($expira && $expira < $inicio) {
            flash('error', 'A expiracao nao pode ser anterior ao inicio.');
            redirect('/portaria/permissoes?editar_id=' . $permissaoId);
        }
        $this->validarLimitePermissaoUsuario($usuario, $expira, '/portaria/permissoes?editar_id=' . $permissaoId);

        $situacao = (string) ($_POST['situacao'] ?? 'ativa');
        if (!in_array($situacao, ['ativa', 'revogada', 'expirada'], true)) {
            $situacao = 'ativa';
        }

        if ($situacao === 'revogada') {
            $permissaoDetalhada = $permissaoModel->findWithDetails($permissaoId) ?? $permissaoAtual;
            $this->registrarAuditoriaRevogacaoPermissao($permissaoDetalhada, 'edicao');
            $permissaoModel->delete($permissaoId);
            flash('success', 'Permissao revogada e removida da lista.');
            redirect('/portaria/permissoes?usuario_id=' . $usuarioId);
        }

        $permissaoModel->update($permissaoId, [
            'usuario_id' => $usuarioId,
            'sala_id' => $acessoTotal ? null : $salaId,
            'acesso_total' => $acessoTotal ? 1 : 0,
            'inicio_autorizacao' => $inicio->format('Y-m-d H:i:s'),
            'expira_em' => $expira?->format('Y-m-d H:i:s'),
            'dias_semana' => !empty($_POST['dias_semana']) ? implode(', ', (array) $_POST['dias_semana']) : null,
            'observacao' => $_POST['observacao'] ?? null,
            'situacao' => $situacao,
        ]);
        audit('Portaria', 'permissao_chave_atualizada', 'Permissao de chave atualizada pela portaria.', [
            'permissao_id' => $permissaoId,
            'usuario_id' => $usuarioId,
        ]);
        flash('success', 'Permissao atualizada.');
        redirect('/portaria/permissoes?usuario_id=' . $usuarioId);
    }

    public function revogarPermissaoChave(): void
    {
        requireProfile('Agente de Portaria');
        verifyCsrf();

        $permissaoId = (int) ($_POST['id'] ?? 0);
        $permissaoModel = new PermissaoSala();
        $permissao = $permissaoModel->findWithDetails($permissaoId);
        if (!$permissao) {
            flash('error', 'Permissao nao encontrada.');
            redirect('/portaria/permissoes');
        }

        $usuarioId = (int) ($permissao['usuario_id'] ?? 0);
        $this->registrarAuditoriaRevogacaoPermissao($permissao, 'botao_revogar');
        $permissaoModel->delete($permissaoId);

        flash('success', 'Permissao revogada e removida da lista.');
        redirect('/portaria/permissoes' . ($usuarioId > 0 ? '?usuario_id=' . $usuarioId : ''));
    }

    public function limparPermissoesRevogadas(): void
    {
        requireProfile('Agente de Portaria');
        verifyCsrf();

        $usuarioId = max(0, (int) ($_POST['usuario_id'] ?? 0));
        $quantidade = (new PermissaoSala())->excluirRevogadas($usuarioId ?: null);

        audit('Portaria', 'permissoes_chaves_revogadas_limpas', 'Permissoes de chave revogadas removidas pela portaria.', [
            'usuario_id' => $usuarioId ?: null,
            'quantidade' => $quantidade,
        ]);

        flash(
            'success',
            $quantidade === 1
                ? '1 permissao revogada foi removida.'
                : $quantidade . ' permissoes revogadas foram removidas.'
        );

        redirect('/portaria/permissoes' . ($usuarioId > 0 ? '?usuario_id=' . $usuarioId : ''));
    }

    private function registrarAuditoriaRevogacaoPermissao(array $permissao, string $origem): void
    {
        audit('Portaria', 'permissao_chave_revogada', 'Permissao de chave revogada e removida da lista pela portaria.', [
            'permissao_id' => (int) ($permissao['id'] ?? 0),
            'usuario_id' => (int) ($permissao['usuario_id'] ?? 0),
            'usuario_nome' => $permissao['usuario_nome'] ?? null,
            'sala_id' => isset($permissao['sala_id']) ? (int) $permissao['sala_id'] : null,
            'sala_nome' => $permissao['sala_nome'] ?? null,
            'acesso_total' => !empty($permissao['acesso_total']),
            'inicio_autorizacao' => $permissao['inicio_autorizacao'] ?? null,
            'expira_em' => $permissao['expira_em'] ?? null,
            'dias_semana' => $permissao['dias_semana'] ?? null,
            'observacao' => $permissao['observacao'] ?? null,
            'autorizado_por' => isset($permissao['autorizado_por']) ? (int) $permissao['autorizado_por'] : null,
            'autorizador_nome' => $permissao['autorizador_nome'] ?? null,
            'criado_em' => $permissao['criado_em'] ?? null,
            'origem_revogacao' => $origem,
        ]);
    }

    public function vinculosBolsistas(): void
    {
        requireProfile(['Agente de Portaria', 'Desenvolvedor']);
        $this->view('usuarios/vinculos-bolsistas', [
            'title' => 'Vinculos de Bolsistas',
            'bolsistas' => (new User())->bolsistasComProfessor(),
            'professores' => (new User())->professoresAtivos(),
            'retorno' => $this->retornoVinculosBolsistas(),
        ]);
    }

    public function salvarVinculoBolsista(): void
    {
        requireProfile(['Agente de Portaria', 'Desenvolvedor']);
        verifyCsrf();

        $bolsistaId = (int) ($_POST['bolsista_id'] ?? 0);
        $professorId = (int) ($_POST['professor_id'] ?? 0);
        $bolsista = (new User())->findWithProfile($bolsistaId);
        $professor = (new User())->findWithProfile($professorId);
        $retorno = $this->retornoVinculosBolsistas();

        // Garante que apenas bolsistas/estagiarios sejam vinculados a professores ativos.
        $perfilBolsista = comparableProfile((string) ($bolsista['perfil_nome'] ?? ''));
        if (!$bolsista || !in_array($perfilBolsista, [comparableProfile('Aluno Bolsista'), comparableProfile('Estagiario')], true)) {
            flash('error', 'Selecione um bolsista ou estagiario valido.');
            redirect($retorno);
        }
        if (!$professor || ($professor['perfil_nome'] ?? '') !== 'Professor' || ($professor['situacao'] ?? '') !== 'ativo') {
            flash('error', 'Selecione um professor ativo.');
            redirect($retorno);
        }

        (new User())->update($bolsistaId, ['professor_indicador_id' => $professorId]);
        audit('Usuarios', 'vinculo_bolsista_professor', 'Bolsista vinculado a professor.', [
            'bolsista_id' => $bolsistaId,
            'professor_id' => $professorId,
        ]);
        flash('success', 'Vinculo atualizado.');
        redirect($retorno);
    }

    private function retornoVinculosBolsistas(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
        return str_contains($uri, '/desenvolvedor/') ? '/desenvolvedor/vinculos-bolsistas' : '/portaria/vinculos-bolsistas';
    }

    public function reservas(): void
    {
        requireProfile('Agente de Portaria');
        $this->view('portaria/reservas', [
            'title' => 'Reservas',
            'reservas' => (new Reserva())->withDetails(),
            'salas' => (new Sala())->all('nome'),
            'usuarios' => (new User())->ativosComPermissaoSolicitarReserva(),
        ]);
    }

    public function salvarReserva(): void
    {
        requireProfile('Agente de Portaria');
        verifyCsrf();
        $ocorrencias = $this->validarReservaPortaria();

        $usuarioId = (int) ($_POST['usuario_id'] ?? 0);
        $solicitanteManual = mb_substr(trim((string) ($_POST['solicitante_nome_manual'] ?? '')), 0, 180);
        $usuarioReservaId = $usuarioId > 0 ? $usuarioId : (int) currentUser()['id'];
        $reservaModel = new Reserva();
        $pdo = Database::pdo();

        try {
            $pdo->beginTransaction();
            foreach ($ocorrencias as $ocorrencia) {
                $reservaModel->create([
                    'usuario_id' => $usuarioReservaId,
                    'solicitante_nome_manual' => $usuarioId > 0 ? null : $solicitanteManual,
                    'sala_id' => (int) $_POST['sala_id'],
                    'titulo' => trim((string) $_POST['titulo']),
                    'finalidade' => $_POST['finalidade'] ?? null,
                    'tipo_reserva' => 'sala',
                    'inicio_em' => $ocorrencia['inicio_em'],
                    'fim_em' => $ocorrencia['fim_em'],
                    'situacao' => 'confirmada',
                ]);
            }
            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            flash('error', 'Nao foi possivel cadastrar a reserva.');
            redirect('/portaria/reservas');
        }

        audit('Reservas', 'criacao_portaria', 'Reserva cadastrada pela portaria.', [
            'quantidade' => count($ocorrencias),
            'usuario_solicitante_id' => $usuarioId > 0 ? $usuarioId : null,
            'solicitante_nome_manual' => $usuarioId > 0 ? null : $solicitanteManual,
        ]);
        flash('success', count($ocorrencias) === 1 ? 'Reserva cadastrada e confirmada.' : count($ocorrencias) . ' reservas cadastradas e confirmadas.');
        redirect('/portaria/reservas');
    }

    public function atualizarReserva(): void
    {
        requireProfile('Agente de Portaria');
        verifyCsrf();
        $reservaModel = new Reserva();
        $reserva = $reservaModel->find((int) ($_POST['id'] ?? 0));
        if (!$reserva || $reserva['situacao'] !== 'pendente') {
            flash('error', 'Reserva não encontrada ou já analisada.');
            redirect('/portaria/reservas');
        }

        if (($_POST['acao'] ?? '') === 'aprovar') {
            if (!$reservaModel->podeAprovar($reserva)) {
                flash('error', 'Não foi possível aprovar: existe conflito ou a sala não está disponível.');
                redirect('/portaria/reservas');
            }
            $reservaModel->update((int) $reserva['id'], ['situacao' => 'confirmada']);
            flash('success', 'Reserva aprovada.');
            redirect('/portaria/reservas');
        }

        $reservaModel->update((int) $reserva['id'], ['situacao' => 'cancelada']);
        flash('success', 'Reserva recusada.');
        redirect('/portaria/reservas');
    }

    public function excluirReservaHistorico(): void
    {
        requireProfile(['Agente de Portaria', 'Desenvolvedor']);
        verifyCsrf();

        $reservaId = (int) ($_POST['id'] ?? 0);
        $reserva = new Reserva();
        if (!$reserva->find($reservaId)) {
            flash('error', 'Reserva nao encontrada.');
            redirect('/portaria/reservas');
        }

        $reserva->delete($reservaId);
        audit('Reservas', 'exclusao_historico_portaria', 'Historico de reserva removido pela Portaria ou pelo acesso tecnico.', [
            'reserva_id' => $reservaId,
        ]);
        flash('success', 'Historico de reserva apagado.');
        redirect('/portaria/reservas');
    }

    private function validarReservaPortaria(): array
    {
        $inicio = $this->criarDataHora((string) ($_POST['inicio_em'] ?? ''));
        $fim = $this->criarDataHora((string) ($_POST['fim_em'] ?? ''));
        $salaId = (int) ($_POST['sala_id'] ?? 0);
        $usuarioId = (int) ($_POST['usuario_id'] ?? 0);
        $solicitanteManual = trim((string) ($_POST['solicitante_nome_manual'] ?? ''));
        $recorrenciaFim = trim((string) ($_POST['recorrencia_fim'] ?? ''));
        $diasSemana = $this->diasSemanaSelecionados((array) ($_POST['dias_semana'] ?? []));

        if (!$inicio || !$fim || $salaId <= 0) {
            flash('error', 'Informe sala, data e horario validos para cadastrar a reserva.');
            redirect('/portaria/reservas');
        }
        if ($usuarioId <= 0 && $solicitanteManual === '') {
            flash('error', 'Selecione um solicitante autorizado ou informe o nome da pessoa sem cadastro.');
            redirect('/portaria/reservas');
        }
        if ($usuarioId > 0 && !(new User())->podeSolicitarReserva($usuarioId)) {
            flash('error', 'Selecione um usuario ativo com permissao para solicitar reserva.');
            redirect('/portaria/reservas');
        }
        if ($inicio < new \DateTimeImmutable()) {
            flash('error', 'Nao e possivel cadastrar reserva com data ou horario anterior ao momento atual.');
            redirect('/portaria/reservas');
        }
        if ($fim <= $inicio) {
            flash('error', 'O fim da reserva precisa ser posterior ao inicio.');
            redirect('/portaria/reservas');
        }
        if ($recorrenciaFim !== '' && $inicio->format('Y-m-d') !== $fim->format('Y-m-d')) {
            flash('error', 'Para repetir por periodo, informe inicio e fim no mesmo dia.');
            redirect('/portaria/reservas');
        }
        if ($recorrenciaFim !== '' && !$diasSemana) {
            flash('error', 'Selecione ao menos um dia da semana para repetir a reserva.');
            redirect('/portaria/reservas');
        }

        $ocorrencias = $recorrenciaFim !== ''
            ? $this->ocorrenciasReservaPortaria($inicio, $fim, $recorrenciaFim, $diasSemana)
            : [[
                'inicio_em' => $inicio->format('Y-m-d H:i:s'),
                'fim_em' => $fim->format('Y-m-d H:i:s'),
            ]];

        if (!$ocorrencias) {
            flash('error', 'O periodo informado nao gerou nenhuma reserva.');
            redirect('/portaria/reservas');
        }
        if (count($ocorrencias) > 120) {
            flash('error', 'O periodo gerou reservas demais. Reduza o intervalo.');
            redirect('/portaria/reservas');
        }

        foreach ($ocorrencias as $ocorrencia) {
            $inicioOcorrencia = new \DateTimeImmutable($ocorrencia['inicio_em']);
            $fimOcorrencia = new \DateTimeImmutable($ocorrencia['fim_em']);
            if (!$this->salaDisponivelParaReserva($salaId, $inicioOcorrencia, $fimOcorrencia)) {
                flash('error', 'Esta sala nao esta disponivel em ' . $inicioOcorrencia->format('d/m/Y H:i') . '.');
                redirect('/portaria/reservas');
            }
        }

        return $ocorrencias;
    }

    private function salaDisponivelParaReserva(int $salaId, \DateTimeImmutable $inicio, \DateTimeImmutable $fim): bool
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT situacao FROM salas WHERE id = ? LIMIT 1');
        $stmt->execute([$salaId]);
        $situacao = $stmt->fetchColumn();
        if (!in_array($situacao, ['disponivel', 'fechada'], true)) {
            return false;
        }

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM movimentacoes WHERE sala_id = ? AND situacao = "aberta"');
        $stmt->execute([$salaId]);
        if ((int) $stmt->fetchColumn() > 0) {
            return false;
        }

        return !(new Reserva())->hasConflict(
            $salaId,
            $inicio->format('Y-m-d H:i:s'),
            $fim->format('Y-m-d H:i:s')
        );
    }

    private function criarDataHora(string $valor): ?\DateTimeImmutable
    {
        return \parseDateTimeInput($valor);
    }

    private function criarDataHoraPermissao(string $prefixo): ?\DateTimeImmutable
    {
        $data = trim((string) ($_POST[$prefixo . '_data'] ?? ''));
        $horaTexto = trim((string) ($_POST[$prefixo . '_hora'] ?? ''));
        $minutoTexto = trim((string) ($_POST[$prefixo . '_minuto'] ?? ''));

        if (!ctype_digit($horaTexto) || !ctype_digit($minutoTexto)) {
            return null;
        }

        $hora = (int) $horaTexto;
        $minuto = (int) $minutoTexto;
        if ($hora < 0 || $hora > 23 || $minuto < 0 || $minuto > 59) {
            return null;
        }

        return \parseDateTimeInput(sprintf('%s %02d:%02d', $data, $hora, $minuto));
    }

    private function diasSemanaSelecionados(array $dias): array
    {
        $dias = array_map('intval', $dias);
        $dias = array_values(array_unique(array_filter($dias, fn (int $dia): bool => $dia >= 1 && $dia <= 7)));
        sort($dias);
        return $dias;
    }

    private function ocorrenciasReservaPortaria(\DateTimeImmutable $inicio, \DateTimeImmutable $fim, string $recorrenciaFim, array $diasSemana): array
    {
        $dataFim = \DateTimeImmutable::createFromFormat('!Y-m-d', $recorrenciaFim);
        if (!$dataFim || $dataFim < $inicio->setTime(0, 0)) {
            flash('error', 'Informe uma data final de repeticao valida.');
            redirect('/portaria/reservas');
        }

        $ocorrencias = [];
        $cursor = $inicio->setTime(0, 0);
        $limite = $dataFim->setTime(0, 0);

        while ($cursor <= $limite) {
            if (in_array((int) $cursor->format('N'), $diasSemana, true)) {
                $inicioOcorrencia = $cursor->setTime((int) $inicio->format('H'), (int) $inicio->format('i'));
                $fimOcorrencia = $cursor->setTime((int) $fim->format('H'), (int) $fim->format('i'));

                if ($inicioOcorrencia < new \DateTimeImmutable()) {
                    flash('error', 'O periodo gera reserva com data ou horario anterior ao momento atual.');
                    redirect('/portaria/reservas');
                }

                $ocorrencias[] = [
                    'inicio_em' => $inicioOcorrencia->format('Y-m-d H:i:s'),
                    'fim_em' => $fimOcorrencia->format('Y-m-d H:i:s'),
                ];
            }
            $cursor = $cursor->modify('+1 day');
        }

        return $ocorrencias;
    }

    public function visitantes(): void
    {
        requireProfile('Agente de Portaria');
        $user = new User();
        $user->purgeExpiredVisitors();
        $this->view('portaria/visitantes', ['title' => 'Visitantes', 'visitantes' => $user->temporaryVisitors()]);
    }

    public function salvarVisitante(): void
    {
        requireProfile('Agente de Portaria');
        verifyCsrf();
        $senha = trim((string) ($_POST['senha'] ?? ''));
        if ($senha === '') {
            flash('error', 'Informe uma senha inicial para o visitante.');
            redirect('/portaria/visitantes');
        }
        $expira = $this->validarExpiracaoVisitante();
        $perfilId = \App\Core\Database::pdo()->query("SELECT id FROM perfis WHERE nome = 'Visitante'")->fetchColumn();
        (new User())->create([
            'nome' => trim((string) $_POST['nome']),
            'email' => trim((string) $_POST['email']),
            'senha_hash' => password_hash($senha, PASSWORD_DEFAULT),
            'perfil_id' => (int) $perfilId,
            'situacao' => $_POST['situacao'] ?? 'ativo',
            'acesso_expira_em' => $expira->format('Y-m-d H:i:s'),
        ]);
        flash('success', 'Visitante cadastrado.');
        redirect('/portaria/visitantes');
    }

    public function atualizarVisitante(): void
    {
        requireProfile('Agente de Portaria');
        verifyCsrf();
        $user = new User();
        $visitanteId = (int) ($_POST['id'] ?? 0);
        if (!$user->isVisitor($visitanteId)) {
            flash('error', 'Visitante nao encontrado.');
            redirect('/portaria/visitantes');
        }
        $expira = $this->validarExpiracaoVisitante();
        $data = ['nome' => trim((string) $_POST['nome']), 'email' => trim((string) $_POST['email']), 'situacao' => $_POST['situacao'] ?? 'ativo', 'acesso_expira_em' => $expira->format('Y-m-d H:i:s')];
        if (!empty($_POST['senha'])) {
            $data['senha_hash'] = password_hash((string) $_POST['senha'], PASSWORD_DEFAULT);
        }
        $user->update($visitanteId, $data);
        flash('success', 'Visitante atualizado.');
        redirect('/portaria/visitantes');
    }

    public function excluirVisitante(): void
    {
        requireProfile('Agente de Portaria');
        verifyCsrf();
        $user = new User();
        $visitanteId = (int) ($_POST['id'] ?? 0);
        if (!$user->removeTemporaryVisitor($visitanteId)) {
            flash('error', 'Visitante nao encontrado.');
            redirect('/portaria/visitantes');
        }
        flash('success', 'Visitante removido.');
        redirect('/portaria/visitantes');
    }

    private function validarExpiracaoVisitante(): \DateTimeImmutable
    {
        $expira = $this->criarDataHora((string) ($_POST['acesso_expira_em'] ?? ''));
        if (!$expira || $expira <= new \DateTimeImmutable()) {
            flash('error', 'Informe uma data e hora futura para o fim do acesso temporario.');
            redirect('/portaria/visitantes');
        }
        return $expira;
    }

    private function validarLimitePermissaoUsuario(array $usuario, ?\DateTimeImmutable $expira, string $retorno): void
    {
        $limiteAcesso = $this->criarDataHora((string) ($usuario['acesso_expira_em'] ?? ''));
        if ($limiteAcesso && (!$expira || $expira > $limiteAcesso)) {
            flash('error', $expira
                ? 'A permissao nao pode expirar depois do acesso temporario do usuario.'
                : 'Nunca expirar nao pode ser usado para uma conta temporaria.');
            redirect($retorno);
        }
    }

    public function salasHoje(): void
    {
        requireProfile('Agente de Portaria');
        $this->view('portaria/salas-hoje', ['title' => 'Salas Hoje', 'salas' => (new Sala())->listDisponibilidade($_GET)]);
    }

    public function historico(): void
    {
        requireProfile('Agente de Portaria');
        $this->view('portaria/historico', [
            'title' => 'Historico',
            'movimentacoes' => (new Movimentacao())->historicoFiltrado($_GET),
            'filtros' => $_GET,
        ]);
        return;
    }

    public function relatorioMovimentacoes(): void
    {
        requireProfile('Agente de Portaria');

        $dataTexto = trim((string) ($_GET['data'] ?? date('Y-m-d')));
        $data = \DateTimeImmutable::createFromFormat('!Y-m-d', $dataTexto);
        if (!$data || $data->format('Y-m-d') !== $dataTexto) {
            $data = new \DateTimeImmutable('today');
            $dataTexto = $data->format('Y-m-d');
        }

        $validarHora = static function ($valor, string $padrao): string {
            $hora = trim((string) $valor);
            return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $hora) ? $hora : $padrao;
        };
        $horaInicio = $validarHora($_GET['hora_inicio'] ?? '', '00:00');
        $horaFim = $validarHora($_GET['hora_fim'] ?? '', '23:59');
        $erroPeriodo = $horaInicio > $horaFim
            ? 'A hora final precisa ser igual ou posterior à hora inicial.'
            : '';

        $salas = (new Sala())->all('nome');
        $salaId = max(0, (int) ($_GET['sala_id'] ?? 0));
        $salaSelecionada = null;
        foreach ($salas as $sala) {
            if ((int) $sala['id'] === $salaId) {
                $salaSelecionada = $sala;
                break;
            }
        }
        if ($salaId > 0 && !$salaSelecionada) {
            $salaId = 0;
        }

        $inicioPeriodo = $dataTexto . ' ' . $horaInicio . ':00';
        $fimPeriodo = $dataTexto . ' ' . $horaFim . ':59';
        $movimentacoes = $erroPeriodo === ''
            ? (new Movimentacao())->relatorioSalasPortaria($inicioPeriodo, $fimPeriodo, $salaId > 0 ? $salaId : null)
            : [];

        $salasUnicas = [];
        $usuariosUnicos = [];
        $quantidadeAcoes = 0;
        foreach ($movimentacoes as &$movimentacao) {
            $acoes = [];
            $retirada = (string) ($movimentacao['retirada_em'] ?? $movimentacao['criado_em'] ?? '');
            $devolucao = (string) ($movimentacao['devolucao_real_em'] ?? '');
            if ($retirada >= $inicioPeriodo && $retirada <= $fimPeriodo) {
                $acoes[] = 'Retirada';
            }
            if ($devolucao !== '' && $devolucao >= $inicioPeriodo && $devolucao <= $fimPeriodo) {
                $acoes[] = 'Devolução';
            }
            $movimentacao['acoes_periodo'] = $acoes ? implode(' e ', $acoes) : 'Movimentação registrada';
            $quantidadeAcoes += max(1, count($acoes));
            $salasUnicas[(int) $movimentacao['sala_id']] = true;
            $usuariosUnicos[(int) $movimentacao['usuario_id']] = true;
        }
        unset($movimentacao);

        $this->view('portaria/relatorio-movimentacoes', [
            'title' => 'Relatório de Movimentações',
            'filtros' => [
                'data' => $dataTexto,
                'hora_inicio' => $horaInicio,
                'hora_fim' => $horaFim,
                'sala_id' => $salaId,
            ],
            'erroPeriodo' => $erroPeriodo,
            'salas' => $salas,
            'salaSelecionadaNome' => $salaSelecionada['nome'] ?? 'Todas as salas',
            'movimentacoes' => $movimentacoes,
            'quantidadeAcoes' => $quantidadeAcoes,
            'quantidadeSalas' => count($salasUnicas),
            'quantidadeUsuarios' => count($usuariosUnicos),
            'inicioPeriodo' => $inicioPeriodo,
            'fimPeriodo' => $fimPeriodo,
            'geradoEm' => date('Y-m-d H:i:s'),
        ]);
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

    private function exigirGestaoSalasItens(): void
    {
        requireProfile(['Agente de Portaria', 'Tecnico']);
    }

    private function salaData(): array
    {
        return [
            'nome' => trim((string) ($_POST['nome'] ?? '')),
            'codigo' => !empty($_POST['codigo']) ? trim((string) $_POST['codigo']) : null,
            'bloco' => !empty($_POST['bloco']) ? trim((string) $_POST['bloco']) : null,
            'capacidade' => ($_POST['capacidade'] ?? '') !== '' ? max(0, (int) $_POST['capacidade']) : null,
            'tipo_ambiente' => $_POST['tipo_ambiente'] ?? 'institucional',
            'situacao' => $_POST['situacao'] ?? 'disponivel',
            'descricao' => !empty($_POST['descricao']) ? trim((string) $_POST['descricao']) : null,
        ];
    }

    private function itemData(): array
    {
        return [
            'nome' => trim((string) ($_POST['nome'] ?? '')),
            'codigo' => !empty($_POST['codigo']) ? trim((string) $_POST['codigo']) : null,
            'categoria' => !empty($_POST['categoria']) ? trim((string) $_POST['categoria']) : null,
            'quantidade' => max(0, (int) ($_POST['quantidade'] ?? 1)),
            'situacao' => $_POST['situacao'] ?? 'disponivel',
            'descricao' => !empty($_POST['descricao']) ? trim((string) $_POST['descricao']) : null,
        ];
    }

    private function criarBloqueioSeNecessario(int $usuarioId): void
    {
        $usuario = (new User())->findWithProfile($usuarioId);
        if (!$usuario) {
            return;
        }

        $perfil = comparableProfile((string) ($usuario['perfil_nome'] ?? ''));
        $perfisBloqueaveis = [
            comparableProfile('Aluno'),
            comparableProfile('Aluno Bolsista'),
        ];
        if (!in_array($perfil, $perfisBloqueaveis, true)) {
            return;
        }

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
