<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\AdvertenciaChave;
use App\Models\BloqueioChave;
use App\Models\ConfiguracaoSistema;
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
        $this->view('portaria/permissoes', [
            'title' => 'Permissões',
            'permissoesSalas' => (new PermissaoSala())->withDetails(),
            'permissoesItens' => (new PermissaoItem())->withDetails(),
            'usuarios' => (new User())->allWithProfile(),
            'salas' => (new Sala())->all('nome'),
        ]);
    }

    public function salvarPermissaoChave(): void
    {
        requireProfile('Agente de Portaria');
        verifyCsrf();

        $usuarioId = (int) ($_POST['usuario_id'] ?? 0);
        $salaId = (int) ($_POST['sala_id'] ?? 0);
        $acessoTotal = !empty($_POST['acesso_total']);
        $usuario = (new User())->find($usuarioId);

        if (!$usuario || ($usuario['situacao'] ?? '') !== 'ativo') {
            flash('error', 'Selecione um usuario ativo.');
            redirect('/portaria/permissoes');
        }
        if (!$acessoTotal && $salaId <= 0) {
            flash('error', 'Selecione uma chave ou marque acesso total.');
            redirect('/portaria/permissoes');
        }

        $inicio = $this->criarDataHora((string) ($_POST['inicio_autorizacao'] ?? ''));
        $expira = $this->criarDataHora((string) ($_POST['expira_em'] ?? ''));
        // A permissao da Portaria sempre tem janela de validade.
        if (!$inicio) {
            flash('error', 'Informe um inicio de autorizacao valido.');
            redirect('/portaria/permissoes');
        }
        if (!$expira) {
            flash('error', 'Informe uma data de expiracao valida.');
            redirect('/portaria/permissoes');
        }
        if ($inicio < new \DateTimeImmutable() || $expira < new \DateTimeImmutable()) {
            flash('error', 'A autorizacao precisa usar data e horario futuros.');
            redirect('/portaria/permissoes');
        }
        if ($inicio && $expira && $expira < $inicio) {
            flash('error', 'A expiracao nao pode ser anterior ao inicio.');
            redirect('/portaria/permissoes');
        }

        (new PermissaoSala())->create([
            'usuario_id' => $usuarioId,
            'sala_id' => $acessoTotal ? null : $salaId,
            'acesso_total' => $acessoTotal ? 1 : 0,
            'autorizado_por' => currentUser()['id'],
            'inicio_autorizacao' => $_POST['inicio_autorizacao'] ?: null,
            'expira_em' => $_POST['expira_em'] ?: null,
            'dias_semana' => !empty($_POST['dias_semana']) ? implode(', ', (array) $_POST['dias_semana']) : null,
            'observacao' => $_POST['observacao'] ?? null,
            'situacao' => 'ativa',
        ]);

        audit('Portaria', 'permissao_chave', 'Permissao de chave cadastrada pela portaria.', [
            'usuario_id' => $usuarioId,
            'sala_id' => $acessoTotal ? null : $salaId,
            'acesso_total' => $acessoTotal,
        ]);
        flash('success', 'Permissao de chave cadastrada.');
        redirect('/portaria/permissoes');
    }

    public function revogarPermissaoChave(): void
    {
        requireProfile('Agente de Portaria');
        verifyCsrf();

        $permissaoId = (int) ($_POST['id'] ?? 0);
        if (!(new PermissaoSala())->find($permissaoId)) {
            flash('error', 'Permissao nao encontrada.');
            redirect('/portaria/permissoes');
        }

        (new PermissaoSala())->update($permissaoId, ['situacao' => 'revogada']);
        audit('Portaria', 'permissao_chave_revogada', 'Permissao de chave revogada pela portaria.', [
            'permissao_id' => $permissaoId,
        ]);
        flash('success', 'Permissao revogada.');
        redirect('/portaria/permissoes');
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
            'usuarios' => (new User())->all('nome'),
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
        $solicitanteManual = mb_substr(trim((string) ($_POST['solicitante_nome_manual'] ?? '')), 0, 180);
        $recorrenciaFim = trim((string) ($_POST['recorrencia_fim'] ?? ''));
        $diasSemana = $this->diasSemanaSelecionados((array) ($_POST['dias_semana'] ?? []));

        if (!$inicio || !$fim || $salaId <= 0) {
            flash('error', 'Informe sala, data e horario validos para cadastrar a reserva.');
            redirect('/portaria/reservas');
        }
        if ($usuarioId <= 0 && $solicitanteManual === '') {
            flash('error', 'Informe um usuario cadastrado ou escreva o nome do solicitante.');
            redirect('/portaria/reservas');
        }
        if ($usuarioId > 0) {
            $usuario = (new User())->find($usuarioId);
            if (!$usuario || ($usuario['situacao'] ?? '') !== 'ativo') {
                flash('error', 'Selecione um usuario ativo para a reserva.');
                redirect('/portaria/reservas');
            }
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
        $data = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $valor);
        return $data instanceof \DateTimeImmutable ? $data : null;
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
        $this->view('portaria/visitantes', ['title' => 'Visitantes', 'visitantes' => (new User())->byProfile('Visitante')]);
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
        $perfilId = \App\Core\Database::pdo()->query("SELECT id FROM perfis WHERE nome = 'Visitante'")->fetchColumn();
        (new User())->create([
            'nome' => trim((string) $_POST['nome']),
            'email' => trim((string) $_POST['email']),
            'senha_hash' => password_hash($senha, PASSWORD_DEFAULT),
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
        $this->view('portaria/historico', [
            'title' => 'Historico',
            'movimentacoes' => (new Movimentacao())->historicoFiltrado($_GET),
            'filtros' => $_GET,
        ]);
        return;
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
