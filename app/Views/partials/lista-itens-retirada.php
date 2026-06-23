<div class="card table-wrap">
    <table>
        <thead>
            <tr>
                <th>Item disponível</th>
                <th>Categoria</th>
                <th>Disponível</th>
                <th>Retirada</th>
                <th>Situação</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($itens as $item): ?>
                <tr>
                    <td>
                        <strong><?= e($item['nome']) ?></strong>
                        <?php if (!empty($item['codigo'])): ?>
                            <br><span class="muted"><?= e($item['codigo']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= e($item['categoria'] ?: 'Não informado') ?></td>
                    <td><?= e($item['quantidade_disponivel'] ?? $item['quantidade']) ?></td>
                    <td>
                        <form method="post" action="<?= e($retiradaItemAction) ?>" class="inline-form withdrawal-row">
                            <?= csrfField() ?>
                            <input type="hidden" name="item_portaria_id" value="<?= e($item['id']) ?>">
                            <input type="text" name="observacao" placeholder="<?= e($observacaoItemPlaceholder ?? 'Opcional') ?>">
                            <input type="password" name="senha_confirmacao" placeholder="Confirme sua senha" required autocomplete="current-password">
                            <button class="button" type="submit">Retirar item</button>
                        </form>
                    </td>
                    <td><span class="status-badge">disponível</span></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$itens): ?>
                <tr><td colspan="5">Nenhum item disponível para retirada no momento.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
