<?php
$user = currentUser();
$profile = fixMojibakeText((string) ($user['perfil_nome'] ?? ''));
$nav = [
    'Desenvolvedor' => [
        ['Desenvolvedor', '/desenvolvedor'],
        ['Usuários', '/desenvolvedor/usuarios'],
        ['Solicitações', '/desenvolvedor/usuarios/solicitacoes'],
        ['Logs', '/desenvolvedor/logs'],
        ['Vinculos', '/desenvolvedor/vinculos-bolsistas'],
        ['Advertências', '/desenvolvedor/advertencias'],
        ['Salas', '/portaria/salas'],
        ['Itens', '/portaria/itens'],
    ],
    'Administrativo' => [
        ['Administrativo', '/administrativo'],
        ['Reservas', '/administrativo/reservas-salas'],
        ['Retiradas', '/administrativo/retiradas'],
        ['Disponibilidade', '/administrativo/disponibilidade-salas'],
        ['Chaves', '/administrativo/chaves-autorizadas'],
    ],
    'Diretor' => [
        ['Direção', '/diretor'],
        ['Chaves', '/diretor/chaves'],
        ['Reservas', '/diretor/reservas'],
        ['Relatórios', '/diretor/relatorios'],
        ['Disponibilidade', '/diretor/disponibilidade'],
    ],
    'Secretário de Curso' => [
        ['Secretário', '/secretario'],
        ['Cursos', '/secretario/cursos'],
        ['Períodos', '/secretario/periodos-academicos'],
        ['Reservas', '/secretario/reservas-curso'],
        ['Retirada', '/secretario/retirada-chaves'],
    ],
    'Coordenador de Curso' => [
        ['Coordenador', '/coordenador'],
        ['Matérias', '/coordenador/materias'],
        ['Reservas', '/coordenador/reservas-aulas'],
    ],
    'Agente de Portaria' => [
        ['Portaria', '/portaria'],
        ['Retiradas', '/portaria/retiradas'],
        ['Reservas', '/portaria/reservas'],
        ['Vinculos', '/portaria/vinculos-bolsistas'],
        ['Permissões', '/portaria/permissoes'],
        ['Visitantes', '/portaria/visitantes'],
        ['Salas', '/portaria/salas'],
        ['Itens', '/portaria/itens'],
        ['Salas Hoje', '/portaria/salas-hoje'],
        ['Histórico', '/portaria/historico'],
        ['Imprimir Movimentações', '/portaria/relatorio-movimentacoes'],
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
        ['Retiradas', '/bolsista/retiradas'],
    ],
    'Estagiario' => [
        ['Estagiario', '/bolsista'],
        ['Retiradas', '/bolsista/retiradas'],
    ],
    'EstagiÃ¡rio' => [
        ['Estagiario', '/bolsista'],
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
    'Motorista' => [
        ['Consulta de Salas', '/motorista'],
        ['Retirada de Chave', '/motorista/retiradas'],
    ],
    'Serviços Gerais' => [
        ['Serviços Gerais', '/servicos-gerais'],
        ['Retiradas', '/servicos-gerais/retiradas'],
    ],
    'Técnico' => [
        ['Salas', '/portaria/salas'],
        ['Itens', '/portaria/itens'],
    ],
];
$nav['Tecnico'] = $nav['Técnico'];
$developerGroups = [
    'Técnico' => $nav['Desenvolvedor'],
    'Administrativo' => $nav['Administrativo'],
    'Direção' => $nav['Diretor'],
    'Secretaria' => $nav['Secretário de Curso'],
    'Coordenador' => $nav['Coordenador de Curso'],
    'Portaria' => $nav['Agente de Portaria'],
    'Professor' => $nav['Professor'],
    'Bolsista' => $nav['Aluno Bolsista'],
    'Estagiario' => $nav['Estagiario'],
    'Aluno' => $nav['Aluno'],
    'Visitante' => $nav['Visitante'],
    'Motorista' => $nav['Motorista'],
    'Serviços Gerais' => $nav['Serviços Gerais'],
];
$items = $nav[$profile] ?? [];
$calendarItem = ['Calendário', '/calendario-salas'];
if (!in_array('/calendario-salas', array_column($items, 1), true)) {
    $items[] = $calendarItem;
}
if (
    $user
    && !isDeveloper()
    && comparableProfile((string) $profile) === comparableProfile('Aluno')
    && (new \App\Models\PermissaoSala())->usuarioTemChaveAtribuida((int) $user['id'])
) {
    $temRetirada = false;
    foreach ($items as $item) {
        if (in_array($item[1], ['/administrativo/retiradas', '/diretor/chaves', '/secretario/retirada-chaves', '/professor/retiradas', '/bolsista/retiradas', '/visitante/chave', '/motorista/retiradas', '/servicos-gerais/retiradas', '/retiradas-autorizadas'], true)) {
            $temRetirada = true;
            break;
        }
    }
    if (!$temRetirada) {
        $items[] = ['Retiradas', '/retiradas-autorizadas'];
    }
}
$guideCatalog = require dirname(__DIR__, 3) . '/config/guide.php';
$guideProfile = fixMojibakeText((string) $profile);
$guideProfileDescription = $guideCatalog['profiles'][$guideProfile]
    ?? 'Este guia apresenta as principais funções disponíveis para o seu perfil.';
$guideRequestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$guideBasePath = basePath();
if ($guideBasePath !== '' && str_starts_with($guideRequestPath, $guideBasePath)) {
    $guideRequestPath = substr($guideRequestPath, strlen($guideBasePath)) ?: '/';
}
$guideCurrentPath = rtrim('/' . ltrim($guideRequestPath, '/'), '/') ?: '/';
$guideDetailsMap = $guideCatalog['details'] ?? [];
$guideTopics = [[
    'id' => 'visao-geral',
    'type' => 'overview',
    'title' => 'Conhecer todo o meu acesso',
    'description' => 'Faça um passeio pelos menus disponíveis para o seu perfil.',
    'category' => 'Comece aqui',
    'functions' => ['Conhecer os menus', 'Entender seu perfil', 'Encontrar a Ajuda'],
    'current' => false,
    'url' => null,
]];
$guideSteps = [[
    'target' => '[data-guide-home]',
    'title' => 'Bem-vindo ao SGRP',
    'description' => $guideProfileDescription,
]];
foreach ($items as $guideItem) {
    $guidePath = (string) $guideItem[1];
    $guideDetails = $guideCatalog['paths'][$guidePath] ?? [
        (string) $guideItem[0],
        'Use esta opção para acessar uma função disponível para o seu perfil.',
    ];
    $guideMetadata = $guideDetailsMap[$guidePath] ?? [];
    $guideSteps[] = [
        'target' => '[data-guide-path="' . $guidePath . '"]',
        'title' => (string) ($guideDetails[0] ?? $guideItem[0]),
        'description' => (string) ($guideDetails[1] ?? ''),
    ];
    $guideTopics[] = [
        'id' => 'pagina:' . $guidePath,
        'type' => 'page',
        'path' => $guidePath,
        'title' => (string) ($guideDetails[0] ?? $guideItem[0]),
        'description' => (string) ($guideDetails[1] ?? ''),
        'category' => (string) ($guideMetadata['category'] ?? 'Outras funções'),
        'functions' => array_values($guideMetadata['functions'] ?? []),
        'current' => $guidePath === $guideCurrentPath,
        'url' => baseUrl($guidePath),
    ];
}
$guideTopicPaths = array_column($guideTopics, 'path');
if (isset($guideCatalog['paths'][$guideCurrentPath]) && !in_array($guideCurrentPath, $guideTopicPaths, true)) {
    $guideCurrentDetails = $guideCatalog['paths'][$guideCurrentPath];
    $guideCurrentMetadata = $guideDetailsMap[$guideCurrentPath] ?? [];
    array_splice($guideTopics, 1, 0, [[
        'id' => 'pagina-atual:' . $guideCurrentPath,
        'type' => 'page',
        'path' => $guideCurrentPath,
        'title' => (string) ($guideCurrentDetails[0] ?? 'Página atual'),
        'description' => (string) ($guideCurrentDetails[1] ?? 'Conheça todos os recursos desta página.'),
        'category' => (string) ($guideCurrentMetadata['category'] ?? 'Página atual'),
        'functions' => array_values($guideCurrentMetadata['functions'] ?? []),
        'current' => true,
        'url' => baseUrl($guideCurrentPath),
    ]]);
}
$guideSteps[] = [
    'target' => '[data-guide-profile]',
    'title' => 'Meu Perfil',
    'description' => 'Atualize seus dados, sua foto e sua senha sempre que necessário.',
];
$guideSteps[] = [
    'target' => null,
    'title' => 'Pronto para usar',
    'description' => 'O botão Ajuda fica disponível no topo da tela sempre que você quiser iniciar outro tutorial.',
];
if (!in_array('/perfil', array_column($guideTopics, 'path'), true)) {
    $guideTopics[] = [
        'id' => 'pagina:/perfil',
        'type' => 'page',
        'path' => '/perfil',
        'title' => 'Atualizar meu perfil',
        'description' => 'Aprenda onde alterar seus dados, sua foto e sua senha.',
        'category' => 'Minha conta',
        'functions' => array_values($guideDetailsMap['/perfil']['functions'] ?? ['Alterar dados pessoais', 'Trocar senha']),
        'current' => false,
        'url' => baseUrl('/perfil'),
    ];
}
$guideConfigJson = json_encode([
    'version' => (string) ($guideCatalog['version'] ?? '1'),
    'userId' => (int) ($user['id'] ?? 0),
    'profile' => $guideProfile,
    'currentPath' => $guideCurrentPath,
    'steps' => $guideSteps,
    'topics' => $guideTopics,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$iconPaths = [
    'alert' => '<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
    'book' => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5z"/>',
    'box' => '<path d="m21 8-9-5-9 5 9 5 9-5Z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/>',
    'briefcase' => '<path d="M10 6V5a2 2 0 0 1 2-2h0a2 2 0 0 1 2 2v1"/><path d="M3 7h18v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z"/><path d="M3 13h18"/>',
    'building' => '<path d="M3 21h18"/><path d="M5 21V5a2 2 0 0 1 2-2h7v18"/><path d="M19 21V9a2 2 0 0 0-2-2h-3"/><path d="M9 7h1"/><path d="M9 11h1"/><path d="M9 15h1"/>',
    'calendar' => '<path d="M8 2v4"/><path d="M16 2v4"/><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M3 10h18"/><path d="m9 16 2 2 4-4"/>',
    'calendar-days' => '<path d="M8 2v4"/><path d="M16 2v4"/><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M3 10h18"/><path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/><path d="M8 18h.01"/><path d="M12 18h.01"/>',
    'clipboard' => '<rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M8 12h8"/><path d="M8 16h6"/>',
    'code' => '<path d="m16 18 6-6-6-6"/><path d="m8 6-6 6 6 6"/>',
    'door' => '<path d="M13 4h3a2 2 0 0 1 2 2v14"/><path d="M2 20h20"/><path d="M13 20V4L5 6v14"/><path d="M10 12h.01"/>',
    'file' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M8 13h8"/><path d="M8 17h5"/>',
    'graduation' => '<path d="m22 10-10-5-10 5 10 5 10-5Z"/><path d="M6 12v5c3 2 9 2 12 0v-5"/><path d="M22 10v6"/>',
    'help' => '<circle cx="12" cy="12" r="10"/><path d="M9.1 9a3 3 0 1 1 5.8 1c0 2-3 2-3 4"/><path d="M12 18h.01"/>',
    'history' => '<path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 3v6h6"/><path d="M12 7v5l3 2"/>',
    'id-card' => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="M15 8h2"/><path d="M15 12h2"/><path d="M7 16h4"/>',
    'key' => '<circle cx="7.5" cy="14.5" r="3.5"/><path d="m10 12 9-9"/><path d="m15 4 3 3"/><path d="m13 6 3 3"/>',
    'log-out' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
    'menu' => '<path d="M4 6h16"/><path d="M4 12h16"/><path d="M4 18h16"/>',
    'presentation' => '<path d="M2 3h20"/><path d="M21 3v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V3"/><path d="m7 21 5-5 5 5"/>',
    'printer' => '<path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/><path d="M18 12h.01"/>',
    'search' => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/><path d="m8.5 11 2 2 4-4"/>',
    'shield' => '<path d="M20 13c0 5-3.5 7.5-8 9-4.5-1.5-8-4-8-9V5l8-3 8 3v8Z"/><path d="m9 12 2 2 4-4"/>',
    'swap' => '<path d="M17 3l4 4-4 4"/><path d="M21 7H7"/><path d="M7 21l-4-4 4-4"/><path d="M3 17h14"/>',
    'user' => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
    'user-check' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="m16 11 2 2 4-4"/>',
    'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.9"/><path d="M16 3.1a4 4 0 0 1 0 7.8"/>',
    'wrench' => '<path d="M14.7 6.3a4 4 0 0 0-5 5L3 18l3 3 6.7-6.7a4 4 0 0 0 5-5l-2.4 2.4-3-3 2.4-2.4Z"/>',
];
$iconForPath = static function (string $path): string {
    return match ($path) {
        '/desenvolvedor' => 'code',
        '/desenvolvedor/usuarios' => 'users',
        '/desenvolvedor/usuarios/solicitacoes' => 'clipboard',
        '/desenvolvedor/logs' => 'file',
        '/desenvolvedor/vinculos-bolsistas', '/portaria/vinculos-bolsistas' => 'user-check',
        '/desenvolvedor/advertencias' => 'alert',
        '/administrativo' => 'briefcase',
        '/administrativo/reservas-salas', '/diretor/reservas', '/secretario/reservas-curso', '/professor/reservas-salas', '/coordenador/reservas-aulas', '/portaria/reservas' => 'calendar',
        '/calendario-salas' => 'calendar-days',
        '/administrativo/retiradas', '/administrativo/chaves-autorizadas', '/diretor/chaves', '/secretario/retirada-chaves', '/professor/retiradas', '/bolsista/retiradas', '/visitante/chave', '/motorista/retiradas', '/servicos-gerais/retiradas', '/retiradas-autorizadas' => 'key',
        '/administrativo/disponibilidade-salas', '/diretor/disponibilidade', '/professor/disponibilidade-salas', '/aluno/consulta-salas', '/motorista' => 'search',
        '/diretor' => 'building',
        '/diretor/movimentacoes', '/diretor/relatorios' => 'history',
        '/secretario' => 'clipboard',
        '/secretario/cursos' => 'graduation',
        '/secretario/periodos-academicos' => 'calendar-days',
        '/professor/orientandos-bolsistas' => 'user-check',
        '/portaria/salas', '/portaria/salas-hoje' => 'door',
        '/portaria/itens' => 'box',
        '/coordenador', '/coordenador/materias', '/professor/aulas-semestre' => 'book',
        '/portaria' => 'shield',
        '/portaria/permissoes' => 'shield',
        '/portaria/visitantes', '/visitante' => 'user',
        '/portaria/historico' => 'history',
        '/portaria/relatorio-movimentacoes' => 'printer',
        '/professor' => 'presentation',
        '/bolsista', '/aluno' => 'graduation',
        '/servicos-gerais' => 'wrench',
        default => 'file',
    };
};
$iconForGroup = static function (string $groupName): string {
    $group = comparableProfile($groupName);
    if (str_contains($group, 'tecnico')) return 'code';
    if (str_contains($group, 'administrativo')) return 'briefcase';
    if (str_contains($group, 'direcao')) return 'building';
    if (str_contains($group, 'secretaria')) return 'clipboard';
    if (str_contains($group, 'coordenador')) return 'book';
    if (str_contains($group, 'portaria')) return 'shield';
    if (str_contains($group, 'professor')) return 'presentation';
    if (str_contains($group, 'bolsista')) return 'user-check';
    if (str_contains($group, 'estagiario')) return 'id-card';
    if (str_contains($group, 'aluno')) return 'graduation';
    if (str_contains($group, 'visitante')) return 'user';
    if (str_contains($group, 'motorista')) return 'user';
    if (str_contains($group, 'servicos')) return 'wrench';
    return 'file';
};
$navIcon = static function (string $icon, string $class = 'nav-icon') use ($iconPaths): string {
    $paths = $iconPaths[$icon] ?? $iconPaths['file'];
    return '<svg class="' . e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $paths . '</svg>';
};
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'SGRP') ?></title>
    <link rel="stylesheet" href="<?= e(assetUrl('css/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(assetUrl('css/guide.css')) ?>">
</head>
<body class="<?= comparableProfile($profile) === comparableProfile('Agente de Portaria') ? 'low-vision-ui' : '' ?>">
    <header class="topbar">
        <div class="topbar__brand">
            <a class="topbar__home" href="<?= e(baseUrl(moduleForProfile($profile))) ?>" aria-label="Início do SGRP" data-guide-home>
                <img class="topbar__logo topbar__logo--sgrp" src="<?= e(assetUrl('assets/sgrp_logo.png')) ?>" alt="SGRP">
            </a>
            <div class="topbar__title">
                <span><?= e($title ?? $profile) ?></span>
            </div>
        </div>
        <button class="menu-toggle" data-menu-toggle type="button" aria-label="Abrir menu" aria-controls="admin-nav" aria-expanded="false"><?= $navIcon('menu', 'menu-toggle__icon') ?></button>
        <div class="topbar__user">
            <?php if (!empty($user['foto_perfil_url'])): ?>
                <img class="topbar__avatar" src="<?= e(baseUrl($user['foto_perfil_url'])) ?>" alt="Foto de perfil">
            <?php endif; ?>
            <span><?= e($user['nome'] ?? '') ?></span>
            <button class="button button--secondary guide-start-button" type="button" data-guide-start title="Ajuda e tutoriais do SGRP"><?= $navIcon('help', 'button__icon') ?><span>Ajuda</span></button>
            <a class="button button--secondary" href="<?= e(baseUrl('/perfil')) ?>" data-guide-profile><?= $navIcon('user', 'button__icon') ?><span>Meu Perfil</span></a>
            <form method="post" action="<?= e(baseUrl('/logout')) ?>">
                <?= csrfField() ?>
                <button class="button" type="submit"><?= $navIcon('log-out', 'button__icon') ?><span>Sair</span></button>
            </form>
            <img class="topbar__logo topbar__logo--uea" src="<?= e(assetUrl('assets/uea_logo_white.png')) ?>" alt="UEA">
        </div>
    </header>
    <nav class="admin-nav" id="admin-nav" data-admin-nav>
        <?php if ($profile === 'Desenvolvedor'): ?>
            <a href="<?= e(baseUrl($calendarItem[1])) ?>" data-guide-path="<?= e($calendarItem[1]) ?>"><?= $navIcon($iconForPath($calendarItem[1])) ?><span><?= e($calendarItem[0]) ?></span></a>
            <?php foreach ($developerGroups as $groupName => $groupItems): ?>
                <div class="nav-group">
                    <button class="nav-group__button" type="button"><?= $navIcon($iconForGroup($groupName)) ?><span><?= e($groupName) ?></span></button>
                    <div class="nav-group__menu">
                        <?php foreach ($groupItems as $item): ?>
                            <a href="<?= e(baseUrl($item[1])) ?>" data-guide-path="<?= e($item[1]) ?>"><?= $navIcon($iconForPath($item[1])) ?><span><?= e($item[0]) ?></span></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <?php foreach ($items as $item): ?>
                <a href="<?= e(baseUrl($item[1])) ?>" data-guide-path="<?= e($item[1]) ?>"><?= $navIcon($iconForPath($item[1])) ?><span><?= e($item[0]) ?></span></a>
            <?php endforeach; ?>
        <?php endif; ?>
    </nav>
    <main class="page-shell">
        <?= flash() ?>
        <?= $content ?>
    </main>
    <footer class="site-footer">© CTIC-CESIT. Todos os direitos reservados.</footer>
    <div class="guide-layer" data-guide-layer aria-hidden="true" hidden>
        <div class="guide-focus-ring guide-focus-ring--center" data-guide-ring></div>
        <section class="guide-popover guide-popover--center guide-catalog" data-guide-catalog role="dialog" aria-modal="true" aria-labelledby="guide-catalog-title" aria-describedby="guide-catalog-description" tabindex="-1">
            <header class="guide-catalog__header">
                <div class="guide-popover__eyebrow">
                    <span><span class="guide-status-dot"></span>Central de Ajuda</span>
                    <button class="guide-catalog__close" type="button" data-guide-close aria-label="Fechar ajuda">×</button>
                </div>
                <h2 id="guide-catalog-title">O que você quer aprender?</h2>
                <p id="guide-catalog-description">Escolha uma página para conhecer seus campos, informações e todas as ações disponíveis.</p>
                <div class="guide-catalog__meta">
                    <span class="guide-meta-chip"><?= e($guideProfile ?: 'Usuário') ?></span>
                    <span class="guide-meta-count" data-guide-topic-count><?= e(count($guideTopics)) ?> tutoriais disponíveis</span>
                </div>
            </header>
            <div class="guide-catalog__toolbar">
                <label class="guide-search">
                    <span class="guide-search__icon" aria-hidden="true">⌕</span>
                    <span class="sr-only">Buscar tutorial</span>
                    <input type="search" placeholder="Buscar página ou função..." autocomplete="off" data-guide-search>
                </label>
            </div>
            <div class="guide-topic-list" data-guide-topic-list></div>
            <div class="guide-empty" data-guide-empty hidden>
                <strong>Nenhum tutorial encontrado</strong>
                <span>Tente buscar por outra página ou ação.</span>
            </div>
            <footer class="guide-catalog__tip">Dica: ao abrir a Ajuda dentro de uma tela, o tutorial dessa página aparece em destaque.</footer>
        </section>
        <section class="guide-popover guide-popover--center" data-guide-popover role="dialog" aria-modal="true" aria-labelledby="guide-title" aria-describedby="guide-description" tabindex="-1" hidden>
            <div class="guide-popover__eyebrow">
                <span>Tutorial</span>
                <span data-guide-counter></span>
            </div>
            <div class="guide-progress" aria-hidden="true"><span data-guide-progress></span></div>
            <h2 id="guide-title" data-guide-title></h2>
            <p id="guide-description" data-guide-description></p>
            <div class="guide-popover__actions">
                <button class="button button--secondary" type="button" data-guide-skip>Encerrar</button>
                <button class="button button--secondary" type="button" data-guide-previous>Anterior</button>
                <button class="button" type="button" data-guide-next>Próximo</button>
            </div>
        </section>
    </div>
    <template data-guide-config><?= e($guideConfigJson ?: '{}') ?></template>
    <script src="<?= e(assetUrl('js/app.js')) ?>"></script>
    <script src="<?= e(assetUrl('js/guide.js')) ?>"></script>
</body>
</html>
