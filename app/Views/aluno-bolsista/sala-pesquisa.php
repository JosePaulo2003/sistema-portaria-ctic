<section class="section-header"><h1>Sala de Pesquisa</h1></section>
<article class="card"><h2><?= e($user['nome']) ?></h2><p><strong>Projeto:</strong> <?= e($user['projeto_pesquisa'] ?? 'Não informado') ?></p><p><strong>Situação:</strong> <?= e($user['situacao']) ?></p></article>
