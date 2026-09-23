<?php
/**
 * GUIA DE MANUTENCAO: Este arquivo renderiza a tela retiradas do módulo portaria.
 *
 * Ponto de atencao: A view recebe dados prontos. Consultar banco aqui faria o HTML virar controller clandestino, e ninguém precisa desse segundo emprego.
 */
?>
<section class="section-header">
    <h1>Retiradas</h1>
    <p>Filtre as solicitações pelo grupo, confira a pessoa e aceite ou recuse a entrega.</p>
</section>

<article class="card withdrawal-flow-help">
    <strong>Como liberar uma chave</strong>
    <p>O agente de Portaria não inicia retiradas em nome de Aluno, Estagiário ou Bolsista; o pedido precisa partir da conta da própria pessoa.</p>
    <ol>
        <li>O usuário solicita a chave e confirma a senha normal.</li>
        <li>Aluno, Bolsista e Estagiário apresentam a senha temporária simples de 4 dígitos.</li>
        <li>Confira se a senha exibida nas duas telas é a mesma e escolha Aceitar ou Recusar.</li>
    </ol>
    <p>As solicitações e retiradas são organizadas automaticamente pelo perfil real de cada usuário. O alerta pode ser minimizado e só desaparece depois que você aceitar ou recusar.</p>
</article>

<?php
$perfisPorUsuario = [];
foreach ($usuarios as $usuario) {
    $perfisPorUsuario[(int) $usuario['id']] = fixMojibakeText((string) ($usuario['perfil_nome'] ?? 'Perfil não informado'));
}
$movimentacoesPorPerfil = [];
foreach ($movimentacoes as $movimentacao) {
    $perfilMovimentacao = $perfisPorUsuario[(int) ($movimentacao['usuario_id'] ?? 0)] ?? 'Perfil não informado';
    $movimentacao['_perfil_nome'] = $perfilMovimentacao;
    $movimentacoesPorPerfil[$perfilMovimentacao][] = $movimentacao;
}
uksort($movimentacoesPorPerfil, 'strnatcasecmp');
?>

<form method="post" action="<?= e(baseUrl('/portaria/retiradas/registrar-chave')) ?>" class="card withdrawal-register-card" data-manual-withdrawal-form hidden aria-hidden="true">
    <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>" data-csrf-token>
    <header class="withdrawal-register-card__header">
        <div>
            <span class="withdrawal-register-card__eyebrow">Registro de chave</span>
            <h2>Nova retirada</h2>
            <p>Identifique a pessoa e escolha a chave que será entregue.</p>
        </div>
        <span class="status-badge status-disponivel">Portaria</span>
    </header>

    <div class="withdrawal-register-card__primary">
        <label class="withdrawal-register-field">
            <span>Quem retirou</span>
            <select name="usuario_id" required data-manual-withdrawal-select>
                <option value="">Selecione uma pessoa</option>
                <option value="nao_cadastrada">Pessoa sem cadastro</option>
                <?php foreach ($usuariosRetiradaPorPerfil as $perfil => $usuariosDoPerfil): ?>
                    <optgroup label="<?= e($perfil) ?>">
                        <?php foreach ($usuariosDoPerfil as $u): ?>
                            <option value="<?= e($u['id']) ?>"><?= e($u['nome']) ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endforeach; ?>
            </select>
            <small>Aluno, Aluno Bolsista e Estagiário solicitam pela própria conta e não aparecem nesta lista.</small>
        </label>

        <label class="withdrawal-register-field">
            <span>Chave da sala</span>
            <select name="sala_id" required>
                <option value="">Selecione uma chave</option>
                <?php foreach ($salas as $sala): ?>
                    <?php $indisponivel = empty($sala['chave_retiravel']); ?>
                    <option value="<?= e($sala['id']) ?>" <?= $indisponivel ? 'disabled' : '' ?>>
                        <?= e($sala['nome']) ?><?= $indisponivel ? ' — ' . e($sala['chave_motivo'] ?? 'indisponível') : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small>Chaves já retiradas ou bloqueadas aparecem desativadas.</small>
        </label>
    </div>

    <label class="withdrawal-manual-field" data-manual-withdrawal-name hidden>
        <span class="withdrawal-manual-field__icon" aria-hidden="true">+</span>
        <span class="withdrawal-manual-field__content">
            <strong>Nome da pessoa sem cadastro</strong>
            <input type="text" name="usuario_nome_manual" maxlength="180" autocomplete="name" placeholder="Digite o nome completo" disabled>
            <small>Esse nome ficará registrado nas devoluções, no histórico e nos relatórios.</small>
        </span>
    </label>

    <label class="withdrawal-register-field withdrawal-register-field--observation">
        <span>Observação <small>(opcional)</small></span>
        <textarea name="observacao" placeholder="Ex.: autorização apresentada, setor responsável ou outra informação importante"></textarea>
    </label>

    <div class="form-actions withdrawal-register-card__actions">
        <button class="button" type="submit">Registrar retirada</button>
    </div>
</form>

<section class="section-header section-header--compact">
    <h2>Devolucoes em aberto</h2>
</section>

<div class="card table-wrap">
    <table>
        <thead>
            <tr>
                <th>Usuario</th>
                <th>Sala/Item</th>
                <th>Retirada</th>
                <th>Devolver</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($movimentacoesPorPerfil as $perfil => $movimentacoesDoPerfil): ?>
                <tr class="withdrawal-profile-group">
                    <th colspan="4">
                        <span><?= e($perfil) ?></span>
                        <small><?= count($movimentacoesDoPerfil) ?> <?= count($movimentacoesDoPerfil) === 1 ? 'retirada' : 'retiradas' ?></small>
                    </th>
                </tr>
                <?php foreach ($movimentacoesDoPerfil as $m): ?>
                    <tr>
                        <td><?= e($m['usuario_nome']) ?></td>
                        <td><?= e($m['sala_nome'] ?? $m['item_nome'] ?? '-') ?></td>
                        <td><?= e(formatDateTimeBr($m['retirada_em'] ?? null)) ?></td>
                        <td>
                            <form method="post" action="<?= e(baseUrl($m['sala_id'] ? '/portaria/retiradas/devolver-chave' : '/portaria/retiradas/devolver-item')) ?>" class="inline-form">
                                <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>" data-csrf-token>
                                <input type="hidden" name="movimentacao_id" value="<?= e($m['id']) ?>">
                                <select name="devolvido_por_usuario_id">
                                    <option value="<?= e($m['usuario_id']) ?>">Mesma pessoa</option>
                                    <option value="nao_cadastrada">Pessoa nao cadastrada</option>
                                    <?php foreach ($usuarios as $u): ?>
                                        <option value="<?= e($u['id']) ?>"><?= e($u['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="text" name="observacao" placeholder="Observacao opcional">
                                <button class="button" type="submit">Registrar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>
            <?php if (!$movimentacoes): ?>
                <tr><td colspan="4">Nenhuma retirada aberta.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
