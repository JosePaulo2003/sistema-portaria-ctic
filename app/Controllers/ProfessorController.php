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
use App\Models\PermissaoSala;
use App\Models\Reserva;
use App\Models\ReservaAula;
use App\Models\Sala;
use App\Models\User;
use App\Services\AutorizacaoAcessoDocumento;
use App\Services\RetiradaChaveService;

// Fluxos do professor e rotas compartilhadas de retirada de chaves/itens.
class ProfessorController extends Controller
{
    public function index(): void { $this->salasHome('Professor'); }
    public function disponibilidadeSalas(): void { requireProfile('Professor'); $this->view('professor/disponibilidade-salas', ['title' => 'Disponibilidade de Salas', 'salas' => (new Sala())->listDisponibilidade($_GET)]); }
    public function reservasSalas(): void { requireProfile('Professor'); $this->view('professor/reservas-salas', ['title' => 'Reservas de Salas', 'reservas' => (new Reserva())->byUserWithDetails((int) currentUser()['id']), 'salas' => (new Sala())->all('nome')]); }

    public function salvarReservaSala(): void
    {
        requireProfile('Professor');
        verifyCsrf();
        $this->validarReservaSala();
        (new Reserva())->create([
            'usuario_id' => currentUser()['id'],
            'sala_id' => (int) $_POST['sala_id'],
            'titulo' => trim((string) $_POST['titulo']),
            'finalidade' => $_POST['finalidade'] ?? null,
            'tipo_reserva' => 'sala',
            'inicio_em' => $_POST['inicio_em'],
            'fim_em' => $_POST['fim_em'],
            'situacao' => 'pendente',
        ]);
        audit('Reservas', 'criacao', 'Reserva de sala solicitada.');
        flash('success', 'Reserva solicitada.');
        redirect('/professor/reservas-salas');
    }

    public function atualizarReservaSala(): void
    {
        requireProfile('Professor');
        verifyCsrf();
        $reserva = new Reserva();
        if (!$reserva->belongsToUser((int) $_POST['id'], (int) currentUser()['id'])) {
            flash('error', 'Reserva nao encontrada para este professor.');
            redirect('/professor/reservas-salas');
        }
        $this->validarReservaSala();
        $reserva->update((int) $_POST['id'], [
            'sala_id' => (int) $_POST['sala_id'],
            'titulo' => trim((string) $_POST['titulo']),
            'finalidade' => $_POST['finalidade'] ?? null,
            'inicio_em' => $_POST['inicio_em'],
            'fim_em' => $_POST['fim_em'],
            'situacao' => $_POST['situacao'] ?? 'pendente',
        ]);
        flash('success', 'Reserva atualizada.');
        redirect('/professor/reservas-salas');
    }

    public function excluirReservaSala(): void
    {
        requireProfile('Professor');
        verifyCsrf();
        $reserva = new Reserva();
        if (!$reserva->belongsToUser((int) $_POST['id'], (int) currentUser()['id'])) {
            flash('error', 'Reserva nao encontrada para este professor.');
            redirect('/professor/reservas-salas');
        }
        $reserva->delete((int) $_POST['id']);
        flash('success', 'Reserva excluÃ­da.');
        redirect('/professor/reservas-salas');
    }

    public function aulasSemestre(): void { requireProfile('Professor'); $this->view('professor/aulas-semestre', ['title' => 'Aulas do Semestre', 'reservas' => (new ReservaAula())->withDetails()]); }
    public function orientandosBolsistas(): void { requireProfile('Professor'); $this->view('professor/orientandos-bolsistas', ['title' => 'Orientandos Bolsistas', 'bolsistas' => (new User())->byProfileForProfessor('Aluno Bolsista', (int) currentUser()['id']), 'salas' => (new Sala())->all('nome'), 'permissoes' => (new PermissaoSala())->forProfessorOrientandos((int) currentUser()['id'])]); }

    public function salvarOrientando(): void
    {
        requireProfile('Professor');
        verifyCsrf();
        $senha = trim((string) ($_POST['senha'] ?? ''));
        if ($senha === '') {
            flash('error', 'Informe uma senha inicial para o orientando.');
            redirect('/professor/orientandos-bolsistas');
        }
        $perfilId = Database::pdo()->query("SELECT id FROM perfis WHERE nome = 'Aluno Bolsista'")->fetchColumn();
        (new User())->create([
            'nome' => trim((string) $_POST['nome']),
            'email' => trim((string) $_POST['email']),
            'matricula' => trim((string) ($_POST['matricula'] ?? '')) ?: null,
            'senha_hash' => password_hash($senha, PASSWORD_DEFAULT),
            'perfil_id' => (int) $perfilId,
            'situacao' => 'ativo',
            'professor_indicador_id' => currentUser()['id'],
            'projeto_pesquisa' => $_POST['projeto_pesquisa'] ?? null,
        ]);
        flash('success', 'Orientando cadastrado e liberado para acesso.');
        redirect('/professor/orientandos-bolsistas');
    }

    public function atualizarOrientando(): void
    {
        requireProfile('Professor');
        verifyCsrf();
        $user = new User();
        if (!$user->belongsToProfessor((int) $_POST['id'], (int) currentUser()['id'])) {
            flash('error', 'Orientando nao encontrado para este professor.');
            redirect('/professor/orientandos-bolsistas');
        }
        $data = [
            'nome' => trim((string) $_POST['nome']),
            'email' => trim((string) $_POST['email']),
            'matricula' => trim((string) ($_POST['matricula'] ?? '')) ?: null,
            'situacao' => $_POST['situacao'] ?? 'ativo',
            'projeto_pesquisa' => $_POST['projeto_pesquisa'] ?: null,
            'professor_indicador_id' => currentUser()['id'],
        ];
        if (!empty($_POST['senha'])) {
            $data['senha_hash'] = password_hash((string) $_POST['senha'], PASSWORD_DEFAULT);
        }
        $user->update((int) $_POST['id'], $data);
        flash('success', 'Orientando atualizado.');
        redirect('/professor/orientandos-bolsistas');
    }

    public function excluirOrientando(): void
    {
        requireProfile('Professor');
        verifyCsrf();
        $user = new User();
        if (!$user->belongsToProfessor((int) $_POST['id'], (int) currentUser()['id'])) {
            flash('error', 'Orientando nao encontrado para este professor.');
            redirect('/professor/orientandos-bolsistas');
        }
        if (!$user->deleteSafely((int) $_POST['id'])) {
            $user->anonymize((int) $_POST['id']);
        }
        flash('success', 'Orientando removido.');
        redirect('/professor/orientandos-bolsistas');
    }

    public function liberarChaveOrientando(): void
    {
        requireProfile('Professor');
        verifyCsrf();

        $professorId = (int) currentUser()['id'];
        $bolsistaIds = array_values(array_unique(array_filter(
            array_map('intval', (array) ($_POST['usuario_ids'] ?? [])),
            static fn (int $id): bool => $id > 0
        )));
        if (!$bolsistaIds || count($bolsistaIds) > 11) {
            flash('error', 'Selecione entre 1 e 11 bolsistas para a autorizacao.');
            redirect('/professor/orientandos-bolsistas');
        }

        $userModel = new User();
        $bolsistas = [];
        foreach ($bolsistaIds as $bolsistaId) {
            if (!$userModel->belongsToProfessor($bolsistaId, $professorId)) {
                flash('error', 'Um dos bolsistas selecionados nao pertence a este professor.');
                redirect('/professor/orientandos-bolsistas');
            }
            $bolsista = $userModel->findWithProfile($bolsistaId);
            if (!$bolsista || ($bolsista['situacao'] ?? '') !== 'ativo') {
                flash('error', 'Somente bolsistas ativos podem receber uma nova autorizacao.');
                redirect('/professor/orientandos-bolsistas');
            }
            $bolsistas[] = $bolsista;
        }

        $salaId = (int) ($_POST['sala_id'] ?? 0);
        $sala = $salaId > 0 ? (new Sala())->find($salaId) : null;
        if (!$sala) {
            flash('error', 'Informe a sala autorizada para o bolsista.');
            redirect('/professor/orientandos-bolsistas');
        }

        $inicio = $this->criarDataHora((string) ($_POST['inicio_autorizacao_data'] ?? ''));
        $nuncaExpirar = !empty($_POST['nunca_expirar']);
        $expira = $nuncaExpirar ? null : $this->criarDataHora((string) ($_POST['expira_em_data'] ?? ''));
        if (!$inicio) {
            flash('error', 'Informe uma data de inicio valida no formato dd/mm/aaaa.');
            redirect('/professor/orientandos-bolsistas');
        }
        if (!$nuncaExpirar && !$expira) {
            flash('error', 'Informe uma data de expiracao valida ou marque Nunca expirar.');
            redirect('/professor/orientandos-bolsistas');
        }
        $inicio = $inicio->setTime(0, 0, 0);
        $expira = $expira?->setTime(23, 59, 59);
        if ($expira && $expira < $inicio) {
            flash('error', 'A expiracao nao pode ser anterior ao inicio.');
            redirect('/professor/orientandos-bolsistas');
        }

        $horarioInicio = $this->horarioAutorizacao('horario_inicio');
        $horarioFim = $this->horarioAutorizacao('horario_fim');
        if (!$horarioInicio || !$horarioFim || $horarioInicio === $horarioFim) {
            flash('error', 'Informe um horario de acesso valido, com inicio e fim diferentes.');
            redirect('/professor/orientandos-bolsistas');
        }

        $diasPermitidos = ['segunda', 'terca', 'quarta', 'quinta', 'sexta', 'sabado', 'domingo'];
        $diasSemana = array_values(array_unique(array_intersect(
            $diasPermitidos,
            array_map(static fn ($dia): string => mb_strtolower(trim((string) $dia)), (array) ($_POST['dias_semana'] ?? []))
        )));
        $finalidade = trim((string) ($_POST['finalidade'] ?? ''));
        if ($finalidade === '') {
            flash('error', 'Informe a finalidade da autorizacao.');
            redirect('/professor/orientandos-bolsistas');
        }
        if (mb_strlen($finalidade) > 800) {
            flash('error', 'A finalidade deve ter no maximo 800 caracteres.');
            redirect('/professor/orientandos-bolsistas');
        }

        $numeroAutorizacao = mb_substr(trim((string) ($_POST['numero_autorizacao'] ?? '')), 0, 40);
        if ($numeroAutorizacao === '') {
            $numeroAutorizacao = sprintf('SGRP-%s-P%d', date('Ymd-His'), $professorId);
        }
        $baixarDocumento = (string) ($_POST['acao'] ?? '') === 'salvar_baixar';
        $documento = null;
        if ($baixarDocumento) {
            try {
                $documento = (new AutorizacaoAcessoDocumento())->gerar([
                    'numero_autorizacao' => $numeroAutorizacao,
                    'professor_nome' => (string) (currentUser()['nome'] ?? ''),
                    'professor_email' => (string) (currentUser()['email'] ?? ''),
                    'sala_nome' => (string) ($sala['nome'] ?? ''),
                    'sala_codigo' => (string) ($sala['codigo'] ?? ''),
                    'finalidade' => $finalidade,
                    'dias_acesso' => $this->diasAutorizacaoParaDocumento($diasSemana),
                    'horario_acesso' => substr($horarioInicio, 0, 5) . ' às ' . substr($horarioFim, 0, 5),
                    'inicio_em' => $inicio->format('d/m/Y'),
                    'expira_em' => $expira?->format('d/m/Y') ?? 'Sem expiração',
                    'data_solicitacao' => date('d/m/Y'),
                    'data_autorizacao' => date('d/m/Y'),
                    'bolsistas' => $bolsistas,
                ]);
            } catch (\Throwable $exception) {
                systemLog('error', 'Professor', 'Falha ao gerar autorizacao de acesso em Word.', [
                    'professor_id' => $professorId,
                    'erro' => $exception->getMessage(),
                ]);
                flash('error', $exception->getMessage());
                redirect('/professor/orientandos-bolsistas');
            }
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $permissaoModel = new PermissaoSala();
            foreach ($bolsistas as $bolsista) {
                $permissaoModel->create([
                    'usuario_id' => (int) $bolsista['id'],
                    'sala_id' => $salaId,
                    'acesso_total' => 0,
                    'autorizado_por' => $professorId,
                    'inicio_autorizacao' => $inicio->format('Y-m-d H:i:s'),
                    'expira_em' => $expira?->format('Y-m-d H:i:s'),
                    'horario_inicio' => $horarioInicio,
                    'horario_fim' => $horarioFim,
                    'dias_semana' => $diasSemana ? implode(', ', $diasSemana) : null,
                    'observacao' => $finalidade,
                    'situacao' => 'ativa',
                ]);
            }
            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if (is_array($documento) && !empty($documento['path'])) {
                @unlink((string) $documento['path']);
            }
            systemLog('error', 'Professor', 'Falha ao salvar autorizacoes de bolsistas.', [
                'professor_id' => $professorId,
                'bolsista_ids' => $bolsistaIds,
                'sala_id' => $salaId,
                'erro' => $exception->getMessage(),
            ]);
            flash('error', 'Nao foi possivel salvar as autorizacoes. Nenhuma alteracao foi realizada.');
            redirect('/professor/orientandos-bolsistas');
        }

        audit('Professor', 'autorizacao_bolsistas', 'Autorizacao de acesso criada para bolsistas.', [
            'professor_id' => $professorId,
            'bolsista_ids' => $bolsistaIds,
            'sala_id' => $salaId,
            'inicio_autorizacao' => $inicio->format('Y-m-d H:i:s'),
            'expira_em' => $expira?->format('Y-m-d H:i:s'),
            'horario_inicio' => $horarioInicio,
            'horario_fim' => $horarioFim,
            'dias_semana' => $diasSemana,
            'documento_gerado' => $baixarDocumento,
        ]);

        if (is_array($documento)) {
            (new AutorizacaoAcessoDocumento())->enviarDownload($documento);
        }

        flash('success', count($bolsistas) . ' autorizacao(oes) de chave criada(s).');
        redirect('/professor/orientandos-bolsistas');
    }

    public function retiradas(): void
    {
        requireProfile('Professor');
        $this->view('professor/retiradas', [
            'title' => 'Retiradas',
            'salas' => (new Sala())->chavesParaRetirada(currentUser()),
            'itens' => (new ItemPortaria())->disponiveisParaRetirada(),
            'bloqueio' => $this->bloqueioAtualizadoParaUsuario((int) currentUser()['id']),
        ]);
    }

    public function retirarChave(): void
    {
        requireProfile(['Professor', 'Aluno Bolsista', 'ServiÃ§os Gerais', 'Visitante', 'SecretÃ¡rio de Curso', 'Administrativo', 'Diretor', 'Motorista']);
        verifyCsrf();
        $retorno = $this->retornoRetiradaChave();
        if (!$this->confirmarSenhaRetirada($retorno)) {
            return;
        }
        $bloqueio = $this->bloqueioAtualizadoParaUsuario((int) currentUser()['id']);
        if ($bloqueio) {
            flash('error', 'VocÃª estÃ¡ temporariamente bloqueado para retirar chaves atÃ© ' . date('d/m/Y H:i', strtotime($bloqueio['fim_em'])) . '.');
            redirect($retorno);
        }
        $usuarioAtual = currentUser();
        $salaId = (int) $_POST['sala_id'];
        if (!(new Sala())->chavePodeSerRetirada($salaId, $usuarioAtual)) {
            flash('error', 'Esta chave nÃ£o estÃ¡ disponÃ­vel para retirada.');
            redirect($retorno);
        }

        $retiradaService = new RetiradaChaveService();
        if ($usuarioAtual && $retiradaService->exigeConfirmacaoPortaria($usuarioAtual)) {
            try {
                $solicitacao = $retiradaService->solicitar($usuarioAtual, $salaId, $_POST['observacao'] ?? null);
            } catch (\RuntimeException $error) {
                flash('error', $error->getMessage());
                redirect($retorno);
            }
            $_SESSION['_codigo_retirada'] = $solicitacao;
            flash('success', !empty($solicitacao['exige_codigo_temporario'])
                ? 'Solicitação enviada à Portaria. Apresente a senha temporária de 4 dígitos para receber a chave.'
                : 'Solicitação enviada à Portaria. Aguarde o agente aceitar ou recusar a entrega.');
            redirect($retorno);
        }

        (new Movimentacao())->create([
            'usuario_id' => currentUser()['id'],
            'sala_id' => $salaId,
            'tipo_movimentacao' => 'retirada_chave',
            'situacao' => 'aberta',
            'retirada_em' => date('Y-m-d H:i:s'),
            'devolucao_prevista_em' => null,
            'registrado_por_usuario_id' => currentUser()['id'],
            'observacao' => $_POST['observacao'] ?? null,
        ]);
        flash('success', 'Retirada registrada automaticamente. Este perfil não exige senha temporária nem confirmação da Portaria.');
        redirect($retorno);
    }

    public function retirarItem(): void
    {
        requireProfile(['Professor', 'Aluno Bolsista', 'ServiÃ§os Gerais', 'SecretÃ¡rio de Curso', 'Administrativo']);
        verifyCsrf();
        $retorno = $this->retornoRetiradaItem();
        if (!$this->confirmarSenhaRetirada($retorno)) {
            return;
        }
        $itemId = (int) $_POST['item_portaria_id'];
        if (!(new ItemPortaria())->podeSerRetirado($itemId)) {
            flash('error', 'Este item nÃ£o estÃ¡ disponÃ­vel para retirada.');
            redirect($retorno);
        }
        (new Movimentacao())->create([
            'usuario_id' => currentUser()['id'],
            'item_portaria_id' => $itemId,
            'tipo_movimentacao' => 'retirada_item',
            'situacao' => 'aberta',
            'retirada_em' => date('Y-m-d H:i:s'),
            'devolucao_prevista_em' => null,
            'registrado_por_usuario_id' => currentUser()['id'],
            'observacao' => $_POST['observacao'] ?? null,
        ]);
        flash('success', 'Retirada de item registrada.');
        redirect($retorno);
    }

    private function confirmarSenhaRetirada(string $retorno): bool
    {
        $senha = (string) ($_POST['senha_confirmacao'] ?? '');
        $usuario = (new User())->find((int) currentUser()['id']);
        if ($senha === '' || !$usuario || !password_verify($senha, (string) $usuario['senha_hash'])) {
            flash('error', 'Confirme sua senha corretamente para registrar a retirada.');
            redirect($retorno);
            return false;
        }
        return true;
    }

    private function bloqueioAtualizadoParaUsuario(int $usuarioId): ?array
    {
        $bloqueioModel = new BloqueioChave();
        $bloqueio = $bloqueioModel->ativoParaUsuario($usuarioId);
        if ($bloqueio) {
            return $bloqueio;
        }

        $usuario = (new User())->findWithProfile($usuarioId);
        if (!$usuario) {
            return null;
        }
        $perfil = comparableProfile((string) ($usuario['perfil_nome'] ?? ''));
        if (!in_array($perfil, [comparableProfile('Aluno'), comparableProfile('Aluno Bolsista')], true)) {
            return null;
        }

        $advertencias = new AdvertenciaChave();
        if (!$advertencias->shouldCreateBlock($usuarioId, $bloqueioModel->latestAdvertenciaIdByUser($usuarioId))) {
            return null;
        }
        $dias = max(1, (int) (new ConfiguracaoSistema())->getValue('dias_bloqueio_advertencia', '7'));
        $bloqueioModel->create([
            'usuario_id' => $usuarioId,
            'advertencia_id' => $advertencias->latestIdByUser($usuarioId),
            'inicio_em' => date('Y-m-d H:i:s'),
            'fim_em' => date('Y-m-d H:i:s', strtotime("+{$dias} days")),
            'situacao' => 'ativo',
        ]);
        return $bloqueioModel->ativoParaUsuario($usuarioId);
    }

    private function retornoRetiradaChave(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
        return match (true) {
            str_contains($uri, '/secretario/') => '/secretario/retirada-chaves',
            str_contains($uri, '/administrativo/') => '/administrativo/retiradas',
            str_contains($uri, '/diretor/') => '/diretor/chaves',
            str_contains($uri, '/servicos-gerais/') => '/servicos-gerais/retiradas',
            str_contains($uri, '/bolsista/') => '/bolsista/retiradas',
            str_contains($uri, '/visitante/') => '/visitante/chave',
            str_contains($uri, '/motorista/') => '/motorista/retiradas',
            default => '/professor/retiradas',
        };
    }

    private function retornoRetiradaItem(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
        return match (true) {
            str_contains($uri, '/secretario/') => '/secretario/retirada-chaves',
            str_contains($uri, '/administrativo/') => '/administrativo/retiradas',
            str_contains($uri, '/servicos-gerais/') => '/servicos-gerais/retiradas',
            str_contains($uri, '/bolsista/') => '/bolsista/retiradas',
            default => '/professor/retiradas',
        };
    }

    private function validarReservaSala(): void
    {
        $inicio = $this->criarDataHora((string) ($_POST['inicio_em'] ?? ''));
        $fim = $this->criarDataHora((string) ($_POST['fim_em'] ?? ''));
        $agora = new \DateTimeImmutable();

        if (!$inicio || !$fim) {
            flash('error', 'Informe datas e horÃ¡rios vÃ¡lidos para a reserva.');
            redirect('/professor/reservas-salas');
        }
        if ($inicio < $agora || $fim < $agora) {
            flash('error', 'NÃ£o Ã© permitido cadastrar reservas com data anterior ao momento atual.');
            redirect('/professor/reservas-salas');
        }
        if ($fim <= $inicio) {
            flash('error', 'O fim da reserva precisa ser posterior ao inÃ­cio.');
            redirect('/professor/reservas-salas');
        }
        $salaId = (int) ($_POST['sala_id'] ?? 0);
        if ($salaId <= 0) {
            flash('error', 'Informe uma sala valida para a reserva.');
            redirect('/professor/reservas-salas');
        }
        $ignoreId = isset($_POST['id']) ? (int) $_POST['id'] : null;
        if (!(new Reserva())->salaDisponivelParaReserva(
            $salaId,
            $inicio->format('Y-m-d H:i:s'),
            $fim->format('Y-m-d H:i:s'),
            $ignoreId
        )) {
            flash('error', 'Ja existe reserva pendente ou confirmada para esta sala no periodo informado.');
            redirect('/professor/reservas-salas');
        }
    }

    private function criarDataHora(string $valor): ?\DateTimeImmutable
    {
        return \parseDateTimeInput($valor);
    }

    private function horarioAutorizacao(string $prefixo): ?string
    {
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

        return sprintf('%02d:%02d:00', $hora, $minuto);
    }

    private function diasAutorizacaoParaDocumento(array $dias): string
    {
        if (!$dias) {
            return 'Todos os dias';
        }

        $rotulos = [
            'segunda' => 'Segunda',
            'terca' => 'Terça',
            'quarta' => 'Quarta',
            'quinta' => 'Quinta',
            'sexta' => 'Sexta',
            'sabado' => 'Sábado',
            'domingo' => 'Domingo',
        ];

        return implode(', ', array_map(static fn (string $dia): string => $rotulos[$dia] ?? $dia, $dias));
    }
}

