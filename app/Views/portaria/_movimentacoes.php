<div class="card table-wrap">
    <table>
        <thead>
            <tr>
                <th>Usuário</th>
                <th>Foto</th>
                <th>Sala/Item</th>
                <th>Tipo</th>
                <th>Retirada</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (($movimentacoes ?? []) as $m): ?>
                <tr>
                    <td><?= e($m['usuario_nome'] ?? '-') ?></td>
                    <td>
                        <?php if (!empty($m['foto_perfil_url'])): ?>
                            <img class="avatar" src="<?= e(baseUrl($m['foto_perfil_url'])) ?>" alt="Foto de perfil">
                        <?php else: ?>
                            <span class="avatar avatar--empty">-</span>
                        <?php endif; ?>
                    </td>
                    <td><?= e($m['sala_nome'] ?? $m['item_nome'] ?? '-') ?></td>
                    <td><?= e(str_replace('_', ' ', $m['tipo_movimentacao'])) ?></td>
                    <td><?= e($m['retirada_em'] ?? $m['criado_em']) ?></td>
                    <td><span class="status-badge"><?= e($m['situacao']) ?></span></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($movimentacoes)): ?>
                <tr><td colspan="6">Nenhuma movimentação encontrada.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
