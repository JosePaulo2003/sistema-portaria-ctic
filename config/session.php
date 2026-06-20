<?php
declare(strict_types=1);

// Configuracao endurecida da sessao e do cookie de autenticacao.
$sessionPath = dirname(__DIR__) . '/storage/sessions';
if (is_dir($sessionPath) && is_writable($sessionPath)) {
    session_save_path($sessionPath);
}

session_name('SGRPSESSID');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.use_trans_sid', '0');
ini_set('session.cookie_httponly', '1');
ini_set('session.sid_length', '48');
ini_set('session.sid_bits_per_character', '6');
ini_set('session.gc_maxlifetime', '7200');
ini_set('session.lazy_write', '1');

$secureCookie = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $secureCookie,
    'httponly' => true,
    'samesite' => 'Lax',
]);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
