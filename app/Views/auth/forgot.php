<section class="auth-card">
    <h1>Recuperar senha</h1>
    <p>Informe o e-mail cadastrado. Você receberá um código de uso único, válido por 30 minutos.</p>
    <form method="post" action="<?= e(baseUrl('/recuperar-senha')) ?>" class="stack">
        <?= csrfField() ?>
        <label>E-mail
            <input type="email" name="email" required autofocus autocomplete="email" maxlength="190">
        </label>
        <button class="button" type="submit">Enviar código de recuperação</button>
        <a class="button button--secondary" href="<?= e(baseUrl('/login')) ?>">Voltar ao login</a>
    </form>
</section>
