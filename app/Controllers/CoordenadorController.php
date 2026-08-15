<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\ReservaAula;
use App\Models\Sala;
use App\Models\User;

class CoordenadorController extends Controller
{
    public function index(): void
    {
        $this->salasHome('Coordenador de Curso');
    }

    public function materias(): void
    {
        requireProfile('Coordenador de Curso');
        $curso = $this->cursoAtual();
        $cursoId = (int) ($curso['id'] ?? 0);

        $this->view('coordenador/materias', [
            'title' => 'Materias do Curso',
            'curso' => $curso,
            'disciplinas' => $cursoId > 0 ? (new Disciplina())->withDetailsByCourse($cursoId) : [],
            'professores' => (new User())->byProfile('Professor'),
        ]);
    }

    public function salvarMateria(): void
    {
        requireProfile('Coordenador de Curso');
        verifyCsrf();
        $curso = $this->cursoObrigatorio();

        (new Disciplina())->create($this->disciplinaData((int) $curso['id']));
        audit('Coordenador', 'criacao_disciplina', 'Disciplina criada pelo coordenador.', ['curso_id' => $curso['id']]);
        flash('success', 'Disciplina criada.');
        redirect('/coordenador/materias');
    }

    public function atualizarDisciplina(): void
    {
        requireProfile('Coordenador de Curso');
        verifyCsrf();
        $curso = $this->cursoObrigatorio();
        $disciplina = new Disciplina();
        $disciplinaId = (int) ($_POST['id'] ?? 0);
        if (!$disciplina->belongsToCourse($disciplinaId, (int) $curso['id'])) {
            flash('error', 'Disciplina nao encontrada para o seu curso.');
            redirect('/coordenador/materias');
        }

        $disciplina->update($disciplinaId, $this->disciplinaData((int) $curso['id']));
        audit('Coordenador', 'atualizacao_disciplina', 'Disciplina atualizada pelo coordenador.', ['disciplina_id' => $disciplinaId]);
        flash('success', 'Disciplina atualizada.');
        redirect('/coordenador/materias');
    }

    public function excluirDisciplina(): void
    {
        requireProfile('Coordenador de Curso');
        verifyCsrf();
        $curso = $this->cursoObrigatorio();
        $disciplina = new Disciplina();
        $disciplinaId = (int) ($_POST['id'] ?? 0);
        if (!$disciplina->belongsToCourse($disciplinaId, (int) $curso['id'])) {
            flash('error', 'Disciplina nao encontrada para o seu curso.');
            redirect('/coordenador/materias');
        }

        try {
            $disciplina->delete($disciplinaId);
        } catch (\Throwable) {
            $disciplina->update($disciplinaId, ['situacao' => 'inativa']);
        }
        audit('Coordenador', 'exclusao_disciplina', 'Disciplina removida ou inativada pelo coordenador.', ['disciplina_id' => $disciplinaId]);
        flash('success', 'Disciplina removida ou inativada.');
        redirect('/coordenador/materias');
    }

    public function reservasAulas(): void
    {
        requireProfile('Coordenador de Curso');
        $curso = $this->cursoAtual();
        $cursoId = (int) ($curso['id'] ?? 0);

        $this->view('coordenador/reservas-aulas', [
            'title' => 'Reservas de Aula',
            'curso' => $curso,
            'reservas' => $cursoId > 0 ? (new ReservaAula())->withDetailsByCourse($cursoId) : [],
            'disciplinas' => $cursoId > 0 ? (new Disciplina())->withDetailsByCourse($cursoId) : [],
            'professores' => (new User())->byProfile('Professor'),
            'salas' => (new Sala())->all('nome'),
        ]);
    }

    public function salvarReservaAula(): void
    {
        requireProfile('Coordenador de Curso');
        verifyCsrf();
        $curso = $this->cursoObrigatorio('/coordenador/reservas-aulas');
        $data = $this->aulaData((int) $curso['id'], '/coordenador/reservas-aulas');

        $reservaAula = new ReservaAula();
        if ($reservaAula->hasScheduleConflict($data['sala_nome'], $data['dia_semana'], $data['horario_inicio'], $data['horario_fim'])) {
            flash('error', 'Ja existe uma aula ativa para esta sala, dia e horario.');
            redirect('/coordenador/reservas-aulas');
        }

        $reservaAula->create($data);
        audit('Coordenador', 'criacao_reserva_aula', 'Reserva de aula criada pelo coordenador.', ['curso_id' => $curso['id']]);
        flash('success', 'Reserva de aula criada.');
        redirect('/coordenador/reservas-aulas');
    }

    public function atualizarReservaAula(): void
    {
        requireProfile('Coordenador de Curso');
        verifyCsrf();
        $curso = $this->cursoObrigatorio('/coordenador/reservas-aulas');
        $reservaId = (int) ($_POST['id'] ?? 0);
        $reservaAula = new ReservaAula();
        if (!$reservaAula->belongsToCourse($reservaId, (int) $curso['id'])) {
            flash('error', 'Reserva de aula nao encontrada para o seu curso.');
            redirect('/coordenador/reservas-aulas');
        }

        $data = $this->aulaData((int) $curso['id'], '/coordenador/reservas-aulas');
        if ($data['situacao'] === 'ativa' && $reservaAula->hasScheduleConflict($data['sala_nome'], $data['dia_semana'], $data['horario_inicio'], $data['horario_fim'], $reservaId)) {
            flash('error', 'Ja existe uma aula ativa para esta sala, dia e horario.');
            redirect('/coordenador/reservas-aulas');
        }

        $reservaAula->update($reservaId, $data);
        audit('Coordenador', 'atualizacao_reserva_aula', 'Reserva de aula atualizada pelo coordenador.', ['reserva_aula_id' => $reservaId]);
        flash('success', 'Reserva de aula atualizada.');
        redirect('/coordenador/reservas-aulas');
    }

    public function excluirReservaAula(): void
    {
        requireProfile('Coordenador de Curso');
        verifyCsrf();
        $curso = $this->cursoObrigatorio('/coordenador/reservas-aulas');
        $reservaId = (int) ($_POST['id'] ?? 0);
        $reservaAula = new ReservaAula();
        if (!$reservaAula->belongsToCourse($reservaId, (int) $curso['id'])) {
            flash('error', 'Reserva de aula nao encontrada para o seu curso.');
            redirect('/coordenador/reservas-aulas');
        }

        $reservaAula->delete($reservaId);
        audit('Coordenador', 'exclusao_reserva_aula', 'Reserva de aula excluida pelo coordenador.', ['reserva_aula_id' => $reservaId]);
        flash('success', 'Reserva de aula excluida.');
        redirect('/coordenador/reservas-aulas');
    }

    public function chavesAutorizadas(): void
    {
        redirect('/coordenador/materias');
    }

    private function cursoAtual(): ?array
    {
        $cursoId = (int) (currentUser()['curso_id'] ?? 0);
        if ($cursoId <= 0) {
            return null;
        }
        return (new Curso())->find($cursoId);
    }

    private function cursoObrigatorio(string $redirectTo = '/coordenador/materias'): array
    {
        $curso = $this->cursoAtual();
        if (!$curso) {
            flash('error', 'Vincule este coordenador a um curso antes de cadastrar materias.');
            redirect($redirectTo);
        }
        return $curso;
    }

    private function disciplinaData(int $cursoId): array
    {
        return [
            'curso_id' => $cursoId,
            'nome' => trim((string) $_POST['nome']),
            'periodo_referencia' => trim((string) $_POST['periodo_referencia']),
            'professor_id' => $_POST['professor_id'] ?: null,
            'observacao' => $_POST['observacao'] ?? null,
            'situacao' => $_POST['situacao'] ?? 'ativa',
        ];
    }

    private function aulaData(int $cursoId, string $redirectTo): array
    {
        $disciplina = $this->disciplinaObrigatoriaDoCurso((int) ($_POST['disciplina_id'] ?? 0), $cursoId, $redirectTo);
        $professorId = (int) ($_POST['professor_id'] ?? 0);
        $inicio = (string) ($_POST['horario_inicio'] ?? '');
        $fim = (string) ($_POST['horario_fim'] ?? '');
        $periodoAcademico = trim((string) ($_POST['periodo_academico'] ?? ''));
        $salaNome = trim((string) ($_POST['sala_nome'] ?? ''));
        $turma = trim((string) ($_POST['turma'] ?? ''));
        $diaSemana = trim((string) ($_POST['dia_semana'] ?? ''));

        if ($professorId <= 0 || !($professor = (new User())->findWithProfile($professorId)) || comparableProfile((string) ($professor['perfil_nome'] ?? '')) !== comparableProfile('Professor')) {
            flash('error', 'Selecione um professor valido.');
            redirect($redirectTo);
        }
        if ($periodoAcademico === '' || $salaNome === '' || $turma === '' || $diaSemana === '') {
            flash('error', 'Preencha todos os campos obrigatorios da reserva de aula.');
            redirect($redirectTo);
        }
        if ($inicio === '' || $fim === '' || $fim <= $inicio) {
            flash('error', 'Informe um horario final posterior ao horario inicial.');
            redirect($redirectTo);
        }

        return [
            'usuario_id' => currentUser()['id'],
            'professor_id' => $professorId,
            'disciplina_id' => (int) $disciplina['id'],
            'periodo_academico' => $periodoAcademico,
            'sala_nome' => $salaNome,
            'turma' => $turma,
            'dia_semana' => $diaSemana,
            'horario_inicio' => $inicio,
            'horario_fim' => $fim,
            'disciplina' => (string) $disciplina['nome'],
            'observacao' => $_POST['observacao'] ?? null,
            'situacao' => $_POST['situacao'] ?? 'ativa',
        ];
    }

    private function disciplinaObrigatoriaDoCurso(int $disciplinaId, int $cursoId, string $redirectTo): array
    {
        $disciplinaModel = new Disciplina();
        if ($disciplinaId <= 0 || !$disciplinaModel->belongsToCourse($disciplinaId, $cursoId)) {
            flash('error', 'Selecione uma materia do seu curso.');
            redirect($redirectTo);
        }

        $disciplina = $disciplinaModel->find($disciplinaId);
        if (!$disciplina) {
            flash('error', 'Materia nao encontrada.');
            redirect($redirectTo);
        }
        return $disciplina;
    }
}
