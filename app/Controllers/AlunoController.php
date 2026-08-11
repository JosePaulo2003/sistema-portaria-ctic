<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Movimentacao;
use App\Models\PermissaoSala;
use App\Models\Sala;
use App\Models\User;

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
        requireAuth();
        if (!(new PermissaoSala())->usuarioTemAlgumAcesso((int) currentUser()['id']) && !isDeveloper()) {
            http_response_code(403);
            exit('Acesso negado.');
        }

        $this->view('aluno/retiradas', [
            'title' => 'Retiradas',
            'salas' => (new Sala())->chavesParaRetirada(currentUser()),
        ]);
    }

    public function retirarChaveAutorizada(): void
    {
        requireAuth();
        verifyCsrf();

        $retorno = '/retiradas-autorizadas';
        if (!(new PermissaoSala())->usuarioTemAlgumAcesso((int) currentUser()['id']) && !isDeveloper()) {
            http_response_code(403);
            exit('Acesso negado.');
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

        audit('Chaves', 'retirada_autorizada', 'Retirada de chave autorizada registrada.');
        flash('success', 'Retirada registrada.');
        redirect($retorno);
    }
}
