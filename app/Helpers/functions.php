<?php
declare(strict_types=1);

use App\Core\Database;
use App\Models\LogAuditoria;
use App\Models\LogSistema;

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
    $assetPath = ltrim($path, '/');
    $url = baseUrl('public/' . $assetPath);
    $file = dirname(__DIR__, 2) . '/public/' . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $assetPath);

    if (is_file($file)) {
        $url .= '?v=' . filemtime($file);
    }

    return $url;
}

function redirect(string $path): never
{
    header('Location: ' . baseUrl($path));
    exit;
}

function e(mixed $value): string
{
    return htmlspecialchars(fixMojibakeText((string) $value), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function fixMojibakeText(string $text): string
{
    if ($text === '' || (!str_contains($text, 'Ã') && !str_contains($text, 'Â'))) {
        return $text;
    }

    $current = $text;
    for ($i = 0; $i < 2; $i++) {
        $converted = @mb_convert_encoding($current, 'ISO-8859-1', 'UTF-8');
        if (!is_string($converted) || $converted === '' || !mb_check_encoding($converted, 'UTF-8')) {
            break;
        }
        if ($converted === $current) {
            break;
        }

        $current = $converted;
        if (!str_contains($current, 'Ã') && !str_contains($current, 'Â')) {
            break;
        }
    }

    return $current;
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

function notificationHtml(string $type, string $title, string $message, bool $autoDismiss = true): string
{
    $notificationType = preg_replace('/[^a-z0-9_-]+/', '', mb_strtolower($type)) ?: 'info';
    $text = trim($title . ': ' . $message);

    return '<span hidden data-browser-alert data-browser-alert-type="' . e($notificationType) . '" data-browser-alert-message="' . e($text) . '"></span>';
}

function notificationStackHtml(string $notifications): string
{
    return $notifications;
}

function appTimestamp(): string
{
    return date('Y-m-d H:i:s');
}

/**
 * Aceita datas digitadas no padrao brasileiro e o formato tecnico legado dos
 * controles HTML. O banco continua recebendo DATETIME no formato do MySQL.
 */
function parseDateTimeInput(string $value): ?\DateTimeImmutable
{
    $value = trim($value);
    if ($value === '') {
        return null;
    }

    foreach (['d/m/Y H:i', 'd/m/Y', 'Y-m-d\\TH:i', 'Y-m-d\\TH:i:s', 'Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d'] as $format) {
        $date = \DateTimeImmutable::createFromFormat('!' . $format, $value);
        $errors = \DateTimeImmutable::getLastErrors();
        $valid = $errors === false
            || ((int) ($errors['warning_count'] ?? 0) === 0 && (int) ($errors['error_count'] ?? 0) === 0);

        if ($date instanceof \DateTimeImmutable && $valid && $date->format($format) === $value) {
            return $date;
        }
    }

    return null;
}

function databaseDateTimeFromInput(string $value): ?string
{
    $date = parseDateTimeInput($value);
    return $date?->format('Y-m-d H:i:s');
}

function formatDateTimeBr(?string $value, string $fallback = '-'): string
{
    if ($value === null || trim($value) === '') {
        return $fallback;
    }

    $date = parseDateTimeInput($value);
    return $date?->format('d/m/Y H:i') ?? $fallback;
}

function formatDateBr(?string $value, string $fallback = '-'): string
{
    if ($value === null || trim($value) === '') {
        return $fallback;
    }

    $date = parseDateTimeInput($value);
    return $date?->format('d/m/Y') ?? $fallback;
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
        $notifications = '';
        foreach ($messages as $type => $text) {
            $notificationType = preg_replace('/[^a-z0-9_-]+/', '', mb_strtolower((string) $type)) ?: 'info';
            $title = match ($notificationType) {
                'error' => 'Atenção',
                'success' => 'Alteração realizada',
                default => 'Aviso',
            };
            $notifications .= notificationHtml($notificationType, $title, (string) $text);
        }
        return notificationStackHtml($notifications);
    }
    $text = $_SESSION['_flash'][$key] ?? '';
    unset($_SESSION['_flash'][$key]);
    return (string) $text;
}

function currentUser(): ?array
{
    if (!authSessionIsValid()) {
        return null;
    }
    if (!empty($_SESSION['_user_cache']) && (int) $_SESSION['_user_cache']['id'] === (int) $_SESSION['user_id']) {
        if (temporaryUserAccessExpired($_SESSION['_user_cache'])) {
            clearAuthSession();
            return null;
        }
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
    if ($user && temporaryUserAccessExpired($user)) {
        clearAuthSession();
        return null;
    }
    $_SESSION['_user_cache'] = $user ?: null;
    return $user ?: null;
}

function temporaryUserAccessExpired(?array $user): bool
{
    if (!$user || comparableProfile((string) ($user['perfil_nome'] ?? '')) !== comparableProfile('Visitante')) {
        return false;
    }

    $expiresAt = strtotime((string) ($user['acesso_expira_em'] ?? ''));
    return $expiresAt !== false && $expiresAt <= time();
}

function authSessionFingerprint(): string
{
    return hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? '') . '|' . ($_SERVER['REMOTE_ADDR'] ?? ''));
}

function clearAuthSession(): void
{
    unset(
        $_SESSION['user_id'],
        $_SESSION['_user_cache'],
        $_SESSION['_auth_fingerprint'],
        $_SESSION['_auth_last_activity'],
        $_SESSION['_auth_created_at'],
        $_SESSION['_auth_expires_at']
    );
}

function authSessionIsValid(): bool
{
    if (empty($_SESSION['user_id'])) {
        return false;
    }

    $fingerprint = $_SESSION['_auth_fingerprint'] ?? '';
    $accessExpiresAt = (int) ($_SESSION['_auth_expires_at'] ?? 0);

    if (!is_string($fingerprint) || !hash_equals($fingerprint, authSessionFingerprint())) {
        clearAuthSession();
        return false;
    }

    if ($accessExpiresAt > 0 && $accessExpiresAt <= time()) {
        clearAuthSession();
        return false;
    }

    return true;
}

function userProfile(): ?string
{
    return currentUser()['perfil_nome'] ?? null;
}

function isProfile(string|array $profiles): bool
{
    $current = userProfile();
    $requested = (array) $profiles;
    $currentComparable = comparableProfile((string) $current);
    $requestedComparable = array_map(fn (string $profile): string => comparableProfile($profile), $requested);

    if ($currentComparable !== '' && str_contains($currentComparable, 'estagi') && in_array(comparableProfile('Aluno Bolsista'), $requestedComparable, true)) {
        return true;
    }
    return in_array($currentComparable, $requestedComparable, true);
}

function comparableProfile(string $profile): string
{
    $profile = fixMojibakeText($profile);
    $profile = strtr($profile, [
        'Ã¡' => 'a', 'Ãà' => 'a', 'Ã£' => 'a', 'Ã¢' => 'a',
        'Ã©' => 'e', 'Ãª' => 'e',
        'Ã­' => 'i',
        'Ã³' => 'o', 'Ãµ' => 'o', 'Ã´' => 'o',
        'Ãº' => 'u',
        'Ã§' => 'c',
        'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a',
        'é' => 'e', 'ê' => 'e',
        'í' => 'i',
        'ó' => 'o', 'õ' => 'o', 'ô' => 'o',
        'ú' => 'u',
        'ç' => 'c',
        'Á' => 'a', 'À' => 'a', 'Ã' => 'a', 'Â' => 'a',
        'É' => 'e', 'Ê' => 'e',
        'Í' => 'i',
        'Ó' => 'o', 'Õ' => 'o', 'Ô' => 'o',
        'Ú' => 'u',
        'Ç' => 'c',
    ]);
    $profile = mb_strtolower($profile);
    return preg_replace('/[^a-z0-9 ]+/', '', $profile) ?? '';
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
    $profile = fixMojibakeText($profile);
    $normalizado = mb_strtolower($profile);
    if (comparableProfile($profile) === comparableProfile('Tecnico')) {
        return '/portaria/salas';
    }
    if (str_contains($normalizado, 'secret')) {
        return '/secretario';
    }
    if (str_contains($normalizado, 'coordenador')) {
        return '/coordenador';
    }
    if (str_contains($normalizado, 'servi') && str_contains($normalizado, 'gerais')) {
        return '/servicos-gerais';
    }
    if (str_contains($normalizado, 'estagi')) {
        return '/bolsista';
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
        'Motorista' => '/motorista',
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
            'criado_em' => appTimestamp(),
        ]);
    } catch (Throwable) {
        // A auditoria nunca deve impedir a operação principal do usuário.
    }
}

function systemLog(string $nivel, string $origem, string $mensagem, array $context = []): void
{
    try {
        (new LogSistema())->registrar($nivel, $origem, $mensagem, $context + [
            'ip_origem' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    } catch (Throwable) {
        // Falha de log tecnico nao deve derrubar a operacao principal.
    }
}

function logCriticalThrowable(Throwable $exception, string $origem = 'HTTP'): void
{
    systemLog('critical', $origem, $exception->getMessage(), [
        'arquivo' => $exception->getFile(),
        'linha' => $exception->getLine(),
        'rota' => $_SERVER['REQUEST_URI'] ?? null,
        'metodo' => $_SERVER['REQUEST_METHOD'] ?? null,
        'trace' => substr($exception->getTraceAsString(), 0, 4000),
    ]);
}
