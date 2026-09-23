<?php
declare(strict_types=1);

/**
 * GUIA DE MANUTENCAO: Este arquivo configura cookies e retenção da sessão autenticada.
 *
 * Ponto de atencao: Mudanças de configuração parecem pequenas até derrubarem todas as rotas ao mesmo tempo. Valide em ambiente seguro e sem credenciais no commit.
 */

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
// A sessão autenticada é persistente e renovada pela aplicação. Dez anos aqui
// funcionam como retenção técnica; navegadores podem impor um limite menor ao
// cookie, que volta a ser renovado sempre que o usuário acessa o SGRP.
ini_set('session.gc_maxlifetime', '315360000');
ini_set('session.lazy_write', '1');

// X-Forwarded-Proto so deve ser confiado quando o Apache recebe trafego de um
// proxy controlado. Aceitar cabecalho de qualquer cliente e terceirizar a
// seguranca do cookie para quem acabou de chegar da internet.
$sessionForwardedProto = strtolower(trim(explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0] ?? ''));
$secureCookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $sessionForwardedProto === 'https';
session_set_cookie_params([
    // lifetime=0 cria cookie de sessao. A retencao longa do servidor evita
    // expirar durante o uso, mas o navegador ainda decide quando encerrar o
    // cookie. "Nunca deslogar" absoluto nao existe; existe compromisso seguro.
    'lifetime' => 0,
    'path' => '/',
    'secure' => $secureCookie,
    'httponly' => true,
    'samesite' => 'Lax',
]);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
