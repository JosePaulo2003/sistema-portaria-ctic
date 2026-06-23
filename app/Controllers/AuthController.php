<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;

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

    public function login(): void
    {
        verifyCsrf();
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['senha'] ?? '');

        if ($this->loginBloqueado($email)) {
            flash('error', 'Muitas tentativas de acesso. Aguarde alguns minutos e tente novamente.');
            redirect('/login');
        }

        $userModel = new User();
        $user = $userModel->findByEmail($email);
        if (!$user || !password_verify($password, $user['senha_hash']) || $user['situacao'] !== 'ativo') {
            $this->registrarFalhaLogin($email);
            flash('error', 'E-mail, senha ou situação inválidos.');
            redirect('/login');
        }

        $this->limparFalhasLogin($email);

        session_regenerate_id(true);
        $_SESSION = [
            'user_id' => (int) $user['id'],
            '_auth_fingerprint' => authSessionFingerprint(),
            '_auth_created_at' => time(),
            '_auth_last_activity' => time(),
        ];

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

    private function loginBloqueado(string $email): bool
    {
        $tentativa = $_SESSION['_login_attempts'][$this->chaveTentativa($email)] ?? null;
        return is_array($tentativa) && (int) ($tentativa['blocked_until'] ?? 0) > time();
    }

    private function registrarFalhaLogin(string $email): void
    {
        $key = $this->chaveTentativa($email);
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

    private function limparFalhasLogin(string $email): void
    {
        unset($_SESSION['_login_attempts'][$this->chaveTentativa($email)]);
    }

    private function chaveTentativa(string $email): string
    {
        return hash('sha256', mb_strtolower($email) . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'local'));
    }
}
