<?php
$user = currentUser();
$profile = $user['perfil_nome'] ?? '';
$nav = [
    'Desenvolvedor' => [
        ['Desenvolvedor', '/desenvolvedor'],
        ['Usuários', '/desenvolvedor/usuarios'],
        ['Solicitações', '/desenvolvedor/usuarios/solicitacoes'],
        ['Logs', '/desenvolvedor/logs'],
        ['Advertências', '/desenvolvedor/advertencias'],
    ],
    'Administrativo' => [
        ['Administrativo', '/administrativo'],
        ['Reservas', '/administrativo/reservas-salas'],
        ['Retiradas', '/administrativo/retiradas'],
        ['Disponibilidade', '/administrativo/disponibilidade-salas'],
    ],
    'Secretário de Curso' => [
        ['Secretário', '/secretario'],
        ['Períodos', '/secretario/periodos-academicos'],
        ['Matérias', '/secretario/materias'],
        ['Bolsistas', '/secretario/bolsistas'],
        ['Aulas', '/secretario/reservas-aulas'],
        ['Salas', '/secretario/salas'],
        ['Chaves', '/secretario/chaves-autorizadas'],
        ['Retirada', '/secretario/retirada-chaves'],
        ['Itens', '/secretario/itens'],
    ],
    'Agente de Portaria' => [
        ['Portaria', '/portaria'],
        ['Retiradas', '/portaria/retiradas'],
        ['Reservas', '/portaria/reservas'],
        ['Permissões', '/portaria/permissoes'],
        ['Visitantes', '/portaria/visitantes'],
        ['Salas Hoje', '/portaria/salas-hoje'],
        ['Histórico', '/portaria/historico'],
    ],
    'Professor' => [
        ['Professor', '/professor'],
        ['Disponibilidade', '/professor/disponibilidade-salas'],
        ['Reservas', '/professor/reservas-salas'],
        ['Aulas', '/professor/aulas-semestre'],
        ['Bolsistas', '/professor/orientandos-bolsistas'],
        ['Retiradas', '/professor/retiradas'],
    ],
    'Aluno Bolsista' => [
        ['Bolsista', '/bolsista'],
        ['Sala de Pesquisa', '/bolsista/sala-pesquisa'],
        ['Retiradas', '/bolsista/retiradas'],
    ],
    'Aluno' => [
        ['Aluno', '/aluno'],
        ['Consulta de Salas', '/aluno/consulta-salas'],
    ],
    'Visitante' => [
        ['Visitante', '/visitante'],
        ['Chave', '/visitante/chave'],
    ],
    'Serviços Gerais' => [
        ['Serviços Gerais', '/servicos-gerais'],
        ['Retiradas', '/servicos-gerais/retiradas'],
    ],
];
$developerGroups = [
    'Técnico' => $nav['Desenvolvedor'],
    'Administrativo' => $nav['Administrativo'],
    'Secretaria' => $nav['Secretário de Curso'],
    'Portaria' => $nav['Agente de Portaria'],
    'Professor' => $nav['Professor'],
    'Bolsista' => $nav['Aluno Bolsista'],
    'Aluno' => $nav['Aluno'],
    'Visitante' => $nav['Visitante'],
    'Serviços Gerais' => $nav['Serviços Gerais'],
];
$items = $nav[$profile] ?? [];
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'SGRP') ?></title>
    <link rel="stylesheet" href="<?= e(assetUrl('css/app.css')) ?>">
</head>
<body>
    <header class="topbar">
        <div class="topbar__brand">
            <a class="topbar__home" href="<?= e(baseUrl(moduleForProfile($profile))) ?>" aria-label="Início do SGRP">
                <img class="topbar__logo topbar__logo--sgrp" src="<?= e(assetUrl('assets/sgrp_logo.png')) ?>" alt="SGRP">
            </a>
            <div class="topbar__title">
                <span><?= e($title ?? $profile) ?></span>
            </div>
        </div>
        <button class="menu-toggle" data-menu-toggle aria-label="Abrir menu">☰</button>
        <div class="topbar__user">
            <?php if (!empty($user['foto_perfil_url'])): ?>
                <img class="topbar__avatar" src="<?= e(baseUrl($user['foto_perfil_url'])) ?>" alt="Foto de perfil">
            <?php endif; ?>
            <span><?= e($user['nome'] ?? '') ?></span>
            <a class="button button--secondary" href="<?= e(baseUrl('/perfil')) ?>">Meu Perfil</a>
            <form method="post" action="<?= e(baseUrl('/logout')) ?>">
                <?= csrfField() ?>
                <button class="button" type="submit">Sair</button>
            </form>
            <img class="topbar__logo topbar__logo--uea" src="<?= e(assetUrl('assets/uea_logo_white.png')) ?>" alt="UEA">
        </div>
    </header>
    <nav class="admin-nav" data-admin-nav>
        <?php if ($profile === 'Desenvolvedor'): ?>
            <?php foreach ($developerGroups as $groupName => $groupItems): ?>
                <div class="nav-group">
                    <button class="nav-group__button" type="button"><?= e($groupName) ?></button>
                    <div class="nav-group__menu">
                        <?php foreach ($groupItems as $item): ?>
                            <a href="<?= e(baseUrl($item[1])) ?>"><?= e($item[0]) ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <?php foreach ($items as $item): ?>
                <a href="<?= e(baseUrl($item[1])) ?>"><?= e($item[0]) ?></a>
            <?php endforeach; ?>
        <?php endif; ?>
    </nav>
    <main class="page-shell">
        <?= flash() ?>
        <?= $content ?>
    </main>
    <footer class="site-footer">© CTIC-CESIT. Todos os direitos reservados.</footer>
    <script src="<?= e(assetUrl('js/app.js')) ?>"></script>
</body>
</html>
