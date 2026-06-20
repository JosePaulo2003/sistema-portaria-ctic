<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'SGRP') ?></title>
    <link rel="stylesheet" href="<?= e(assetUrl('css/app.css')) ?>">
</head>
<body class="auth-page">
    <main class="auth-shell">
        <?= flash() ?>
        <?= $content ?>
    </main>
    <footer class="site-footer">© CTIC-CESIT. Todos os direitos reservados.</footer>
</body>
</html>
