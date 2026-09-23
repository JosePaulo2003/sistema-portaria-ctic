<?php
/**
 * GUIA DE MANUTENCAO: Este arquivo renderiza a tela auth do módulo layouts.
 *
 * Ponto de atencao: A view recebe dados prontos. Consultar banco aqui faria o HTML virar controller clandestino, e ninguém precisa desse segundo emprego.
 */
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'SGRP') ?></title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= e(assetUrl('assets/sgrp-favicon-32.png')) ?>">
    <link rel="icon" type="image/svg+xml" sizes="any" href="<?= e(assetUrl('assets/sgrp-favicon.svg')) ?>">
    <link rel="stylesheet" href="<?= e(assetUrl('css/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(assetUrl('css/usability-refresh.css')) ?>">
</head>
<body class="auth-page">
    <main class="auth-shell">
        <?= flash() ?>
        <?= $content ?>
    </main>
    <footer class="site-footer">© CTIC-CESIT. Todos os direitos reservados.</footer>
    <script src="<?= e(assetUrl('js/app.js')) ?>"></script>
</body>
</html>
