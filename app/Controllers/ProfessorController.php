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

        $bolsistaId = (int) ($_POST['usuario_id'] ?? 0);
        $professorId = (int) currentUser()['id'];
        if (!(new User())->belongsToProfessor($bolsistaId, $professorId)) {
            flash('error', 'Bolsista nao encontrado para este professor.');
            redirect('/professor/orientandos-bolsistas');
        }

        $salaId = (int) ($_POST['sala_id'] ?? 0);
        if ($salaId <= 0) {
            flash('error', 'Informe a sala autorizada para o bolsista.');
            redirect('/professor/orientandos-bolsistas');
        }

        $inicio = $this->criarDataHora((string) ($_POST['inicio_autorizacao'] ?? ''));
        $expira = $this->criarDataHora((string) ($_POST['expira_em'] ?? ''));
        if (!empty($_POST['inicio_autorizacao']) && !$inicio) {
            flash('error', 'Informe um inicio de autorizacao valido.');
            redirect('/professor/orientandos-bolsistas');
        }
        if (!empty($_POST['expira_em']) && !$expira) {
            flash('error', 'Informe uma data de expiracao valida.');
            redirect('/professor/orientandos-bolsistas');
        }
        if ($inicio && $expira && $expira < $inicio) {
            flash('error', 'A expiracao nao pode ser anterior ao inicio.');
            redirect('/professor/orientandos-bolsistas');
        }

        (new PermissaoSala())->create([
            'usuario_id' => $bolsistaId,
            'sala_id' => $salaId,
            'acesso_total' => 0,
            'autorizado_por' => $professorId,
            'inicio_autorizacao' => $_POST['inicio_autorizacao'] ?: null,
            'expira_em' => $_POST['expira_em'] ?: null,
            'dias_semana' => !empty($_POST['dias_semana']) ? implode(', ', (array) $_POST['dias_semana']) : null,
            'observacao' => $_POST['observacao'] ?? null,
            'situacao' => 'ativa',
        ]);
        flash('success', 'Chave liberada para o bolsista.');
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
        $salaId = (int) $_POST['sala_id'];
        if (!(new Sala())->chavePodeSerRetirada($salaId, currentUser())) {
            flash('error', 'Esta chave nÃ£o estÃ¡ disponÃ­vel para retirada.');
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
        flash('success', 'Retirada registrada.');
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
        if ((new Reserva())->hasConflict($salaId, $inicio->format('Y-m-d H:i:s'), $fim->format('Y-m-d H:i:s'), $ignoreId)) {
            flash('error', 'Ja existe reserva pendente ou confirmada para esta sala no periodo informado.');
            redirect('/professor/reservas-salas');
        }
    }

    private function criarDataHora(string $valor): ?\DateTimeImmutable
    {
        $data = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $valor);
        return $data instanceof \DateTimeImmutable ? $data : null;
    }
}

