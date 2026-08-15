<section class="auth-card">
    <h1>SGRP</h1>
    <p>Sistema de Gestão de Recursos Pedagógicos</p>
    <form method="post" action="<?= e(baseUrl('/login')) ?>" class="stack" autocomplete="off">
        <?= csrfField() ?>
        <label>Nome ou e-mail
            <input type="text" name="identificador" required autofocus autocomplete="username" placeholder="Digite seu nome completo ou e-mail">
        </label>
        <label>Senha
            <input type="password" name="senha" required autocomplete="current-password">
        </label>
        <button class="button" type="submit">Entrar</button>
    </form>
    <p class="auth-link"><a href="<?= e(baseUrl('/recuperar-senha')) ?>">Esqueci minha senha</a></p>
    <div class="auth-divider"><span>ou</span></div>
    <a class="button button--secondary auth-secondary-action" href="<?= e(baseUrl('/login/portaria')) ?>">Acesso Portaria</a>
</section>
