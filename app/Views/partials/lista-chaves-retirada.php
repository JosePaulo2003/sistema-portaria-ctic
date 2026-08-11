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
                                <input type="password" name="senha_confirmacao" placeholder="Confirme sua senha" required autocomplete="current-password">
                                <button class="button" type="submit">Retirar chave</button>
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
                <tr><td colspan="4">Nenhuma chave encontrada para retirada no momento.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
