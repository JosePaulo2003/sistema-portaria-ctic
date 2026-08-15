<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Curso;
use App\Models\ItemPortaria;
use App\Models\PeriodoAcademico;
use App\Models\PermissaoSala;
use App\Models\Reserva;
use App\Models\Sala;
use App\Models\User;

// GestÃ£o acadÃªmica e operacional feita pela secretaria.
class SecretarioController extends Controller
{
    public function index(): void { $this->salasHome('SecretÃ¡rio de Curso'); }
    public function disponibilidade(): void { requireProfile('SecretÃ¡rio de Curso'); $this->view('secretario/disponibilidade-salas', ['title' => 'Disponibilidade', 'salas' => (new Sala())->listDisponibilidade($_GET)]); }
    public function cursos(): void
    {
        requireProfile('SecretÃ¡rio de Curso');
        $this->view('secretario/cursos', [
            'title' => 'Cursos',
            'cursos' => (new Curso())->all('nome'),
        ]);
    }

    public function salvarCurso(): void
    {
        requireProfile('SecretÃ¡rio de Curso');
        verifyCsrf();
        (new Curso())->create($this->cursoData());
        flash('success', 'Curso criado.');
        redirect('/secretario/cursos');
    }

    public function reservasCurso(): void
    {
        requireProfile('SecretÃ¡rio de Curso');
        $this->view('secretario/reservas-curso', [
            'title' => 'Reservas do Curso',
            'reservas' => (new Reserva())->withDetails(),
            'salas' => (new Sala())->all('nome'),
        ]);
    }

    public function salvarReservaCurso(): void
    {
        requireProfile('SecretÃ¡rio de Curso');
        verifyCsrf();
        $this->validarReservaCurso();

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

        audit('Reservas', 'criacao', 'Reserva do curso solicitada.');
        flash('success', 'Reserva solicitada. Aguarde a aprovaÃ§Ã£o da Portaria.');
        redirect('/secretario/reservas-curso');
    }

    public function periodos(): void { requireProfile('SecretÃ¡rio de Curso'); $this->view('secretario/periodos-academicos', ['title' => 'PerÃ­odos', 'periodos' => (new PeriodoAcademico())->all('data_inicio DESC')]); }
    public function salvarPeriodo(): void { requireProfile('SecretÃ¡rio de Curso'); verifyCsrf(); $this->validarPeriodo(); (new PeriodoAcademico())->create($this->periodoData()); flash('success', 'PerÃ­odo salvo.'); redirect('/secretario/periodos-academicos'); }
    public function atualizarPeriodo(): void { requireProfile('SecretÃ¡rio de Curso'); verifyCsrf(); $this->validarPeriodo(); (new PeriodoAcademico())->update((int) $_POST['id'], $this->periodoData()); flash('success', 'PerÃ­odo atualizado.'); redirect('/secretario/periodos-academicos'); }
    public function excluirPeriodo(): void { requireProfile('SecretÃ¡rio de Curso'); verifyCsrf(); (new PeriodoAcademico())->delete((int) $_POST['id']); flash('success', 'PerÃ­odo excluÃ­do.'); redirect('/secretario/periodos-academicos'); }

    public function atualizarCurso(): void { requireProfile('SecretÃ¡rio de Curso'); verifyCsrf(); (new Curso())->update((int) $_POST['id'], $this->cursoData()); flash('success', 'Curso atualizado.'); redirect('/secretario/cursos'); }
    public function excluirCurso(): void { requireProfile('SecretÃ¡rio de Curso'); verifyCsrf(); try { (new Curso())->delete((int) $_POST['id']); } catch (\Throwable) { (new Curso())->update((int) $_POST['id'], ['situacao' => 'inativo']); } flash('success', 'Curso removido ou inativado.'); redirect('/secretario/cursos'); }

    public function bolsistas(): void
    {
        requireProfile('SecretÃ¡rio de Curso');
        $this->view('secretario/bolsistas', ['title' => 'Bolsistas', 'bolsistas' => (new User())->byProfile('Aluno Bolsista'), 'professores' => (new User())->byProfile('Professor')]);
    }

    public function salvarBolsista(): void
    {
        requireProfile('SecretÃ¡rio de Curso');
        verifyCsrf();
        $senha = trim((string) ($_POST['senha'] ?? ''));
        if ($senha === '') {
            flash('error', 'Informe uma senha inicial para o bolsista.');
            redirect('/secretario/bolsistas');
        }
        $perfilId = Database::pdo()->query("SELECT id FROM perfis WHERE nome = 'Aluno Bolsista'")->fetchColumn();
        (new User())->create([
            'nome' => trim((string) $_POST['nome']),
            'email' => trim((string) $_POST['email']),
            'senha_hash' => password_hash($senha, PASSWORD_DEFAULT),
            'perfil_id' => (int) $perfilId,
            'situacao' => $_POST['situacao'] ?? 'pendente',
            'professor_indicador_id' => $_POST['professor_indicador_id'] ?: null,
            'projeto_pesquisa' => $_POST['projeto_pesquisa'] ?: null,
        ]);
        flash('success', 'Bolsista salvo.');
        redirect('/secretario/bolsistas');
    }

    public function atualizarBolsista(): void
    {
        requireProfile('SecretÃ¡rio de Curso');
        verifyCsrf();
        $data = ['nome' => trim((string) $_POST['nome']), 'email' => trim((string) $_POST['email']), 'situacao' => $_POST['situacao'] ?? 'ativo', 'professor_indicador_id' => $_POST['professor_indicador_id'] ?: null, 'projeto_pesquisa' => $_POST['projeto_pesquisa'] ?: null];
        if (!empty($_POST['senha'])) { $data['senha_hash'] = password_hash((string) $_POST['senha'], PASSWORD_DEFAULT); }
        (new User())->update((int) $_POST['id'], $data);
        flash('success', 'Bolsista atualizado.');
        redirect('/secretario/bolsistas');
    }

    public function excluirBolsista(): void
    {
        requireProfile('SecretÃ¡rio de Curso');
        verifyCsrf();
        $user = new User();
        if (!$user->deleteSafely((int) $_POST['id'])) { $user->anonymize((int) $_POST['id']); }
        flash('success', 'Bolsista removido.');
        redirect('/secretario/bolsistas');
    }

    public function chavesAutorizadas(): void
    {
        requireProfile('SecretÃ¡rio de Curso');
        $this->view('secretario/chaves-autorizadas', ['title' => 'Chaves Autorizadas', 'permissoes' => (new PermissaoSala())->withDetails(), 'usuarios' => (new User())->allWithProfile(), 'salas' => (new Sala())->all('nome')]);
    }
    public function salvarChaveAutorizada(): void { requireProfile(['SecretÃ¡rio de Curso', 'Professor']); verifyCsrf(); $this->validarPermissao(); (new PermissaoSala())->create($this->permissaoData()); flash('success', 'PermissÃ£o salva.'); redirect('/secretario/chaves-autorizadas'); }
    public function atualizarChaveAutorizada(): void { requireProfile('SecretÃ¡rio de Curso'); verifyCsrf(); $this->validarPermissao(); (new PermissaoSala())->update((int) $_POST['id'], $this->permissaoData()); flash('success', 'PermissÃ£o atualizada.'); redirect('/secretario/chaves-autorizadas'); }
    public function excluirChaveAutorizada(): void { requireProfile('SecretÃ¡rio de Curso'); verifyCsrf(); (new PermissaoSala())->delete((int) $_POST['id']); flash('success', 'PermissÃ£o excluÃ­da.'); redirect('/secretario/chaves-autorizadas'); }

    public function retiradaChaves(): void
    {
        requireProfile('SecretÃ¡rio de Curso');
        $this->view('secretario/retirada-chaves', ['title' => 'Retirada de Chaves e Itens', 'salas' => (new Sala())->chavesParaRetirada(currentUser()), 'itens' => (new ItemPortaria())->disponiveisParaRetirada()]);
    }

    private function periodoData(): array { return ['nome' => trim((string) $_POST['nome']), 'data_inicio' => $_POST['data_inicio'], 'data_fim' => $_POST['data_fim'], 'situacao' => $_POST['situacao'] ?? 'ativo']; }
    private function cursoData(): array { return ['nome' => trim((string) $_POST['nome']), 'codigo' => $_POST['codigo'] ?: null, 'situacao' => $_POST['situacao'] ?? 'ativo']; }
    private function permissaoData(): array { return ['usuario_id' => (int) $_POST['usuario_id'], 'sala_id' => !empty($_POST['acesso_total']) ? null : (int) $_POST['sala_id'], 'acesso_total' => !empty($_POST['acesso_total']) ? 1 : 0, 'autorizado_por' => currentUser()['id'], 'inicio_autorizacao' => \databaseDateTimeFromInput((string) ($_POST['inicio_autorizacao'] ?? '')), 'expira_em' => !empty($_POST['nunca_expirar']) ? null : \databaseDateTimeFromInput((string) ($_POST['expira_em'] ?? '')), 'dias_semana' => !empty($_POST['dias_semana']) ? implode(', ', (array) $_POST['dias_semana']) : null, 'observacao' => $_POST['observacao'] ?? null, 'situacao' => $_POST['situacao'] ?? 'ativa']; }

    private function validarPeriodo(): void
    {
        $inicio = $this->criarData((string) ($_POST['data_inicio'] ?? ''));
        $fim = $this->criarData((string) ($_POST['data_fim'] ?? ''));
        $hoje = new \DateTimeImmutable('today');

        if (!$inicio || !$fim) {
            flash('error', 'Informe datas vÃ¡lidas para o perÃ­odo.');
            redirect('/secretario/periodos-academicos');
        }
        if ($inicio < $hoje || $fim < $hoje) {
            flash('error', 'NÃ£o Ã© permitido cadastrar datas anteriores a hoje.');
            redirect('/secretario/periodos-academicos');
        }
        if ($fim < $inicio) {
            flash('error', 'A data final nÃ£o pode ser anterior Ã  data inicial.');
            redirect('/secretario/periodos-academicos');
        }
    }

    private function validarPermissao(): void
    {
        $inicio = $this->criarDataHora((string) ($_POST['inicio_autorizacao'] ?? ''));
        $expira = !empty($_POST['nunca_expirar']) ? null : $this->criarDataHora((string) ($_POST['expira_em'] ?? ''));
        $agora = new \DateTimeImmutable();

        if (!empty($_POST['inicio_autorizacao']) && (!$inicio || $inicio < $agora)) {
            flash('error', 'O inÃ­cio da autorizaÃ§Ã£o nÃ£o pode estar no passado.');
            redirect('/secretario/chaves-autorizadas');
        }
        if (empty($_POST['nunca_expirar']) && !empty($_POST['expira_em']) && (!$expira || $expira < $agora)) {
            flash('error', 'A expiraÃ§Ã£o da autorizaÃ§Ã£o nÃ£o pode estar no passado.');
            redirect('/secretario/chaves-autorizadas');
        }
        if ($inicio && $expira && $expira < $inicio) {
            flash('error', 'A expiraÃ§Ã£o nÃ£o pode ser anterior ao inÃ­cio da autorizaÃ§Ã£o.');
            redirect('/secretario/chaves-autorizadas');
        }
    }

    private function validarReservaCurso(): void
    {
        $inicio = $this->criarDataHora((string) ($_POST['inicio_em'] ?? ''));
        $fim = $this->criarDataHora((string) ($_POST['fim_em'] ?? ''));
        $salaId = (int) ($_POST['sala_id'] ?? 0);

        if (!$inicio || !$fim || $salaId <= 0) {
            flash('error', 'Informe sala, data e horÃ¡rio vÃ¡lidos para solicitar a reserva.');
            redirect('/secretario/reservas-curso');
        }
        if ($inicio < new \DateTimeImmutable()) {
            flash('error', 'NÃ£o Ã© possÃ­vel reservar sala com data ou horÃ¡rio anterior ao momento atual.');
            redirect('/secretario/reservas-curso');
        }
        if ($fim <= $inicio) {
            flash('error', 'O fim da reserva precisa ser posterior ao inÃ­cio.');
            redirect('/secretario/reservas-curso');
        }
        if (!$this->salaDisponivelParaReserva($salaId, $inicio, $fim)) {
            flash('error', 'Esta sala nÃ£o estÃ¡ disponÃ­vel no perÃ­odo informado.');
            redirect('/secretario/reservas-curso');
        }
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

        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM reservas
             WHERE sala_id = ? AND situacao IN ("pendente", "confirmada")
               AND inicio_em < ? AND fim_em > ?'
        );
        $stmt->execute([
            $salaId,
            $fim->format('Y-m-d H:i:s'),
            $inicio->format('Y-m-d H:i:s'),
        ]);
        return (int) $stmt->fetchColumn() === 0;
    }

    private function criarData(string $valor): ?\DateTimeImmutable
    {
        $data = \DateTimeImmutable::createFromFormat('Y-m-d', $valor);
        return $data instanceof \DateTimeImmutable ? $data->setTime(0, 0) : null;
    }

    private function criarDataHora(string $valor): ?\DateTimeImmutable
    {
        return \parseDateTimeInput($valor);
    }
}

