<section class="auth-card auth-card--portaria">
    <h1>Acesso Portaria</h1>

    <?php if (!empty($agenteSelecionado)): ?>
        <p>Agente selecionado: <strong><?= e($agenteSelecionado['nome']) ?></strong></p>
        <form method="post" action="<?= e(baseUrl('/login/portaria')) ?>" class="stack" autocomplete="off">
            <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>" data-csrf-token>
            <input type="hidden" name="agente_id" value="<?= e($agenteSelecionado['id']) ?>">
            <label>Senha
                <input type="password" name="senha" required autofocus autocomplete="current-password">
            </label>
            <button class="button" type="submit">Entrar na Portaria</button>
        </form>
        <div class="auth-navigation-links">
            <a href="<?= e(baseUrl('/login/portaria')) ?>">Escolher outro agente</a>
            <a href="<?= e(baseUrl('/login')) ?>">Acesso geral</a>
        </div>
    <?php else: ?>
        <p>Selecione seu nome. Na próxima tela, confirme sua senha individual.</p>

        <?php if (empty($agentesPortaria)): ?>
            <div class="empty-state auth-empty-state">Nenhum agente de portaria ativo foi encontrado.</div>
        <?php else: ?>
            <nav class="portaria-agent-list" aria-label="Agentes de portaria">
                <?php foreach ($agentesPortaria as $agente): ?>
                    <a class="portaria-agent" href="<?= e(baseUrl('/login/portaria?agente_id=' . (int) $agente['id'])) ?>">
                        <span class="portaria-agent__avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr(trim((string) $agente['nome']), 0, 1))) ?></span>
                        <span><?= e($agente['nome']) ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>

        <p class="auth-link"><a href="<?= e(baseUrl('/login')) ?>">Voltar ao acesso geral</a></p>
    <?php endif; ?>
</section>
