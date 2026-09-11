<section class="auth-card">
    <h1>Redefinir senha</h1>
    <p>Digite o código enviado ao seu e-mail e escolha a nova senha.</p>
    <form method="post" action="<?= e(baseUrl('/redefinir-senha')) ?>" class="stack">
        <?= csrfField() ?>
        <label>E-mail
            <input type="email" name="email" value="<?= e($email ?? '') ?>" required autocomplete="email" maxlength="190">
        </label>
        <label>Código de recuperação
            <input type="text" name="codigo" required autofocus autocomplete="one-time-code" inputmode="text" maxlength="9" placeholder="ABCD-2345" style="text-transform:uppercase">
        </label>
        <label>Nova senha
            <input type="password" name="senha" required autocomplete="new-password" minlength="8" maxlength="255">
        </label>
        <label>Confirmar nova senha
            <input type="password" name="senha_confirmacao" required autocomplete="new-password" minlength="8" maxlength="255">
        </label>
        <button class="button" type="submit">Salvar nova senha</button>
        <a class="button button--secondary" href="<?= e(baseUrl('/recuperar-senha')) ?>">Solicitar novo código</a>
    </form>
    <p class="auth-link"><a href="<?= e(baseUrl('/login')) ?>">Voltar ao login</a></p>
</section>
