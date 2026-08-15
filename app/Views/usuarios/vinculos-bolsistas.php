<section class="section-header">
    <h1>Vinculos de Bolsistas</h1>
    <p>Associe bolsistas e estagiarios aos professores responsaveis.</p>
</section>

<section class="card form-card">
    <h2>Atualizar vinculo</h2>
    <form method="post" action="<?= e(baseUrl($retorno)) ?>" class="form-grid">
        <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>" data-csrf-token>
        <label>
            Bolsista ou estagiario
            <select name="bolsista_id" required>
                <option value="">Selecione</option>
                <?php foreach ($bolsistas as $bolsista): ?>
                    <option value="<?= e($bolsista['id']) ?>">
                        <?= e($bolsista['nome']) ?> - <?= e($bolsista['perfil_nome']) ?><?= !empty($bolsista['professor_nome']) ? ' - atual: ' . e($bolsista['professor_nome']) : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            Professor responsavel
            <select name="professor_id" required>
                <option value="">Selecione</option>
                <?php foreach ($professores as $professor): ?>
                    <option value="<?= e($professor['id']) ?>"><?= e($professor['nome']) ?> - <?= e($professor['email']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <div class="form-actions">
            <button class="button" type="submit">Salvar vinculo</button>
        </div>
    </form>
</section>

<section class="resource-section">
    <h2>Vinculos cadastrados</h2>
    <div class="card table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Bolsista/Estagiario</th>
                    <th>E-mail</th>
                    <th>Perfil</th>
                    <th>Professor responsavel</th>
                    <th>Situacao</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bolsistas as $bolsista): ?>
                    <tr>
                        <td><?= e($bolsista['nome']) ?></td>
                        <td><?= e($bolsista['email']) ?></td>
                        <td><?= e($bolsista['perfil_nome']) ?></td>
                        <td><?= e($bolsista['professor_nome'] ?? 'Sem vinculo') ?></td>
                        <td><span class="status-badge status-<?= e($bolsista['situacao']) ?>"><?= e($bolsista['situacao']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$bolsistas): ?>
                    <tr><td colspan="5">Nenhum bolsista ou estagiario cadastrado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
