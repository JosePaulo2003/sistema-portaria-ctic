<?php
declare(strict_types=1);

/**
 * GUIA DE MANUTENCAO: Este arquivo controla login, logout e recuperação de senha; qualquer descuido aqui vira convite para incidente.
 *
 * Ponto de atencao: Controller não é depósito de regra de negócio. Se ele começar a prever o futuro, extraia um Service e devolva a bola de cristal.
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Models\RecuperacaoSenha;
use App\Models\User;
use App\Services\EmailService;
use Throwable;

// Autentica usuários e protege o login contra tentativas repetidas.
class AuthController extends Controller
{
    public function loginForm(): void
    {
        if (currentUser()) {
            redirect(moduleForProfile(userProfile() ?? ''));
        }
        $this->view('auth/login', ['title' => 'Entrar'], 'auth');
    }

    public function portariaForm(): void
    {
        if (currentUser()) {
            redirect(moduleForProfile(userProfile() ?? ''));
        }

        $userModel = new User();
        $agenteId = max(0, (int) ($_GET['agente_id'] ?? 0));
        $agenteSelecionado = $agenteId > 0
            ? $userModel->activePortariaAgentById($agenteId)
            : null;

        if ($agenteId > 0 && !$agenteSelecionado) {
            flash('error', 'Agente de portaria indisponivel. Selecione outro nome.');
            redirect('/login/portaria');
        }

        $this->view('auth/portaria', [
            'title' => 'Acesso Portaria',
            'agentesPortaria' => $agenteId === 0 ? $userModel->activePortariaAgents() : [],
            'agenteSelecionado' => $agenteSelecionado,
        ], 'auth');
    }

    public function forgotForm(): void
    {
        if (currentUser()) {
            redirect(moduleForProfile(userProfile() ?? ''));
        }
        $this->view('auth/forgot', ['title' => 'Recuperar senha'], 'auth');
    }

    public function forgot(): void
    {
        verifyCsrf();
        $email = trim((string) ($_POST['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Informe um e-mail valido.');
            redirect('/recuperar-senha');
        }

        $user = (new User())->findByEmail($email);
        if ($user && ($user['situacao'] ?? '') === 'ativo') {
            $recuperacoes = new RecuperacaoSenha();
            $ipHash = hash('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? 'local'));

            if ($recuperacoes->podeSolicitar((int) $user['id'], $ipHash)) {
                $codigo = $this->gerarCodigoRecuperacao();
                $recuperacaoId = $recuperacoes->criar((int) $user['id'], $this->normalizarCodigo($codigo), $ipHash);

                try {
                    (new EmailService())->enviarRecuperacaoSenha($user, $codigo);
                    systemLog('info', 'Auth', 'E-mail de recuperacao de senha enviado.', [
                        'usuario_id_solicitado' => (int) $user['id'],
                    ]);
                } catch (Throwable $exception) {
                    $recuperacoes->invalidar($recuperacaoId);
                    systemLog('error', 'Auth', 'Falha ao enviar e-mail de recuperacao de senha.', [
                        'usuario_id_solicitado' => (int) $user['id'],
                        'erro' => $exception->getMessage(),
                    ]);
                }
            } else {
                systemLog('warning', 'Auth', 'Limite de recuperacao de senha atingido.', [
                    'usuario_id_solicitado' => (int) $user['id'],
                ]);
            }
        }

        $_SESSION['_recuperacao_email'] = $email;
        flash('success', 'Se existir uma conta ativa para esse e-mail, enviaremos um código de redefinição válido por 30 minutos.');
        redirect('/redefinir-senha');
    }

    public function resetForm(): void
    {
        if (currentUser()) {
            redirect(moduleForProfile(userProfile() ?? ''));
        }

        $this->view('auth/reset', [
            'title' => 'Redefinir senha',
            'email' => (string) ($_SESSION['_recuperacao_email'] ?? ''),
        ], 'auth');
    }

    public function reset(): void
    {
        verifyCsrf();
        $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
        $codigo = $this->normalizarCodigo((string) ($_POST['codigo'] ?? ''));
        $senha = (string) ($_POST['senha'] ?? '');
        $confirmacao = (string) ($_POST['senha_confirmacao'] ?? '');

        $_SESSION['_recuperacao_email'] = $email;
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/\A[A-Z0-9]{8}\z/', $codigo)) {
            flash('error', 'Confira o e-mail e o código recebido.');
            redirect('/redefinir-senha');
        }
        if (mb_strlen($senha) < 8 || strlen($senha) > 255 || str_contains($senha, "\0")) {
            flash('error', 'A nova senha deve ter entre 8 e 255 caracteres.');
            redirect('/redefinir-senha');
        }
        if (!hash_equals($senha, $confirmacao)) {
            flash('error', 'A confirmação não corresponde à nova senha.');
            redirect('/redefinir-senha');
        }

        if (!(new RecuperacaoSenha())->redefinir($email, $codigo, password_hash($senha, PASSWORD_DEFAULT))) {
            flash('error', 'Código inválido, expirado ou com o limite de tentativas atingido. Solicite um novo código se necessário.');
            redirect('/redefinir-senha');
        }

        unset($_SESSION['_recuperacao_email']);
        systemLog('info', 'Auth', 'Senha redefinida com código enviado por e-mail.');
        flash('success', 'Senha redefinida com sucesso. Você já pode entrar.');
        redirect('/login');
    }

    private function gerarCodigoRecuperacao(): string
    {
        $alfabeto = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $codigo = '';
        for ($i = 0; $i < 8; $i++) {
            $codigo .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
        }
        return substr($codigo, 0, 4) . '-' . substr($codigo, 4);
    }

    private function normalizarCodigo(string $codigo): string
    {
        return strtoupper((string) preg_replace('/[^a-zA-Z0-9]/', '', trim($codigo)));
    }

    public function login(): void
    {
        verifyCsrf();
        $identificador = trim((string) ($_POST['identificador'] ?? ''));
        $password = (string) ($_POST['senha'] ?? '');
        $chaveLogin = 'geral:' . $identificador;

        if ($this->loginBloqueado($chaveLogin)) {
            flash('error', 'Muitas tentativas de acesso. Aguarde alguns minutos e tente novamente.');
            redirect('/login');
        }

        $userModel = new User();
        $userModel->purgeExpiredVisitors();
        $usuariosEncontrados = $userModel->findByLoginIdentifier($identificador);
        if (count($usuariosEncontrados) > 1) {
            flash('error', 'Existe mais de um usuario com esse nome. Entre usando o e-mail.');
            redirect('/login');
        }
        $user = $usuariosEncontrados[0] ?? null;

        if (!$user || !password_verify($password, $user['senha_hash']) || $user['situacao'] !== 'ativo') {
            $this->registrarFalhaLogin($chaveLogin);
            flash('error', 'Nome, e-mail ou senha invalidos.');
            redirect('/login');
        }

        $this->limparFalhasLogin($chaveLogin);
        $this->concluirLogin($user, $password, $userModel);
    }

    public function loginPortaria(): void
    {
        verifyCsrf();
        $agenteId = max(0, (int) ($_POST['agente_id'] ?? 0));
        $password = (string) ($_POST['senha'] ?? '');
        $chaveLogin = 'portaria:' . $agenteId;
        $retornoLogin = $agenteId > 0
            ? '/login/portaria?agente_id=' . $agenteId
            : '/login/portaria';

        if ($this->loginBloqueado($chaveLogin)) {
            flash('error', 'Muitas tentativas de acesso. Aguarde alguns minutos e tente novamente.');
            redirect($retornoLogin);
        }

        $userModel = new User();
        $user = $agenteId > 0 ? $userModel->activePortariaAgentById($agenteId) : null;

        if (!$user || !password_verify($password, $user['senha_hash'])) {
            $this->registrarFalhaLogin($chaveLogin);
            flash('error', 'Agente ou senha invalidos.');
            redirect($retornoLogin);
        }

        $this->limparFalhasLogin($chaveLogin);
        $this->concluirLogin($user, $password, $userModel);
    }

    private function concluirLogin(array $user, string $password, User $userModel): never
    {

        session_regenerate_id(true);
        $_SESSION = [
            'user_id' => (int) $user['id'],
            '_auth_fingerprint' => authSessionFingerprint(),
            '_auth_created_at' => time(),
            '_auth_expires_at' => comparableProfile((string) $user['perfil_nome']) === comparableProfile('Visitante')
                && !empty($user['acesso_expira_em'])
                ? strtotime((string) $user['acesso_expira_em'])
                : null,
        ];
        refreshAuthSessionCookie();

        if (password_needs_rehash($user['senha_hash'], PASSWORD_DEFAULT)) {
            $userModel->update((int) $user['id'], ['senha_hash' => password_hash($password, PASSWORD_DEFAULT)]);
        }

        $userModel->touchLogin((int) $user['id']);
        audit('Auth', 'login', 'Usuário autenticado.');
        redirect(moduleForProfile($user['perfil_nome']));
    }

    public function logout(): void
    {
        verifyCsrf();
        audit('Auth', 'logout', 'Usuário encerrou a sessão.');
        $_SESSION = [];
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'] ?? '/',
            'domain' => $params['domain'] ?? '',
            'secure' => (bool) ($params['secure'] ?? false),
            'httponly' => (bool) ($params['httponly'] ?? true),
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
        session_destroy();
        redirect('/login');
    }

    private function loginBloqueado(string $identifier): bool
    {
        $tentativa = $_SESSION['_login_attempts'][$this->chaveTentativa($identifier)] ?? null;
        return is_array($tentativa) && (int) ($tentativa['blocked_until'] ?? 0) > time();
    }

    private function registrarFalhaLogin(string $identifier): void
    {
        $key = $this->chaveTentativa($identifier);
        $agora = time();
        $tentativa = $_SESSION['_login_attempts'][$key] ?? ['count' => 0, 'first_at' => $agora, 'blocked_until' => 0];
        if ($agora - (int) ($tentativa['first_at'] ?? $agora) > 900) {
            $tentativa = ['count' => 0, 'first_at' => $agora, 'blocked_until' => 0];
        }
        $tentativa['count'] = (int) ($tentativa['count'] ?? 0) + 1;
        if ($tentativa['count'] >= 5) {
            $tentativa['blocked_until'] = $agora + 300;
            $tentativa['count'] = 0;
            $tentativa['first_at'] = $agora;
        }
        $_SESSION['_login_attempts'][$key] = $tentativa;
    }

    private function limparFalhasLogin(string $identifier): void
    {
        unset($_SESSION['_login_attempts'][$this->chaveTentativa($identifier)]);
    }

    private function chaveTentativa(string $identifier): string
    {
        return hash('sha256', mb_strtolower(trim($identifier)) . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'local'));
    }
}
