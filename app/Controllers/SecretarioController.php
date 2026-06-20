<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\ItemPortaria;
use App\Models\PeriodoAcademico;
use App\Models\PermissaoSala;
use App\Models\ReservaAula;
use App\Models\Sala;
use App\Models\User;

// Gestão acadêmica e operacional feita pela secretaria.
class SecretarioController extends Controller
{
    public function index(): void { requireProfile('Secretário de Curso'); $this->view('secretario/index', ['title' => 'Secretário']); }
    public function disponibilidade(): void { requireProfile('Secretário de Curso'); $this->view('secretario/disponibilidade-salas', ['title' => 'Disponibilidade', 'salas' => (new Sala())->listDisponibilidade($_GET)]); }
    public function reservasCurso(): void { requireProfile('Secretário de Curso'); $this->view('secretario/reservas-curso', ['title' => 'Reservas do Curso']); }

    public function periodos(): void { requireProfile('Secretário de Curso'); $this->view('secretario/periodos-academicos', ['title' => 'Períodos', 'periodos' => (new PeriodoAcademico())->all('data_inicio DESC')]); }
    public function salvarPeriodo(): void { requireProfile('Secretário de Curso'); verifyCsrf(); (new PeriodoAcademico())->create($this->periodoData()); flash('success', 'Período salvo.'); redirect('/secretario/periodos-academicos'); }
    public function atualizarPeriodo(): void { requireProfile('Secretário de Curso'); verifyCsrf(); (new PeriodoAcademico())->update((int) $_POST['id'], $this->periodoData()); flash('success', 'Período atualizado.'); redirect('/secretario/periodos-academicos'); }
    public function excluirPeriodo(): void { requireProfile('Secretário de Curso'); verifyCsrf(); (new PeriodoAcademico())->delete((int) $_POST['id']); flash('success', 'Período excluído.'); redirect('/secretario/periodos-academicos'); }

    public function materias(): void
    {
        requireProfile('Secretário de Curso');
        $this->view('secretario/materias', [
            'title' => 'Cursos e Disciplinas',
            'cursos' => (new Curso())->all('nome'),
            'disciplinas' => (new Disciplina())->withDetails(),
            'professores' => (new User())->byProfile('Professor'),
        ]);
    }

    public function salvarMateria(): void
    {
        requireProfile('Secretário de Curso');
        verifyCsrf();
        if (($_POST['tipo'] ?? '') === 'curso') {
            (new Curso())->create(['nome' => trim((string) $_POST['nome']), 'codigo' => $_POST['codigo'] ?: null, 'situacao' => $_POST['situacao'] ?? 'ativo']);
            flash('success', 'Curso criado.');
        } else {
            (new Disciplina())->create($this->disciplinaData());
            flash('success', 'Disciplina criada.');
        }
        redirect('/secretario/materias');
    }

    public function atualizarCurso(): void { requireProfile('Secretário de Curso'); verifyCsrf(); (new Curso())->update((int) $_POST['id'], ['nome' => trim((string) $_POST['nome']), 'codigo' => $_POST['codigo'] ?: null, 'situacao' => $_POST['situacao'] ?? 'ativo']); flash('success', 'Curso atualizado.'); redirect('/secretario/materias'); }
    public function excluirCurso(): void { requireProfile('Secretário de Curso'); verifyCsrf(); try { (new Curso())->delete((int) $_POST['id']); } catch (\Throwable) { (new Curso())->update((int) $_POST['id'], ['situacao' => 'inativo']); } flash('success', 'Curso removido ou inativado.'); redirect('/secretario/materias'); }
    public function atualizarDisciplina(): void { requireProfile('Secretário de Curso'); verifyCsrf(); (new Disciplina())->update((int) $_POST['id'], $this->disciplinaData()); flash('success', 'Disciplina atualizada.'); redirect('/secretario/materias'); }
    public function excluirDisciplina(): void { requireProfile('Secretário de Curso'); verifyCsrf(); try { (new Disciplina())->delete((int) $_POST['id']); } catch (\Throwable) { (new Disciplina())->update((int) $_POST['id'], ['situacao' => 'inativa']); } flash('success', 'Disciplina removida ou inativada.'); redirect('/secretario/materias'); }

    public function bolsistas(): void
    {
        requireProfile('Secretário de Curso');
        $this->view('secretario/bolsistas', ['title' => 'Bolsistas', 'bolsistas' => (new User())->byProfile('Aluno Bolsista'), 'professores' => (new User())->byProfile('Professor')]);
    }

    public function salvarBolsista(): void
    {
        requireProfile('Secretário de Curso');
        verifyCsrf();
        $perfilId = Database::pdo()->query("SELECT id FROM perfis WHERE nome = 'Aluno Bolsista'")->fetchColumn();
        (new User())->create([
            'nome' => trim((string) $_POST['nome']),
            'email' => trim((string) $_POST['email']),
            'senha_hash' => password_hash((string) ($_POST['senha'] ?: '12345678'), PASSWORD_DEFAULT),
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
        requireProfile('Secretário de Curso');
        verifyCsrf();
        $data = ['nome' => trim((string) $_POST['nome']), 'email' => trim((string) $_POST['email']), 'situacao' => $_POST['situacao'] ?? 'ativo', 'professor_indicador_id' => $_POST['professor_indicador_id'] ?: null, 'projeto_pesquisa' => $_POST['projeto_pesquisa'] ?: null];
        if (!empty($_POST['senha'])) { $data['senha_hash'] = password_hash((string) $_POST['senha'], PASSWORD_DEFAULT); }
        (new User())->update((int) $_POST['id'], $data);
        flash('success', 'Bolsista atualizado.');
        redirect('/secretario/bolsistas');
    }

    public function excluirBolsista(): void
    {
        requireProfile('Secretário de Curso');
        verifyCsrf();
        $user = new User();
        if (!$user->deleteSafely((int) $_POST['id'])) { $user->anonymize((int) $_POST['id']); }
        flash('success', 'Bolsista removido.');
        redirect('/secretario/bolsistas');
    }

    public function reservasAulas(): void
    {
        requireProfile('Secretário de Curso');
        $this->view('secretario/reservas-aulas', ['title' => 'Aulas', 'reservas' => (new ReservaAula())->withDetails(), 'professores' => (new User())->byProfile('Professor'), 'disciplinas' => (new Disciplina())->all('nome')]);
    }
    public function salvarReservaAula(): void { requireProfile('Secretário de Curso'); verifyCsrf(); (new ReservaAula())->create($this->aulaData()); flash('success', 'Aula cadastrada.'); redirect('/secretario/reservas-aulas'); }
    public function atualizarReservaAula(): void { requireProfile('Secretário de Curso'); verifyCsrf(); (new ReservaAula())->update((int) $_POST['id'], $this->aulaData()); flash('success', 'Aula atualizada.'); redirect('/secretario/reservas-aulas'); }
    public function excluirReservaAula(): void { requireProfile('Secretário de Curso'); verifyCsrf(); (new ReservaAula())->delete((int) $_POST['id']); flash('success', 'Aula excluída.'); redirect('/secretario/reservas-aulas'); }

    public function salas(): void { requireProfile('Secretário de Curso'); $this->view('secretario/salas', ['title' => 'Salas', 'salas' => (new Sala())->all('nome')]); }
    public function salvarSala(): void { requireProfile('Secretário de Curso'); verifyCsrf(); (new Sala())->create($this->salaData()); flash('success', 'Sala cadastrada.'); redirect('/secretario/salas'); }
    public function atualizarSala(): void { requireProfile('Secretário de Curso'); verifyCsrf(); (new Sala())->update((int) $_POST['id'], $this->salaData()); flash('success', 'Sala atualizada.'); redirect('/secretario/salas'); }
    public function excluirSala(): void { requireProfile('Secretário de Curso'); verifyCsrf(); try { (new Sala())->delete((int) $_POST['id']); } catch (\Throwable) { (new Sala())->update((int) $_POST['id'], ['situacao' => 'bloqueada']); } flash('success', 'Sala removida ou bloqueada.'); redirect('/secretario/salas'); }

    public function chavesAutorizadas(): void
    {
        requireProfile('Secretário de Curso');
        $this->view('secretario/chaves-autorizadas', ['title' => 'Chaves Autorizadas', 'permissoes' => (new PermissaoSala())->withDetails(), 'usuarios' => (new User())->allWithProfile(), 'salas' => (new Sala())->all('nome')]);
    }
    public function salvarChaveAutorizada(): void { requireProfile(['Secretário de Curso', 'Professor']); verifyCsrf(); (new PermissaoSala())->create($this->permissaoData()); flash('success', 'Permissão salva.'); redirect('/secretario/chaves-autorizadas'); }
    public function atualizarChaveAutorizada(): void { requireProfile('Secretário de Curso'); verifyCsrf(); (new PermissaoSala())->update((int) $_POST['id'], $this->permissaoData()); flash('success', 'Permissão atualizada.'); redirect('/secretario/chaves-autorizadas'); }
    public function excluirChaveAutorizada(): void { requireProfile('Secretário de Curso'); verifyCsrf(); (new PermissaoSala())->delete((int) $_POST['id']); flash('success', 'Permissão excluída.'); redirect('/secretario/chaves-autorizadas'); }

    public function retiradaChaves(): void
    {
        requireProfile('Secretário de Curso');
        $this->view('secretario/retirada-chaves', ['title' => 'Retirada de Chaves e Itens', 'salas' => (new Sala())->chavesDisponiveisParaRetirada(currentUser()), 'itens' => (new ItemPortaria())->disponiveisParaRetirada()]);
    }

    public function itens(): void { requireProfile('Secretário de Curso'); $this->view('secretario/itens', ['title' => 'Itens', 'itens' => (new ItemPortaria())->all('nome')]); }
    public function salvarItem(): void { requireProfile('Secretário de Curso'); verifyCsrf(); (new ItemPortaria())->create($this->itemData()); flash('success', 'Item cadastrado.'); redirect('/secretario/itens'); }
    public function atualizarItem(): void { requireProfile('Secretário de Curso'); verifyCsrf(); (new ItemPortaria())->update((int) $_POST['id'], $this->itemData()); flash('success', 'Item atualizado.'); redirect('/secretario/itens'); }
    public function excluirItem(): void { requireProfile('Secretário de Curso'); verifyCsrf(); try { (new ItemPortaria())->delete((int) $_POST['id']); } catch (\Throwable) { (new ItemPortaria())->update((int) $_POST['id'], ['situacao' => 'indisponivel']); } flash('success', 'Item removido ou indisponibilizado.'); redirect('/secretario/itens'); }

    private function periodoData(): array { return ['nome' => trim((string) $_POST['nome']), 'data_inicio' => $_POST['data_inicio'], 'data_fim' => $_POST['data_fim'], 'situacao' => $_POST['situacao'] ?? 'ativo']; }
    private function disciplinaData(): array { return ['curso_id' => (int) $_POST['curso_id'], 'nome' => trim((string) $_POST['nome']), 'periodo_referencia' => trim((string) $_POST['periodo_referencia']), 'professor_id' => $_POST['professor_id'] ?: null, 'observacao' => $_POST['observacao'] ?? null, 'situacao' => $_POST['situacao'] ?? 'ativa']; }
    private function aulaData(): array { return ['usuario_id' => currentUser()['id'], 'professor_id' => (int) $_POST['professor_id'], 'disciplina_id' => (int) $_POST['disciplina_id'], 'periodo_academico' => $_POST['periodo_academico'], 'sala_nome' => $_POST['sala_nome'], 'turma' => $_POST['turma'], 'dia_semana' => $_POST['dia_semana'], 'horario_inicio' => $_POST['horario_inicio'], 'horario_fim' => $_POST['horario_fim'], 'disciplina' => $_POST['disciplina'], 'observacao' => $_POST['observacao'] ?? null, 'situacao' => $_POST['situacao'] ?? 'ativa']; }
    private function salaData(): array { return ['nome' => trim((string) $_POST['nome']), 'codigo' => $_POST['codigo'] ?: null, 'bloco' => $_POST['bloco'] ?: null, 'capacidade' => $_POST['capacidade'] !== '' ? (int) $_POST['capacidade'] : null, 'tipo_ambiente' => $_POST['tipo_ambiente'], 'situacao' => $_POST['situacao'] ?? 'disponivel', 'descricao' => $_POST['descricao'] ?? null]; }
    private function itemData(): array { return ['nome' => trim((string) $_POST['nome']), 'codigo' => $_POST['codigo'] ?: null, 'categoria' => $_POST['categoria'] ?: null, 'quantidade' => max(0, (int) ($_POST['quantidade'] ?? 1)), 'situacao' => $_POST['situacao'] ?? 'disponivel', 'descricao' => $_POST['descricao'] ?? null]; }
    private function permissaoData(): array { return ['usuario_id' => (int) $_POST['usuario_id'], 'sala_id' => !empty($_POST['acesso_total']) ? null : (int) $_POST['sala_id'], 'acesso_total' => !empty($_POST['acesso_total']) ? 1 : 0, 'autorizado_por' => currentUser()['id'], 'inicio_autorizacao' => $_POST['inicio_autorizacao'] ?: null, 'expira_em' => !empty($_POST['nunca_expirar']) ? null : ($_POST['expira_em'] ?: null), 'dias_semana' => !empty($_POST['dias_semana']) ? implode(', ', (array) $_POST['dias_semana']) : null, 'observacao' => $_POST['observacao'] ?? null, 'situacao' => $_POST['situacao'] ?? 'ativa']; }
}
