<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Curso;
use App\Models\Perfil;
use App\Models\SolicitacaoUsuario;
use App\Models\User;

class UsuarioController extends Controller
{
    public function index(): void
    {
        requireProfile('Desenvolvedor');
        $this->view('usuarios/index', [
            'title' => 'Usuarios',
            'usuarios' => (new User())->allWithProfile(),
        ]);
    }

    public function create(): void
    {
        requireProfile('Desenvolvedor');
        $this->view('usuarios/create', [
            'title' => 'Novo usuario',
            'perfis' => (new Perfil())->all('nivel DESC'),
            'cursos' => (new Curso())->all('nome'),
        ]);
    }

    public function store(): void
    {
        requireProfile('Desenvolvedor');
        verifyCsrf();
        (new User())->create([
            'nome' => trim((string) $_POST['nome']),
            'email' => trim((string) $_POST['email']),
            'senha_hash' => password_hash((string) $_POST['senha'], PASSWORD_DEFAULT),
            'perfil_id' => (int) $_POST['perfil_id'],
            'situacao' => $_POST['situacao'] ?? 'ativo',
            'curso_id' => ($_POST['curso_id'] ?? '') ?: null,
        ]);
        flash('success', 'Usuario criado.');
        redirect('/desenvolvedor/usuarios');
    }

    public function edit(): void
    {
        requireProfile('Desenvolvedor');
        $this->view('usuarios/edit', [
            'title' => 'Editar usuario',
            'usuario' => (new User())->findWithProfile((int) $_GET['id']),
            'perfis' => (new Perfil())->all('nivel DESC'),
            'cursos' => (new Curso())->all('nome'),
        ]);
    }

    public function update(): void
    {
        requireProfile('Desenvolvedor');
        verifyCsrf();
        $data = [
            'nome' => trim((string) $_POST['nome']),
            'email' => trim((string) $_POST['email']),
            'perfil_id' => (int) $_POST['perfil_id'],
            'situacao' => $_POST['situacao'] ?? 'ativo',
            'curso_id' => ($_POST['curso_id'] ?? '') ?: null,
        ];
        if (!empty($_POST['senha'])) {
            $data['senha_hash'] = password_hash((string) $_POST['senha'], PASSWORD_DEFAULT);
        }
        (new User())->update((int) $_POST['id'], $data);
        flash('success', 'Usuario atualizado.');
        redirect('/desenvolvedor/usuarios');
    }

    public function destroy(): void
    {
        requireProfile('Desenvolvedor');
        verifyCsrf();
        $id = (int) $_POST['id'];
        $user = new User();
        if (!$user->deleteSafely($id)) {
            $user->anonymize($id);
        }
        flash('success', 'Usuario removido.');
        redirect('/desenvolvedor/usuarios');
    }

    public function solicitacoes(): void
    {
        requireProfile('Desenvolvedor');
        $this->view('usuarios/solicitacoes', [
            'title' => 'Solicitacoes de usuarios',
            'solicitacoes' => (new SolicitacaoUsuario())->recentes(),
            'perfis' => (new Perfil())->all('nivel DESC'),
        ]);
    }

    public function aprovarSolicitacao(): void
    {
        requireProfile('Desenvolvedor');
        verifyCsrf();
        $solicitacoes = new SolicitacaoUsuario();
        $solicitacao = $solicitacoes->find((int) ($_POST['id'] ?? 0));
        if (!$solicitacao || $solicitacao['situacao'] !== 'pendente') {
            flash('error', 'Solicitacao nao encontrada ou ja analisada.');
            redirect('/desenvolvedor/usuarios/solicitacoes');
        }
        if ((new User())->findByEmail((string) $solicitacao['email'])) {
            flash('error', 'Ja existe um usuario com este e-mail.');
            redirect('/desenvolvedor/usuarios/solicitacoes');
        }

        $senha = trim((string) ($_POST['senha'] ?? ''));
        if ($senha === '') {
            flash('error', 'Informe uma senha inicial para aprovar a solicitacao.');
            redirect('/desenvolvedor/usuarios/solicitacoes');
        }

        $usuarioId = (new User())->create([
            'nome' => trim((string) ($_POST['nome'] ?: $solicitacao['nome'])),
            'email' => trim((string) ($_POST['email'] ?: $solicitacao['email'])),
            'senha_hash' => password_hash($senha, PASSWORD_DEFAULT),
            'perfil_id' => (int) $_POST['perfil_id'],
            'situacao' => $_POST['situacao'] ?? 'ativo',
            'curso_id' => ($_POST['curso_id'] ?? '') ?: null,
        ]);
        $solicitacoes->aprovar((int) $solicitacao['id'], $usuarioId, (int) currentUser()['id']);
        audit('Usuarios', 'aprovacao', 'Solicitacao aprovada e usuario criado.', ['solicitacao_id' => $solicitacao['id'], 'usuario_id' => $usuarioId]);
        flash('success', 'Solicitacao aprovada e usuario criado.');
        redirect('/desenvolvedor/usuarios/solicitacoes');
    }

    public function recusarSolicitacao(): void
    {
        requireProfile('Desenvolvedor');
        verifyCsrf();
        $solicitacoes = new SolicitacaoUsuario();
        $solicitacao = $solicitacoes->find((int) ($_POST['id'] ?? 0));
        if (!$solicitacao || $solicitacao['situacao'] !== 'pendente') {
            flash('error', 'Solicitacao nao encontrada ou ja analisada.');
            redirect('/desenvolvedor/usuarios/solicitacoes');
        }
        $solicitacoes->recusar((int) $solicitacao['id'], (int) currentUser()['id']);
        audit('Usuarios', 'recusa', 'Solicitacao de usuario recusada.', ['solicitacao_id' => $solicitacao['id']]);
        flash('success', 'Solicitacao recusada.');
        redirect('/desenvolvedor/usuarios/solicitacoes');
    }

    public function limparSolicitacoesAnalisadas(): void
    {
        requireProfile('Desenvolvedor');
        verifyCsrf();
        $total = (new SolicitacaoUsuario())->limparAnalisadas();
        audit('Usuarios', 'limpeza', 'Solicitacoes analisadas removidas.', ['total' => $total]);
        flash('success', $total . ' solicitacao(oes) aprovada(s) ou recusada(s) removida(s).');
        redirect('/desenvolvedor/usuarios/solicitacoes');
    }
}
