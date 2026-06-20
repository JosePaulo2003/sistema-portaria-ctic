<section class="auth-card">
    <h1>SGRP</h1>
    <p>Sistema de Gestão de Recursos Pedagógicos</p>
    <form method="post" action="<?= e(baseUrl('/login')) ?>" class="stack">
        <?= csrfField() ?>
        <label>E-mail
            <input type="email" name="email" required autofocus value="desenvolvedor@sgrp.local">
        </label>
        <label>Senha
            <input type="password" name="senha" required value="12345678">
        </label>
        <button class="button" type="submit">Entrar</button>
    </form>
</section>
