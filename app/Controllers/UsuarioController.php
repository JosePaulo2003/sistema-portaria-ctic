<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Perfil;
use App\Models\SolicitacaoUsuario;
use App\Models\User;

// Cadastro e manutenção de usuários feita pelo desenvolvedor.
class UsuarioController extends Controller
{
    public function index(): void
    {
        requireProfile('Desenvolvedor');
        $this->view('usuarios/index', ['title' => 'Usuários', 'usuarios' => (new User())->allWithProfile()]);
    }

    public function create(): void
    {
        requireProfile('Desenvolvedor');
        $this->view('usuarios/create', ['title' => 'Novo usuário', 'perfis' => (new Perfil())->all('nivel DESC')]);
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
        ]);
        flash('success', 'Usuário criado.');
        redirect('/desenvolvedor/usuarios');
    }

    public function edit(): void
    {
        requireProfile('Desenvolvedor');
        $this->view('usuarios/edit', [
            'title' => 'Editar usuário',
            'usuario' => (new User())->findWithProfile((int) $_GET['id']),
            'perfis' => (new Perfil())->all('nivel DESC'),
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
        ];
        if (!empty($_POST['senha'])) {
            $data['senha_hash'] = password_hash((string) $_POST['senha'], PASSWORD_DEFAULT);
        }
        (new User())->update((int) $_POST['id'], $data);
        flash('success', 'Usuário atualizado.');
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
        flash('success', 'Usuário removido.');
        redirect('/desenvolvedor/usuarios');
    }

    public function solicitacoes(): void
    {
        requireProfile('Desenvolvedor');
        $this->view('usuarios/solicitacoes', [
            'title' => 'Solicitações de usuários',
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
            flash('error', 'Solicitação não encontrada ou já analisada.');
            redirect('/desenvolvedor/usuarios/solicitacoes');
        }
        if ((new User())->findByEmail((string) $solicitacao['email'])) {
            flash('error', 'Já existe um usuário com este e-mail.');
            redirect('/desenvolvedor/usuarios/solicitacoes');
        }

        $usuarioId = (new User())->create([
            'nome' => trim((string) ($_POST['nome'] ?: $solicitacao['nome'])),
            'email' => trim((string) ($_POST['email'] ?: $solicitacao['email'])),
            'senha_hash' => password_hash((string) ($_POST['senha'] ?: '12345678'), PASSWORD_DEFAULT),
            'perfil_id' => (int) $_POST['perfil_id'],
            'situacao' => $_POST['situacao'] ?? 'ativo',
        ]);
        $solicitacoes->aprovar((int) $solicitacao['id'], $usuarioId, (int) currentUser()['id']);
        audit('Usuários', 'aprovação', 'Solicitação aprovada e usuário criado.', ['solicitacao_id' => $solicitacao['id'], 'usuario_id' => $usuarioId]);
        flash('success', 'Solicitação aprovada e usuário criado.');
        redirect('/desenvolvedor/usuarios/solicitacoes');
    }

    public function recusarSolicitacao(): void
    {
        requireProfile('Desenvolvedor');
        verifyCsrf();
        $solicitacoes = new SolicitacaoUsuario();
        $solicitacao = $solicitacoes->find((int) ($_POST['id'] ?? 0));
        if (!$solicitacao || $solicitacao['situacao'] !== 'pendente') {
            flash('error', 'Solicitação não encontrada ou já analisada.');
            redirect('/desenvolvedor/usuarios/solicitacoes');
        }
        $solicitacoes->recusar((int) $solicitacao['id'], (int) currentUser()['id']);
        audit('Usuários', 'recusa', 'Solicitação de usuário recusada.', ['solicitacao_id' => $solicitacao['id']]);
        flash('success', 'Solicitação recusada.');
        redirect('/desenvolvedor/usuarios/solicitacoes');
    }
}
