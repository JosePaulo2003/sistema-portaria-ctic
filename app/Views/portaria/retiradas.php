<section class="section-header"><h1>Registrar Devoluções</h1></section>

<div class="card table-wrap">
    <table>
        <thead>
            <tr>
                <th>Usuário</th>
                <th>Sala/Item</th>
                <th>Retirada</th>
                <th>Devolver</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($movimentacoes as $m): ?>
                <tr>
                    <td><?= e($m['usuario_nome']) ?></td>
                    <td><?= e($m['sala_nome'] ?? $m['item_nome'] ?? '-') ?></td>
                    <td><?= e($m['retirada_em']) ?></td>
                    <td>
                        <form method="post" action="<?= e(baseUrl($m['sala_id'] ? '/portaria/retiradas/devolver-chave' : '/portaria/retiradas/devolver-item')) ?>" class="inline-form">
                            <?= csrfField() ?>
                            <input type="hidden" name="movimentacao_id" value="<?= e($m['id']) ?>">
                            <select name="devolvido_por_usuario_id">
                                <option value="<?= e($m['usuario_id']) ?>">Mesma pessoa</option>
                                <option value="nao_cadastrada">Pessoa não cadastrada</option>
                                <?php foreach ($usuarios as $u): ?>
                                    <option value="<?= e($u['id']) ?>"><?= e($u['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" name="observacao" placeholder="Observação opcional">
                            <button class="button" type="submit">Registrar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$movimentacoes): ?>
                <tr><td colspan="4">Nenhuma retirada aberta.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
