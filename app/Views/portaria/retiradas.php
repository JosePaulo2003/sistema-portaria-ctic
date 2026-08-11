<section class="section-header">
    <h1>Retiradas</h1>
    <p>Registre retiradas de chaves para usuarios autorizados e acompanhe as devolucoes em aberto.</p>
</section>

<form method="post" action="<?= e(baseUrl('/portaria/retiradas/registrar-chave')) ?>" class="card form-grid">
    <?= csrfField() ?>
    <label>Usuario que retirou
        <select name="usuario_id" required>
            <option value="">Selecione</option>
            <?php foreach ($usuarios as $u): ?>
                <?php if (($u['situacao'] ?? '') === 'ativo'): ?>
                    <option value="<?= e($u['id']) ?>"><?= e($u['nome']) ?></option>
                <?php endif; ?>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Chave
        <select name="sala_id" required>
            <option value="">Selecione</option>
            <?php foreach ($salas as $sala): ?>
                <option value="<?= e($sala['id']) ?>"><?= e($sala['nome']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label class="full">Observacao
        <textarea name="observacao" placeholder="Opcional"></textarea>
    </label>
    <div class="form-actions">
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
            <?php if (!$movimentacoes): ?>
                <tr><td colspan="4">Nenhuma retirada aberta.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
