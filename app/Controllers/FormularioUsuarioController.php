<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\SolicitacaoUsuario;

// Entrada publica usada pelo Google Apps Script para enviar pedidos de cadastro.
class FormularioUsuarioController extends Controller
{
    public function receber(): void
    {
        $dados = $this->dadosRecebidos();
        if (!$this->tokenValido((string) ($dados['token'] ?? ($_SERVER['HTTP_X_FORM_TOKEN'] ?? '')))) {
            $this->json(['ok' => false, 'mensagem' => 'Token inválido.'], 403);
            return;
        }

        $nome = trim((string) ($dados['nome'] ?? ''));
        $email = mb_strtolower(trim((string) ($dados['email'] ?? '')));
        if ($nome === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->json(['ok' => false, 'mensagem' => 'Nome e e-mail válido são obrigatórios.'], 422);
            return;
        }

        $solicitacoes = new SolicitacaoUsuario();
        if ($solicitacoes->emailPendenteOuUsuarioExiste($email)) {
            $this->json(['ok' => false, 'mensagem' => 'Já existe usuário ou solicitação pendente para este e-mail.'], 409);
            return;
        }

        $id = $solicitacoes->receber($dados + ['email' => $email]);
        audit('Solicitações', 'recebimento', 'Solicitação de usuário recebida por formulário.', ['solicitacao_id' => $id, 'email' => $email]);
        $this->json(['ok' => true, 'id' => $id, 'mensagem' => 'Solicitação recebida.']);
    }

    private function dadosRecebidos(): array
    {
        $json = file_get_contents('php://input') ?: '';
        $dados = json_decode($json, true);
        if (!is_array($dados) && str_contains($json, '\\"')) {
            $dados = json_decode(stripslashes($json), true);
        }
        return is_array($dados) ? $dados : $_POST;
    }

    private function tokenValido(string $token): bool
    {
        $esperado = (string) config('form_webhook_token', '');
        return $esperado !== '' && hash_equals($esperado, $token);
    }
}
