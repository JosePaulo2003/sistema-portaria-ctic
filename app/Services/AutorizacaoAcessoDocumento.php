<?php
declare(strict_types=1);

/**
 * GUIA DE MANUTENCAO: Este arquivo gera documentos DOCX a partir do modelo de autorização; XML de Word pune improvisos com entusiasmo.
 *
 * Ponto de atencao: Preserve transações, validações e efeitos colaterais na ordem atual. Reordenar por estética é uma forma criativa de fabricar inconsistência.
 */

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMXPath;
use PharData;
use RuntimeException;
use ZipArchive;

final class AutorizacaoAcessoDocumento
{
    private const W_NS = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    /**
     * @return array{path: string, filename: string}
     */
    public function gerar(array $dados): array
    {
        if (!class_exists(DOMDocument::class)) {
            throw new RuntimeException('O servidor precisa da extensao PHP dom para gerar o documento Word.');
        }
        if (!class_exists(ZipArchive::class) && !class_exists(PharData::class)) {
            throw new RuntimeException('O servidor precisa das extensoes PHP zip ou phar para gerar o documento Word.');
        }

        $template = dirname(__DIR__, 2) . '/resources/templates/autorizacao-acesso-bolsistas-editavel.docx';
        if (!is_file($template)) {
            throw new RuntimeException('O modelo editavel de autorizacao nao foi encontrado no servidor.');
        }

        $arquivoTemporario = tempnam(sys_get_temp_dir(), 'sgrp_autorizacao_');
        if ($arquivoTemporario === false) {
            throw new RuntimeException('Nao foi possivel criar o arquivo temporario da autorizacao.');
        }
        @unlink($arquivoTemporario);
        $arquivoTemporario .= '.docx';

        if (!copy($template, $arquivoTemporario)) {
            throw new RuntimeException('Nao foi possivel preparar o modelo de autorizacao.');
        }

        $numeroAutorizacao = trim((string) ($dados['numero_autorizacao'] ?? ''))
            ?: 'SGRP-' . date('Ymd-His');
        $professorNome = trim((string) ($dados['professor_nome'] ?? ''));
        $professorEmail = trim((string) ($dados['professor_email'] ?? ''));
        $professorDetalhado = $professorNome;
        if ($professorEmail !== '') {
            $professorDetalhado .= ($professorDetalhado !== '' ? ' - ' : '') . $professorEmail;
        }

        $salaNome = trim((string) ($dados['sala_nome'] ?? ''));
        $salaCodigo = trim((string) ($dados['sala_codigo'] ?? ''));
        $localDetalhado = $salaNome;
        if ($salaCodigo !== '') {
            $localDetalhado .= ($localDetalhado !== '' ? ' - ' : '') . $salaCodigo;
        }

        $inicioEm = trim((string) ($dados['inicio_em'] ?? ''));
        $expiraEm = trim((string) ($dados['expira_em'] ?? '')) ?: 'Sem expiração';
        $diasAcesso = trim((string) ($dados['dias_acesso'] ?? '')) ?: 'Todos os dias';
        $periodoDias = $inicioEm !== ''
            ? $inicioEm . ' a ' . $expiraEm . ' | ' . $diasAcesso
            : $diasAcesso;

        $dataAprovacao = trim((string) ($dados['data_autorizacao'] ?? '')) ?: date('d/m/Y');

        $valores = [
            'NUMERO_AUTORIZACAO' => $numeroAutorizacao,
            'PROFESSOR_SOLICITANTE' => $professorDetalhado,
            'LOCAL_AUTORIZADO' => $localDetalhado,
            'FINALIDADE_ACESSO' => trim((string) ($dados['finalidade'] ?? '')),
            'DIAS_ACESSO' => $periodoDias,
            'HORARIO_ACESSO' => trim((string) ($dados['horario_acesso'] ?? '')),
            'EXPIRA_EM' => $expiraEm,
            'DATA_SOLICITACAO' => trim((string) ($dados['data_solicitacao'] ?? date('d/m/Y'))),
            'DATA_AUTORIZACAO' => 'APROVADA em ' . $dataAprovacao,
        ];

        $bolsistas = array_values((array) ($dados['bolsistas'] ?? []));
        for ($indice = 1; $indice <= 11; $indice++) {
            $bolsista = $bolsistas[$indice - 1] ?? [];
            $valores[sprintf('AUTORIZADO_%02d', $indice)] = trim((string) ($bolsista['nome'] ?? ''));
            $matricula = trim((string) ($bolsista['matricula'] ?? ''));
            $valores[sprintf('MATRICULA_%02d', $indice)] = $bolsista
                ? ($matricula !== '' ? $matricula : 'Não informada')
                : '';
        }

        try {
            $this->preencherControles($arquivoTemporario, $valores);
        } catch (\Throwable $exception) {
            @unlink($arquivoTemporario);
            throw new RuntimeException('Nao foi possivel preencher o documento de autorizacao.', 0, $exception);
        }

        $identificador = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) ($dados['numero_autorizacao'] ?? ''));
        $identificador = trim((string) $identificador, '-') ?: date('Ymd-His');

        return [
            'path' => $arquivoTemporario,
            'filename' => 'autorizacao-acesso-bolsistas-' . $identificador . '.docx',
        ];
    }

    public function enviarDownload(array $documento): never
    {
        $arquivo = (string) ($documento['path'] ?? '');
        $nome = (string) ($documento['filename'] ?? 'autorizacao-acesso-bolsistas.docx');
        if (!is_file($arquivo)) {
            throw new RuntimeException('O documento de autorizacao nao esta disponivel para download.');
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . str_replace('"', '', $nome) . '"');
        header('Content-Length: ' . filesize($arquivo));
        header('Cache-Control: private, no-store, max-age=0');
        readfile($arquivo);
        @unlink($arquivo);
        exit;
    }

    private function preencherControles(string $arquivo, array $valores): void
    {
        if (class_exists(ZipArchive::class)) {
            $this->preencherComZipArchive($arquivo, $valores);
            return;
        }

        $this->preencherComPhar($arquivo, $valores);
    }

    private function preencherComZipArchive(string $arquivo, array $valores): void
    {
        $zip = new ZipArchive();
        if ($zip->open($arquivo) !== true) {
            throw new RuntimeException('Falha ao abrir o modelo Word.');
        }

        try {
            $xml = $zip->getFromName('word/document.xml');
            if (!is_string($xml) || $xml === '') {
                throw new RuntimeException('O conteudo principal do modelo Word nao foi encontrado.');
            }

            $xmlAtualizado = $this->preencherXml($xml, $valores);
            if (!$zip->addFromString('word/document.xml', $xmlAtualizado)) {
                throw new RuntimeException('Falha ao gravar o documento Word preenchido.');
            }
        } finally {
            $zip->close();
        }
    }

    private function preencherComPhar(string $arquivo, array $valores): void
    {
        $arquivoZip = $arquivo . '.zip';
        if (!copy($arquivo, $arquivoZip)) {
            throw new RuntimeException('Falha ao preparar o pacote Word para preenchimento.');
        }

        try {
            $phar = new PharData($arquivoZip);
            if (!isset($phar['word/document.xml'])) {
                throw new RuntimeException('O conteudo principal do modelo Word nao foi encontrado.');
            }
            $xml = $phar['word/document.xml']->getContent();
            $phar['word/document.xml'] = $this->preencherXml($xml, $valores);
            unset($phar);

            if (!copy($arquivoZip, $arquivo)) {
                throw new RuntimeException('Falha ao finalizar o documento Word preenchido.');
            }
        } finally {
            @unlink($arquivoZip);
        }
    }

    private function preencherXml(string $xml, array $valores): string
    {
        $documento = new DOMDocument('1.0', 'UTF-8');
        $documento->preserveWhiteSpace = true;
        $documento->formatOutput = false;
        if (!$documento->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT)) {
            throw new RuntimeException('O conteudo do modelo Word e invalido.');
        }

        $xpath = new DOMXPath($documento);
        $xpath->registerNamespace('w', self::W_NS);

        foreach ($xpath->query('//w:sdt') as $controle) {
            if (!$controle instanceof DOMElement) {
                continue;
            }

            $tag = (string) $xpath->evaluate('string(./w:sdtPr/w:tag/@w:val)', $controle);
            if ($tag === '' || !array_key_exists($tag, $valores)) {
                continue;
            }

            $conteudo = $xpath->query('./w:sdtContent', $controle)?->item(0);
            if (!$conteudo instanceof DOMElement) {
                continue;
            }

            $propriedadesOriginais = $xpath->query('.//w:rPr', $conteudo)?->item(0);
            $propriedadesClonadas = $propriedadesOriginais instanceof DOMElement
                ? $propriedadesOriginais->cloneNode(true)
                : null;
            while ($conteudo->firstChild) {
                $conteudo->removeChild($conteudo->firstChild);
            }

            $run = $documento->createElementNS(self::W_NS, 'w:r');
            if ($propriedadesClonadas instanceof DOMElement) {
                $run->appendChild($propriedadesClonadas);
            }
            $texto = $documento->createElementNS(self::W_NS, 'w:t');
            $texto->setAttributeNS('http://www.w3.org/XML/1998/namespace', 'xml:space', 'preserve');
            $texto->appendChild($documento->createTextNode((string) $valores[$tag]));
            $run->appendChild($texto);
            $conteudo->appendChild($run);
        }

        $this->removerLinhasDeAutorizadosNaoUtilizadas($xpath, $valores);

        $xmlAtualizado = $documento->saveXML();
        if (!is_string($xmlAtualizado) || $xmlAtualizado === '') {
            throw new RuntimeException('Falha ao salvar o conteudo preenchido do modelo Word.');
        }

        return $xmlAtualizado;
    }

    private function removerLinhasDeAutorizadosNaoUtilizadas(DOMXPath $xpath, array $valores): void
    {
        for ($indice = 1; $indice <= 11; $indice++) {
            $tag = sprintf('AUTORIZADO_%02d', $indice);
            if (trim((string) ($valores[$tag] ?? '')) !== '') {
                continue;
            }

            $controle = $xpath->query(sprintf(
                '//w:sdt[w:sdtPr/w:tag/@w:val="%s"]',
                $tag
            ))?->item(0);
            if (!$controle instanceof DOMElement) {
                continue;
            }

            $linha = $xpath->query('ancestor::w:tr[1]', $controle)?->item(0);
            if ($linha instanceof DOMElement && $linha->parentNode) {
                $linha->parentNode->removeChild($linha);
            }
        }
    }
}
