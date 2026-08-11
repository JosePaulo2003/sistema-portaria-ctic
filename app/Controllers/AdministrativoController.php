<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\ItemPortaria;
use App\Models\PermissaoSala;
use App\Models\Reserva;
use App\Models\Sala;
use App\Models\User;

// Ãrea administrativa de consulta e acompanhamento geral.
class AdministrativoController extends Controller
{
    public function index(): void { $this->salasHome('Administrativo'); }
    public function reservasSalas(): void { requireProfile('Administrativo'); $this->view('administrativo/reservas-salas', ['title' => 'Reservas', 'reservas' => (new Reserva())->withDetails(), 'salas' => (new Sala())->all('nome')]); }
    public function salvarReservaSala(): void
    {
        requireProfile('Administrativo');
        verifyCsrf();
        $this->validarReservaAdministrativa();
        (new Reserva())->create([
            'usuario_id' => currentUser()['id'],
            'sala_id' => (int) $_POST['sala_id'],
            'titulo' => trim((string) $_POST['titulo']),
            'finalidade' => $_POST['finalidade'] ?? null,
            'tipo_reserva' => 'sala',
            'inicio_em' => $_POST['inicio_em'],
            'fim_em' => $_POST['fim_em'],
            'situacao' => 'pendente',
        ]);
        audit('Reservas', 'criacao', 'Reserva administrativa solicitada.');
        flash('success', 'Reserva solicitada. Aguarde a aprovaÃ§Ã£o da Portaria.');
        redirect('/administrativo/reservas-salas');
    }
    public function retiradas(): void
    {
        requireProfile('Administrativo');
        $this->view('administrativo/retiradas', [
            'title' => 'Retiradas',
            'salas' => (new Sala())->chavesParaRetirada(currentUser()),
            'itens' => (new ItemPortaria())->disponiveisParaRetirada(),
        ]);
    }
    public function disponibilidadeSalas(): void { requireProfile('Administrativo'); $this->view('administrativo/disponibilidade-salas', ['title' => 'Disponibilidade', 'salas' => (new Sala())->listDisponibilidade($_GET)]); }

    public function salas(): void
    {
        requireProfile('Administrativo');
        $this->view('secretario/salas', [
            'title' => 'Salas',
            'salas' => (new Sala())->all('nome'),
            'actionPrefix' => '/administrativo/salas',
        ]);
    }

    public function salvarSala(): void
    {
        requireProfile('Administrativo');
        verifyCsrf();
        (new Sala())->create($this->salaData());
        flash('success', 'Sala cadastrada.');
        redirect('/administrativo/salas');
    }

    public function atualizarSala(): void
    {
        requireProfile('Administrativo');
        verifyCsrf();
        (new Sala())->update((int) $_POST['id'], $this->salaData());
        flash('success', 'Sala atualizada.');
        redirect('/administrativo/salas');
    }

    public function excluirSala(): void
    {
        requireProfile('Administrativo');
        verifyCsrf();
        try {
            (new Sala())->delete((int) $_POST['id']);
        } catch (\Throwable) {
            (new Sala())->update((int) $_POST['id'], ['situacao' => 'bloqueada']);
        }
        flash('success', 'Sala removida ou bloqueada.');
        redirect('/administrativo/salas');
    }

    public function chavesAutorizadas(): void
    {
        requireProfile('Administrativo');
        $this->view('secretario/chaves-autorizadas', [
            'title' => 'Chaves Autorizadas',
            'permissoes' => (new PermissaoSala())->withDetails(),
            'usuarios' => (new User())->allWithProfile(),
            'salas' => (new Sala())->all('nome'),
            'actionPrefix' => '/administrativo/chaves-autorizadas',
        ]);
    }

    public function salvarChaveAutorizada(): void
    {
        requireProfile('Administrativo');
        verifyCsrf();
        $this->validarPermissao();
        (new PermissaoSala())->create($this->permissaoData());
        flash('success', 'Permissao salva.');
        redirect('/administrativo/chaves-autorizadas');
    }

    public function atualizarChaveAutorizada(): void
    {
        requireProfile('Administrativo');
        verifyCsrf();
        $this->validarPermissao();
        (new PermissaoSala())->update((int) $_POST['id'], $this->permissaoData());
        flash('success', 'Permissao atualizada.');
        redirect('/administrativo/chaves-autorizadas');
    }

    public function excluirChaveAutorizada(): void
    {
        requireProfile('Administrativo');
        verifyCsrf();
        (new PermissaoSala())->delete((int) $_POST['id']);
        flash('success', 'Permissao excluida.');
        redirect('/administrativo/chaves-autorizadas');
    }

    public function itens(): void
    {
        requireProfile('Administrativo');
        $this->view('secretario/itens', [
            'title' => 'Itens',
            'itens' => (new ItemPortaria())->all('nome'),
            'actionPrefix' => '/administrativo/itens',
        ]);
    }

    public function salvarItem(): void
    {
        requireProfile('Administrativo');
        verifyCsrf();
        (new ItemPortaria())->create($this->itemData());
        flash('success', 'Item cadastrado.');
        redirect('/administrativo/itens');
    }

    public function atualizarItem(): void
    {
        requireProfile('Administrativo');
        verifyCsrf();
        (new ItemPortaria())->update((int) $_POST['id'], $this->itemData());
        flash('success', 'Item atualizado.');
        redirect('/administrativo/itens');
    }

    public function excluirItem(): void
    {
        requireProfile('Administrativo');
        verifyCsrf();
        try {
            (new ItemPortaria())->delete((int) $_POST['id']);
        } catch (\Throwable) {
            (new ItemPortaria())->update((int) $_POST['id'], ['situacao' => 'indisponivel']);
        }
        flash('success', 'Item removido ou indisponibilizado.');
        redirect('/administrativo/itens');
    }

    private function validarReservaAdministrativa(): void
    {
        $inicio = $this->criarDataHora((string) ($_POST['inicio_em'] ?? ''));
        $fim = $this->criarDataHora((string) ($_POST['fim_em'] ?? ''));
        $salaId = (int) ($_POST['sala_id'] ?? 0);

        if (!$inicio || !$fim || $salaId <= 0) {
            flash('error', 'Informe sala, data e horÃ¡rio vÃ¡lidos para solicitar a reserva.');
            redirect('/administrativo/reservas-salas');
        }
        if ($inicio < new \DateTimeImmutable()) {
            flash('error', 'NÃ£o Ã© possÃ­vel reservar sala com data ou horÃ¡rio anterior ao momento atual.');
            redirect('/administrativo/reservas-salas');
        }
        if ($fim <= $inicio) {
            flash('error', 'O fim da reserva precisa ser posterior ao inÃ­cio.');
            redirect('/administrativo/reservas-salas');
        }
        if (!$this->salaDisponivelParaReserva($salaId, $inicio, $fim)) {
            flash('error', 'Esta sala nÃ£o estÃ¡ disponÃ­vel no perÃ­odo informado.');
            redirect('/administrativo/reservas-salas');
        }
    }

    private function salaDisponivelParaReserva(int $salaId, \DateTimeImmutable $inicio, \DateTimeImmutable $fim): bool
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT situacao FROM salas WHERE id = ? LIMIT 1');
        $stmt->execute([$salaId]);
        $situacao = $stmt->fetchColumn();
        if (!in_array($situacao, ['disponivel', 'fechada'], true)) {
            return false;
        }

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM movimentacoes WHERE sala_id = ? AND situacao = "aberta"');
        $stmt->execute([$salaId]);
        if ((int) $stmt->fetchColumn() > 0) {
            return false;
        }

        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM reservas
             WHERE sala_id = ? AND situacao IN ("pendente", "confirmada")
               AND inicio_em < ? AND fim_em > ?'
        );
        $stmt->execute([
            $salaId,
            $fim->format('Y-m-d H:i:s'),
            $inicio->format('Y-m-d H:i:s'),
        ]);
        return (int) $stmt->fetchColumn() === 0;
    }

    private function criarDataHora(string $valor): ?\DateTimeImmutable
    {
        $data = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $valor);
        return $data instanceof \DateTimeImmutable ? $data : null;
    }

    private function salaData(): array
    {
        return [
            'nome' => trim((string) $_POST['nome']),
            'codigo' => $_POST['codigo'] ?: null,
            'bloco' => $_POST['bloco'] ?: null,
            'capacidade' => $_POST['capacidade'] !== '' ? (int) $_POST['capacidade'] : null,
            'tipo_ambiente' => $_POST['tipo_ambiente'],
            'situacao' => $_POST['situacao'] ?? 'disponivel',
            'descricao' => $_POST['descricao'] ?? null,
        ];
    }

    private function itemData(): array
    {
        return [
            'nome' => trim((string) $_POST['nome']),
            'codigo' => $_POST['codigo'] ?: null,
            'categoria' => $_POST['categoria'] ?: null,
            'quantidade' => max(0, (int) ($_POST['quantidade'] ?? 1)),
            'situacao' => $_POST['situacao'] ?? 'disponivel',
            'descricao' => $_POST['descricao'] ?? null,
        ];
    }

    private function permissaoData(): array
    {
        return [
            'usuario_id' => (int) $_POST['usuario_id'],
            'sala_id' => !empty($_POST['acesso_total']) ? null : (int) $_POST['sala_id'],
            'acesso_total' => !empty($_POST['acesso_total']) ? 1 : 0,
            'autorizado_por' => currentUser()['id'],
            'inicio_autorizacao' => $_POST['inicio_autorizacao'] ?: null,
            'expira_em' => !empty($_POST['nunca_expirar']) ? null : ($_POST['expira_em'] ?: null),
            'dias_semana' => !empty($_POST['dias_semana']) ? implode(', ', (array) $_POST['dias_semana']) : null,
            'observacao' => $_POST['observacao'] ?? null,
            'situacao' => $_POST['situacao'] ?? 'ativa',
        ];
    }

    private function validarPermissao(): void
    {
        $inicio = $this->criarDataHora((string) ($_POST['inicio_autorizacao'] ?? ''));
        $expira = !empty($_POST['nunca_expirar']) ? null : $this->criarDataHora((string) ($_POST['expira_em'] ?? ''));
        $agora = new \DateTimeImmutable();

        if (!empty($_POST['inicio_autorizacao']) && (!$inicio || $inicio < $agora)) {
            flash('error', 'O inicio da autorizacao nao pode estar no passado.');
            redirect('/administrativo/chaves-autorizadas');
        }
        if (empty($_POST['nunca_expirar']) && !empty($_POST['expira_em']) && (!$expira || $expira < $agora)) {
            flash('error', 'A expiracao da autorizacao nao pode estar no passado.');
            redirect('/administrativo/chaves-autorizadas');
        }
        if ($inicio && $expira && $expira < $inicio) {
            flash('error', 'A expiracao nao pode ser anterior ao inicio da autorizacao.');
            redirect('/administrativo/chaves-autorizadas');
        }
    }
}

