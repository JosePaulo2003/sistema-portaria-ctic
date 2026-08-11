<section class="section-header">
    <h1>Reservas</h1>
    <p>Cadastre reservas confirmadas e acompanhe solicitacoes pendentes dos demais perfis.</p>
</section>

<?php
$podeApagarReserva = isDeveloper() || isProfile('Agente de Portaria');
$diasSemanaReserva = [
    1 => 'Segunda',
    2 => 'Terca',
    3 => 'Quarta',
    4 => 'Quinta',
    5 => 'Sexta',
    6 => 'Sabado',
    7 => 'Domingo',
];
?>

<form method="post" action="<?= e(baseUrl('/portaria/reservas')) ?>" class="card form-grid">
    <?= csrfField() ?>
    <label>Sala
        <select name="sala_id" required>
            <option value="">Selecione</option>
            <?php foreach ($salas as $sala): ?>
                <option value="<?= e($sala['id']) ?>"><?= e($sala['nome']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Titulo
        <input name="titulo" required>
    </label>
    <label>Usuario cadastrado
        <select name="usuario_id">
            <option value="">Sem cadastro / informar nome</option>
            <?php foreach (($usuarios ?? []) as $usuario): ?>
                <option value="<?= e($usuario['id']) ?>"><?= e($usuario['nome']) ?><?= !empty($usuario['email']) ? ' - ' . e($usuario['email']) : '' ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Nome sem cadastro
        <input name="solicitante_nome_manual" placeholder="Nome do solicitante">
    </label>
    <label>Inicio
        <input type="datetime-local" name="inicio_em" required>
    </label>
    <label>Fim
        <input type="datetime-local" name="fim_em" required>
    </label>
    <label>Repetir ate
        <input type="date" name="recorrencia_fim">
    </label>
    <fieldset class="checkbox-group full">
        <legend>Dias da semana</legend>
        <?php foreach ($diasSemanaReserva as $numeroDia => $dia): ?>
            <label class="checkbox-pill">
                <input type="checkbox" name="dias_semana[]" value="<?= e($numeroDia) ?>">
                <span><?= e($dia) ?></span>
            </label>
        <?php endforeach; ?>
    </fieldset>
    <label class="full">Finalidade
        <textarea name="finalidade" placeholder="Informe a finalidade da reserva"></textarea>
    </label>
    <div class="form-actions">
        <button class="button" type="submit">Cadastrar reserva</button>
    </div>
</form>

<div class="card table-wrap">
    <table>
        <thead>
            <tr>
                <th>Titulo</th>
                <th>Sala</th>
                <th>Solicitante</th>
                <th>Inicio</th>
                <th>Fim</th>
                <th>Situacao</th>
                <th>Acoes</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reservas as $reserva): ?>
                <tr>
                    <td>
                        <strong><?= e($reserva['titulo']) ?></strong>
                        <?php if (!empty($reserva['finalidade'])): ?>
                            <br><span class="muted"><?= e($reserva['finalidade']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= e($reserva['sala_nome'] ?? '-') ?></td>
                    <td><?= e($reserva['usuario_nome']) ?></td>
                    <td><?= e(date('d/m/Y H:i', strtotime($reserva['inicio_em']))) ?></td>
                    <td><?= e(date('d/m/Y H:i', strtotime($reserva['fim_em']))) ?></td>
                    <td><span class="status-badge"><?= e($reserva['situacao']) ?></span></td>
                    <td>
                        <?php if (($reserva['situacao'] ?? '') === 'pendente'): ?>
                            <form method="post" action="<?= e(baseUrl('/portaria/reservas/atualizar')) ?>" class="inline-actions">
                                <?= csrfField() ?>
                                <input type="hidden" name="id" value="<?= e($reserva['id']) ?>">
                                <button class="button" name="acao" value="aprovar" type="submit">Aprovar</button>
                                <button class="button button--danger" name="acao" value="recusar" type="submit" data-confirm="Recusar esta reserva?">Recusar</button>
                            </form>
                        <?php elseif (!$podeApagarReserva): ?>
                            <span class="muted">Sem acao</span>
                        <?php endif; ?>
                        <?php if ($podeApagarReserva): ?>
                            <form method="post" action="<?= e(baseUrl('/portaria/reservas/excluir-historico')) ?>" class="inline-actions">
                                <?= csrfField() ?>
                                <input type="hidden" name="id" value="<?= e($reserva['id']) ?>">
                                <button class="button button--danger" type="submit" data-confirm="Apagar este historico de reserva?">Apagar</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$reservas): ?>
                <tr><td colspan="7">Nenhuma reserva cadastrada.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
