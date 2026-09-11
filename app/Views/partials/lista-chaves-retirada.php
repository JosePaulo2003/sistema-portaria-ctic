<?php
$perfilRetirada = comparableProfile((string) (currentUser()['perfil_nome'] ?? ''));
$exigeCodigoTemporario = in_array($perfilRetirada, array_map('comparableProfile', ['Aluno', 'Aluno Bolsista', 'Estagiário']), true);
$exigeConfirmacaoPortaria = $exigeCodigoTemporario;
?>
<div class="alert alert-info withdrawal-permission-notice">
    <strong>Permissão e solicitação obrigatórias:</strong> para solicitar ou renovar o acesso a qualquer chave, compareça presencialmente ao CTIC-CESIT. Aluno, Estagiário e Bolsista precisam entrar na própria conta e solicitar a retirada; a Portaria não pode iniciar o pedido em nome deles.
</div>
<?php if ($exigeCodigoTemporario): ?>
    <p class="withdrawal-instruction">Confirme sua senha normal para solicitar. Depois, apresente a senha temporária de 4 dígitos ao agente de Portaria.</p>
<?php else: ?>
    <p class="withdrawal-instruction">Este perfil tem retirada automática: com uma permissão válida, basta confirmar a senha normal para registrar a retirada.</p>
<?php endif; ?>
<div class="card table-wrap">
    <table>
        <thead>
            <tr>
                <th>Chave</th>
                <th>Localizacao</th>
                <th>Retirada</th>
                <th>Situacao</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($salas as $s): ?>
                <tr>
                    <td>
                        <strong><?= e($s['nome']) ?></strong>
                        <?php if (!empty($s['codigo'])): ?>
                            <br><span class="muted"><?= e($s['codigo']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= e(trim(($s['bloco'] ?? '') . ' ' . ($s['tipo_ambiente'] ?? '')) ?: 'Nao informado') ?></td>
                    <td>
                        <?php if (!empty($s['chave_retiravel'])): ?>
                            <form method="post" action="<?= e($retiradaAction) ?>" class="inline-form withdrawal-row">
                                <?= csrfField() ?>
                                <input type="hidden" name="sala_id" value="<?= e($s['id']) ?>">
                                <input type="text" name="observacao" placeholder="<?= e($observacaoPlaceholder ?? 'Opcional') ?>">
                                <input type="password" name="senha_confirmacao" placeholder="Senha normal da sua conta" required autocomplete="current-password">
                                <button class="button" type="submit"><?= $exigeConfirmacaoPortaria ? 'Solicitar retirada' : 'Retirar chave' ?></button>
                            </form>
                        <?php else: ?>
                            <span class="muted"><?= e($s['chave_motivo'] ?? 'Chave indisponivel no momento.') ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="status-badge status-<?= e($s['chave_status'] ?? 'disponivel') ?>">
                            <?= e($s['chave_status_label'] ?? 'disponivel') ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$salas): ?>
                <tr><td colspan="4">Nenhuma permissão de chave ativa. Compareça ao CTIC-CESIT para solicitar o acesso.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
