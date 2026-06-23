<?php
declare(strict_types=1);

use App\Core\Database;
use App\Models\LogAuditoria;

// Funcoes globais de apoio para URL, seguranca, sessao, autorizacao e auditoria.
function config(string $key, mixed $default = null): mixed
{
    return $GLOBALS['config'][$key] ?? $default;
}

function basePath(): string
{
    $configured = trim((string) config('app_base_path', ''), '/');
    if ($configured !== '') {
        return '/' . $configured;
    }
    $script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    return rtrim($script === '/' ? '' : $script, '/');
}

function baseUrl(string $path = ''): string
{
    $base = basePath();
    $path = '/' . ltrim($path, '/');
    return rtrim($base, '/') . ($path === '/' ? '' : $path);
}

function assetUrl(string $path): string
{
    return baseUrl('public/' . ltrim($path, '/'));
}

function redirect(string $path): never
{
    header('Location: ' . baseUrl($path));
    exit;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrfToken(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrfField(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrfToken()) . '">';
}

function verifyCsrf(): void
{
    $token = $_POST['_csrf'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['_csrf'] ?? '', $token)) {
        http_response_code(419);
        exit('CSRF invÃ¡lido.');
    }
}

function flash(?string $key = null, ?string $message = null): string
{
    if ($key !== null && $message !== null) {
        $_SESSION['_flash'][$key] = $message;
        return '';
    }
    if ($key === null) {
        $messages = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        $html = '';
        foreach ($messages as $type => $text) {
            $html .= '<div class="flash flash--' . e($type) . '">' . e($text) . '</div>';
        }
        return $html;
    }
    $text = $_SESSION['_flash'][$key] ?? '';
    unset($_SESSION['_flash'][$key]);
    return (string) $text;
}

function currentUser(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    if (!empty($_SESSION['_user_cache']) && (int) $_SESSION['_user_cache']['id'] === (int) $_SESSION['user_id']) {
        return $_SESSION['_user_cache'];
    }
    $stmt = Database::pdo()->prepare(
        'SELECT u.*, p.nome AS perfil_nome, p.nivel AS perfil_nivel
         FROM usuarios u
         JOIN perfis p ON p.id = u.perfil_id
         WHERE u.id = ? LIMIT 1'
    );
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    $_SESSION['_user_cache'] = $user ?: null;
    return $user ?: null;
}

function userProfile(): ?string
{
    return currentUser()['perfil_nome'] ?? null;
}

function isProfile(string|array $profiles): bool
{
    return in_array(userProfile(), (array) $profiles, true);
}

function isDeveloper(): bool
{
    return isProfile('Desenvolvedor');
}

function requireAuth(): void
{
    if (!currentUser()) {
        redirect('/login');
    }
}

function requireProfile(string|array $profiles): void
{
    requireAuth();
    if (!isProfile($profiles) && !isDeveloper()) {
        http_response_code(403);
        exit('Acesso negado.');
    }
}

function moduleForProfile(string $profile): string
{
    $normalizado = mb_strtolower($profile);
    if (str_contains($normalizado, 'secret')) {
        return '/secretario';
    }
    if (str_contains($normalizado, 'servi') && str_contains($normalizado, 'gerais')) {
        return '/servicos-gerais';
    }

    return match ($profile) {
        'Desenvolvedor' => '/desenvolvedor',
        'Administrativo' => '/administrativo',
        'Diretor' => '/diretor',
        'Secretário de Curso' => '/secretario',
        'Agente de Portaria' => '/portaria',
        'Professor' => '/professor',
        'Aluno Bolsista' => '/bolsista',
        'Aluno' => '/aluno',
        'Visitante' => '/visitante',
        'Serviços Gerais' => '/servicos-gerais',
        default => '/login',
    };
}

function audit(string $modulo, string $acao, string $descricao, array $context = []): void
{
    try {
        (new LogAuditoria())->create([
            'usuario_id' => currentUser()['id'] ?? null,
            'modulo' => $modulo,
            'acao' => $acao,
            'descricao' => $descricao,
            'contexto_json' => $context ? json_encode($context, JSON_UNESCAPED_UNICODE) : null,
            'ip_origem' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    } catch (Throwable) {
        // A auditoria nunca deve impedir a operação principal do usuário.
    }
}
