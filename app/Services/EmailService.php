<?php
declare(strict_types=1);

/**
 * GUIA DE MANUTENCAO: Este arquivo monta e envia mensagens SMTP sem expor credenciais, códigos ou cabeçalhos injetáveis.
 *
 * Ponto de atencao: Preserve transações, validações e efeitos colaterais na ordem atual. Reordenar por estética é uma forma criativa de fabricar inconsistência.
 */

namespace App\Services;

use RuntimeException;

final class EmailService
{
    /** @var resource|null */
    private $socket = null;

    public function enviarNovoAcesso(array $usuario, string $senhaInicial): void
    {
        $nome = trim((string) ($usuario['nome'] ?? '')) ?: 'Usuário';
        $destinatario = trim((string) ($usuario['email'] ?? ''));
        $perfil = trim((string) ($usuario['perfil_nome'] ?? '')) ?: 'Usuário';
        $situacao = trim((string) ($usuario['situacao'] ?? 'ativo'));
        if (!filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Destinatário de e-mail inválido.');
        }
        if ($senhaInicial === '') {
            throw new RuntimeException('Senha inicial não informada.');
        }

        $nomeSeguro = htmlspecialchars($nome, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $emailSeguro = htmlspecialchars($destinatario, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $senhaSegura = htmlspecialchars($senhaInicial, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $perfilSeguro = htmlspecialchars($perfil, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $situacaoTexto = $situacao === 'ativo'
            ? 'Seu acesso já está ativo.'
            : 'Seu cadastro foi criado e está aguardando ativação.';

        $html = '<!doctype html><html lang="pt-BR"><body style="margin:0;background:#f3f7f5;font-family:Arial,sans-serif;color:#18352c">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0"><tr><td style="padding:32px 16px">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;margin:auto;background:#fff;border:1px solid #d7e3dd;border-radius:10px">'
            . '<tr><td style="padding:32px"><div style="margin-bottom:18px;color:#176b4d;font-size:13px;font-weight:bold;letter-spacing:.04em">CTIC/CESIT · SGRP</div>'
            . '<h1 style="margin:0 0 18px;color:#14553f;font-size:26px">Cadastro aprovado</h1>'
            . '<p>Olá, <strong>' . $nomeSeguro . '</strong>.</p>'
            . '<p>Você está recebendo esta mensagem porque sua solicitação de cadastro no Sistema de Gestão de Recursos Pedagógicos foi aprovada pelo CTIC/CESIT. ' . $situacaoTexto . '</p>'
            . '<p>Estes são os dados cadastrados para o seu primeiro acesso:</p>'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:24px 0;background:#edf6f2;border-radius:8px">'
            . '<tr><td style="padding:18px"><div style="margin-bottom:10px"><strong>Perfil:</strong> ' . $perfilSeguro . '</div>'
            . '<div style="margin-bottom:10px"><strong>E-mail:</strong> ' . $emailSeguro . '</div>'
            . '<div><strong>Senha:</strong> <span style="font-family:monospace;font-size:16px">' . $senhaSegura . '</span></div></td></tr></table>'
            . '<p>Para entrar, abra o endereço do SGRP que você utiliza normalmente na rede interna do CTIC/CESIT.</p>'
            . '<p>Após entrar, você pode alterar a senha em <strong>Meu Perfil</strong>. Não encaminhe estas credenciais para outras pessoas.</p>'
            . '<p>Em caso de dúvida, procure pessoalmente a equipe do CTIC/CESIT.</p>'
            . '<p style="margin-top:28px;color:#60756d;font-size:13px">Esta é uma mensagem automática relacionada a uma solicitação de cadastro. Não é uma mensagem publicitária.</p>'
            . '</td></tr></table></td></tr></table></body></html>';

        $texto = "Olá, {$nome}.\n\n"
            . "Você está recebendo esta mensagem porque sua solicitação de cadastro no SGRP foi aprovada pelo CTIC/CESIT. {$situacaoTexto}\n\n"
            . "Perfil: {$perfil}\nE-mail: {$destinatario}\nSenha: {$senhaInicial}\n\n"
            . "Para entrar, abra o SGRP normalmente na rede interna do CTIC/CESIT.\n\n"
            . "Após entrar, você pode alterar a senha em Meu Perfil. Não encaminhe estas credenciais para outras pessoas.\n"
            . "Em caso de dúvida, procure pessoalmente a equipe do CTIC/CESIT.";

        $this->enviar($destinatario, 'CTIC/CESIT: cadastro aprovado no SGRP', $texto, $html);
    }

    public function enviarRecuperacaoSenha(array $usuario, string $codigo): void
    {
        $nome = trim((string) ($usuario['nome'] ?? '')) ?: 'Usuário';
        $destinatario = trim((string) ($usuario['email'] ?? ''));
        if (!filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Destinatário de e-mail inválido.');
        }

        $nomeSeguro = htmlspecialchars($nome, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $codigoSeguro = htmlspecialchars($codigo, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $aplicacao = htmlspecialchars((string) config('app_name', 'SGRP'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $html = '<!doctype html><html lang="pt-BR"><body style="margin:0;background:#f3f7f5;font-family:Arial,sans-serif;color:#18352c">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0"><tr><td style="padding:32px 16px">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;margin:auto;background:#fff;border:1px solid #d7e3dd;border-radius:10px">'
            . '<tr><td style="padding:32px"><div style="margin-bottom:18px;color:#176b4d;font-size:13px;font-weight:bold;letter-spacing:.04em">CTIC/CESIT · SGRP</div>'
            . '<h1 style="margin:0 0 18px;color:#14553f;font-size:26px">Código para redefinir sua senha</h1>'
            . '<p>Olá, <strong>' . $nomeSeguro . '</strong>.</p>'
            . '<p>Recebemos uma solicitação para redefinir sua senha no ' . $aplicacao . '. Digite o código abaixo na tela que já está aberta no sistema:</p>'
            . '<div style="margin:26px 0;padding:18px;background:#edf6f2;border:1px solid #c9ddd3;border-radius:8px;text-align:center;font-family:monospace;font-size:28px;font-weight:bold;letter-spacing:.12em;color:#14553f">' . $codigoSeguro . '</div>'
            . '<p>O código é de uso único, expira em 30 minutos e aceita no máximo cinco tentativas.</p>'
            . '<p>Se você não fez esta solicitação, ignore a mensagem. Em caso de dúvida, procure pessoalmente a equipe do CTIC/CESIT.</p>'
            . '<p style="margin-top:28px;color:#60756d;font-size:13px">Mensagem automática de segurança. Não é uma mensagem publicitária.</p>'
            . '</td></tr></table></td></tr></table></body></html>';

        $texto = "Olá, {$nome}.\n\n"
            . "Recebemos uma solicitação para redefinir sua senha no " . config('app_name', 'SGRP') . ".\n"
            . "Digite este código na tela que já está aberta no sistema: {$codigo}\n\n"
            . "O código é de uso único, expira em 30 minutos e aceita no máximo cinco tentativas.\n"
            . "Se você não fez esta solicitação, ignore a mensagem e procure o CTIC/CESIT em caso de dúvida.";

        $this->enviar($destinatario, 'CTIC/CESIT: código de redefinição do SGRP', $texto, $html);
    }

    private function enviar(string $destinatario, string $assunto, string $texto, string $html): void
    {
        // Nunca inclua $senha, codigos ou a resposta completa de AUTH em logs.
        // Diagnostico SMTP bom informa etapa e codigo; diagnostico "completo"
        // costuma virar vazamento de credencial com excelente rastreabilidade.
        $host = trim((string) config('mail_host', ''));
        $porta = (int) config('mail_port', 587);
        $criptografia = strtolower(trim((string) config('mail_encryption', 'tls')));
        $usuario = trim((string) config('mail_username', ''));
        $senha = (string) config('mail_password', '');
        $remetente = trim((string) config('mail_from_address', ''));
        $nomeRemetente = $this->semQuebras((string) config('mail_from_name', config('app_name', 'SGRP')));
        $timeout = (int) config('mail_timeout', 15);

        if (
            $host === ''
            || !preg_match('/\A[a-zA-Z0-9.-]+\z/', $host)
            || $porta < 1
            || $porta > 65535
            || !in_array($criptografia, ['tls', 'ssl', 'none'], true)
        ) {
            throw new RuntimeException('Configuração SMTP incompleta.');
        }
        if (!filter_var($remetente, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('MAIL_FROM_ADDRESS inválido.');
        }

        $alvo = ($criptografia === 'ssl' ? 'ssl://' : '') . $host . ':' . $porta;
        $contexto = stream_context_create([
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'peer_name' => $host,
            ],
        ]);
        $erroNumero = 0;
        $erroMensagem = '';
        $this->socket = @stream_socket_client($alvo, $erroNumero, $erroMensagem, $timeout, STREAM_CLIENT_CONNECT, $contexto);
        if (!is_resource($this->socket)) {
            throw new RuntimeException('Não foi possível conectar ao servidor SMTP.');
        }
        stream_set_timeout($this->socket, $timeout);

        try {
            $this->esperar([220]);
            $hostname = gethostname() ?: 'localhost';
            $this->comando('EHLO ' . preg_replace('/[^a-zA-Z0-9.-]/', '', $hostname), [250]);

            if ($criptografia === 'tls') {
                // STARTTLS muda o socket existente para um canal cifrado. O
                // segundo EHLO nao e repeticao decorativa: capacidades podem
                // mudar depois do handshake.
                $this->comando('STARTTLS', [220]);
                $metodoTls = defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')
                    ? STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT
                    : STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
                if (@stream_socket_enable_crypto($this->socket, true, $metodoTls) !== true) {
                    throw new RuntimeException('Não foi possível proteger a conexão SMTP com TLS.');
                }
                $this->comando('EHLO ' . preg_replace('/[^a-zA-Z0-9.-]/', '', $hostname), [250]);
            }

            if ($usuario !== '') {
                $this->comando('AUTH LOGIN', [334]);
                $this->comando(base64_encode($usuario), [334]);
                $this->comando(base64_encode($senha), [235]);
            }

            $this->comando('MAIL FROM:<' . $remetente . '>', [250]);
            $this->comando('RCPT TO:<' . $destinatario . '>', [250, 251]);
            $this->comando('DATA', [354]);

            $fronteira = 'sgrp_' . bin2hex(random_bytes(12));
            $dominioRemetente = substr(strrchr($remetente, '@') ?: '', 1) ?: 'localhost';
            $headers = [
                'Date: ' . date(DATE_RFC2822),
                'From: ' . $this->codificarCabecalho($nomeRemetente) . ' <' . $remetente . '>',
                'Reply-To: <' . $remetente . '>',
                'To: <' . $destinatario . '>',
                'Subject: ' . $this->codificarCabecalho($this->semQuebras($assunto)),
                'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . $dominioRemetente . '>',
                'MIME-Version: 1.0',
                'Auto-Submitted: auto-generated',
                'X-Auto-Response-Suppress: All',
                'Content-Type: multipart/alternative; boundary="' . $fronteira . '"',
            ];
            $mensagem = implode("\r\n", $headers) . "\r\n\r\n"
                . '--' . $fronteira . "\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
                . rtrim(chunk_split(base64_encode($texto), 76, "\r\n")) . "\r\n"
                . '--' . $fronteira . "\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
                . rtrim(chunk_split(base64_encode($html), 76, "\r\n")) . "\r\n"
                . '--' . $fronteira . "--\r\n";
            // SMTP encerra DATA com uma linha contendo apenas ponto. O dot
            // stuffing impede que uma linha do corpo encerre o e-mail antes da
            // hora. Protocolos antigos: sempre reservando uma pegadinha vintage.
            $mensagem = preg_replace('/(?m)^\./', '..', $mensagem) ?? $mensagem;
            fwrite($this->socket, $mensagem . ".\r\n");
            $this->esperar([250]);
            $this->comando('QUIT', [221]);
        } finally {
            if (is_resource($this->socket)) {
                fclose($this->socket);
            }
            $this->socket = null;
        }
    }

    private function comando(string $comando, array $codigosEsperados): string
    {
        if (!is_resource($this->socket) || fwrite($this->socket, $comando . "\r\n") === false) {
            throw new RuntimeException('Falha de comunicação com o servidor SMTP.');
        }
        return $this->esperar($codigosEsperados);
    }

    private function esperar(array $codigosEsperados): string
    {
        if (!is_resource($this->socket)) {
            throw new RuntimeException('Conexão SMTP indisponível.');
        }

        $resposta = '';
        do {
            $linha = fgets($this->socket, 2048);
            if ($linha === false) {
                $meta = stream_get_meta_data($this->socket);
                throw new RuntimeException(!empty($meta['timed_out']) ? 'Tempo limite do servidor SMTP.' : 'Resposta SMTP incompleta.');
            }
            $resposta .= $linha;
        } while (isset($linha[3]) && $linha[3] === '-');

        $codigo = (int) substr($resposta, 0, 3);
        if (!in_array($codigo, $codigosEsperados, true)) {
            throw new RuntimeException('Servidor SMTP recusou a operação (código ' . $codigo . ').');
        }
        return $resposta;
    }

    private function codificarCabecalho(string $valor): string
    {
        return mb_encode_mimeheader($valor, 'UTF-8', 'B', "\r\n");
    }

    private function semQuebras(string $valor): string
    {
        return trim(str_replace(["\r", "\n"], '', $valor));
    }

}
