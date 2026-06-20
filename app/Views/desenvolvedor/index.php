<section class="section-header"><h1>Painel Técnico</h1><p>Acesso máximo: o Desenvolvedor pode acessar, criar, editar e operar todos os módulos.</p></section>
<div class="dashboard-grid">
    <a class="card card-link" href="<?= e(baseUrl('/desenvolvedor/usuarios')) ?>"><h2>Usuários</h2><p>Gerenciar contas, perfis e situação.</p></a>
    <a class="card card-link" href="<?= e(baseUrl('/desenvolvedor/logs')) ?>"><h2>Logs</h2><p>Auditoria e eventos de sistema.</p></a>
    <a class="card card-link" href="<?= e(baseUrl('/desenvolvedor/advertencias')) ?>"><h2>Advertências</h2><p>Bloqueios e configuração de prazo.</p></a>
    <a class="card card-link" href="<?= e(baseUrl('/secretario/salas')) ?>"><h2>Salas</h2><p>Criar e manter ambientes.</p></a>
    <a class="card card-link" href="<?= e(baseUrl('/secretario/itens')) ?>"><h2>Itens</h2><p>Criar itens de portaria.</p></a>
    <a class="card card-link" href="<?= e(baseUrl('/portaria/visitantes')) ?>"><h2>Visitantes</h2><p>Criar acessos temporários.</p></a>
    <a class="card card-link" href="<?= e(baseUrl('/professor/reservas-salas')) ?>"><h2>Reservas</h2><p>Criar reservas de sala.</p></a>
    <a class="card card-link" href="<?= e(baseUrl('/professor/retiradas')) ?>"><h2>Retiradas</h2><p>Registrar retirada de chave ou item.</p></a>
</div>
