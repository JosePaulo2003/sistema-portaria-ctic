<div class="card table-wrap">
    <table>
        <thead>
            <tr>
                <th>Chave disponível</th>
                <th>Localização</th>
                <th>Retirada</th>
                <th>Situação</th>
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
                    <td><?= e(trim(($s['bloco'] ?? '') . ' ' . ($s['tipo_ambiente'] ?? '')) ?: 'Não informado') ?></td>
                    <td>
                        <form method="post" action="<?= e($retiradaAction) ?>" class="inline-form withdrawal-row">
                            <?= csrfField() ?>
                            <input type="hidden" name="sala_id" value="<?= e($s['id']) ?>">
                            <input type="text" name="observacao" placeholder="<?= e($observacaoPlaceholder ?? 'Opcional') ?>">
                            <button class="button" type="submit">Retirar chave</button>
                        </form>
                    </td>
                    <td><span class="status-badge">disponível</span></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$salas): ?>
                <tr><td colspan="4">Nenhuma chave disponível para retirada no momento.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
