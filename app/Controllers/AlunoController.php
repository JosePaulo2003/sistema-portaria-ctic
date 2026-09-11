<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\BloqueioChave;
use App\Models\PermissaoSala;
use App\Models\Sala;
use App\Models\User;
use App\Services\RetiradaChaveService;

// Area do aluno: consulta de informacoes e retirada de chaves quando autorizada.
class AlunoController extends Controller
{
    public function index(): void
    {
        $this->salasHome('Aluno');
    }

    public function consultaSalas(): void
    {
        requireProfile('Aluno');
        $this->view('aluno/consulta-salas', [
            'title' => 'Consulta de Salas',
            'salas' => (new Sala())->listDisponibilidade($_GET),
        ]);
    }

    public function retiradasAutorizadas(): void
    {
        requireProfile('Aluno');
        if (!(new PermissaoSala())->usuarioTemChaveAtribuida((int) currentUser()['id']) && !isDeveloper()) {
            http_response_code(403);
            exit('Acesso negado.');
        }

        $this->view('aluno/retiradas', [
            'title' => 'Retiradas',
            'salas' => (new Sala())->chavesParaRetirada(currentUser()),
            'bloqueio' => (new BloqueioChave())->ativoParaUsuario((int) currentUser()['id']),
        ]);
    }

    public function retirarChaveAutorizada(): void
    {
        requireProfile('Aluno');
        verifyCsrf();

        $retorno = '/retiradas-autorizadas';
        if (!(new PermissaoSala())->usuarioTemChaveAtribuida((int) currentUser()['id']) && !isDeveloper()) {
            http_response_code(403);
            exit('Acesso negado.');
        }

        $bloqueio = (new BloqueioChave())->ativoParaUsuario((int) currentUser()['id']);
        if ($bloqueio) {
            flash('error', 'Voce esta temporariamente bloqueado para retirar chaves ate ' . date('d/m/Y H:i', strtotime($bloqueio['fim_em'])) . '.');
            redirect($retorno);
        }

        $senha = (string) ($_POST['senha_confirmacao'] ?? '');
        $usuario = (new User())->find((int) currentUser()['id']);
        if ($senha === '' || !$usuario || !password_verify($senha, (string) $usuario['senha_hash'])) {
            flash('error', 'Confirme sua senha corretamente para registrar a retirada.');
            redirect($retorno);
        }

        $salaId = (int) ($_POST['sala_id'] ?? 0);
        if (!(new Sala())->chavePodeSerRetirada($salaId, currentUser())) {
            flash('error', 'Esta chave nao esta disponivel para retirada.');
            redirect($retorno);
        }

        try {
            $solicitacao = (new RetiradaChaveService())->solicitar(currentUser(), $salaId, $_POST['observacao'] ?? null);
        } catch (\RuntimeException $error) {
            flash('error', $error->getMessage());
            redirect($retorno);
        }
        $_SESSION['_codigo_retirada'] = $solicitacao;
        flash('success', 'Solicitação enviada à Portaria. Apresente a senha temporária de 4 dígitos para receber a chave.');
        redirect($retorno);
    }
}
