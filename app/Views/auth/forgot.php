<section class="auth-card">
    <h1>Recuperar senha</h1>
    <p>Informe seu e-mail institucional para registrar a solicitacao de recuperacao.</p>
    <form method="post" action="<?= e(baseUrl('/recuperar-senha')) ?>" class="stack" autocomplete="off">
        <?= csrfField() ?>
        <label>E-mail
            <input type="email" name="email" required autofocus autocomplete="off">
        </label>
        <button class="button" type="submit">Registrar solicitacao</button>
        <a class="button button--secondary" href="<?= e(baseUrl('/login')) ?>">Voltar ao login</a>
    </form>
</section>
