<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\AdvertenciaChave;
use App\Models\BloqueioChave;
use App\Models\ConfiguracaoSistema;
use App\Models\LogAuditoria;

// Painel técnico do desenvolvedor, com acesso amplo a manutenção e auditoria.
class DesenvolvedorController extends Controller
{
    public function index(): void
    {
        requireProfile('Desenvolvedor');
        $this->view('desenvolvedor/index', ['title' => 'Desenvolvedor']);
    }

    public function logs(): void
    {
        requireProfile('Desenvolvedor');
        $this->view('desenvolvedor/logs', ['title' => 'Logs', 'logs' => (new LogAuditoria())->withUser()]);
    }

    public function limparLogs(): void
    {
        requireProfile('Desenvolvedor');
        verifyCsrf();
        (new LogAuditoria())->clear();
        flash('success', 'Logs limpos.');
        redirect('/desenvolvedor/logs');
    }

    public function advertencias(): void
    {
        requireProfile('Desenvolvedor');
        $this->view('desenvolvedor/advertencias', [
            'title' => 'Advertências',
            'advertencias' => (new AdvertenciaChave())->withDetails(),
            'bloqueios' => (new BloqueioChave())->withDetails(),
            'config' => (new ConfiguracaoSistema())->getValue('dias_bloqueio_advertencia', '7'),
        ]);
    }

    public function salvarAdvertencias(): void
    {
        requireProfile('Desenvolvedor');
        verifyCsrf();
        (new ConfiguracaoSistema())->setValue('dias_bloqueio_advertencia', (string) max(1, (int) ($_POST['dias'] ?? 7)));
        flash('success', 'Configuração salva.');
        redirect('/desenvolvedor/advertencias');
    }

    public function atualizarBloqueio(): void
    {
        requireProfile('Desenvolvedor');
        verifyCsrf();
        $acao = $_POST['acao'] ?? 'atualizar';
        (new BloqueioChave())->update((int) $_POST['id'], $acao === 'zerar'
            ? ['fim_em' => date('Y-m-d H:i:s'), 'situacao' => 'encerrado']
            : ['fim_em' => $_POST['fim_em'], 'situacao' => $_POST['situacao'] ?? 'ativo']
        );
        flash('success', 'Bloqueio atualizado.');
        redirect('/desenvolvedor/advertencias');
    }

    public function excluirBloqueio(): void
    {
        requireProfile('Desenvolvedor');
        verifyCsrf();
        (new BloqueioChave())->delete((int) $_POST['id']);
        flash('success', 'Bloqueio apagado da lista.');
        redirect('/desenvolvedor/advertencias');
    }

    public function limparAdvertencias(): void
    {
        requireProfile('Desenvolvedor');
        verifyCsrf();
        $pdo = \App\Core\Database::pdo();
        $pdo->exec('UPDATE bloqueios_chaves SET advertencia_id = NULL');
        $pdo->exec('DELETE FROM advertencias_chaves');
        flash('success', 'Histórico de advertências limpo.');
        redirect('/desenvolvedor/advertencias');
    }
}
