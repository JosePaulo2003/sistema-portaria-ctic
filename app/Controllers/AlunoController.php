<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\BloqueioChave;
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
