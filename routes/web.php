<?php
declare(strict_types=1);

use App\Controllers\AdministrativoController;
use App\Controllers\AlunoBolsistaController;
use App\Controllers\AlunoController;
use App\Controllers\AuthController;
use App\Controllers\DesenvolvedorController;
use App\Controllers\PerfilController;
use App\Controllers\PortariaController;
use App\Controllers\ProfessorController;
use App\Controllers\SecretarioController;
use App\Controllers\ServicosGeraisController;
use App\Controllers\SalaController;
use App\Controllers\UsuarioController;
use App\Controllers\VisitanteController;
use App\Core\Router;

// Mapa central de rotas; cada endereço aponta para a controller responsável.
$router = new Router();

$router->get('/', fn () => currentUser() ? redirect(moduleForProfile(userProfile() ?? '')) : redirect('/login'));
$router->get('/login', [AuthController::class, 'loginForm']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);
$router->get('/perfil', [PerfilController::class, 'edit']);
$router->post('/perfil', [PerfilController::class, 'update']);
$router->get('/salas/detalhes', [SalaController::class, 'detalhes']);

$router->get('/desenvolvedor', [DesenvolvedorController::class, 'index']);
$router->get('/desenvolvedor/usuarios', [UsuarioController::class, 'index']);
$router->get('/usuarios/cadastro', [UsuarioController::class, 'create']);
$router->post('/usuarios/cadastro', [UsuarioController::class, 'store']);
$router->get('/usuarios/editar', [UsuarioController::class, 'edit']);
$router->post('/usuarios/editar', [UsuarioController::class, 'update']);
$router->post('/usuarios/excluir', [UsuarioController::class, 'destroy']);
$router->get('/desenvolvedor/logs', [DesenvolvedorController::class, 'logs']);
$router->post('/desenvolvedor/logs/limpar', [DesenvolvedorController::class, 'limparLogs']);
$router->get('/desenvolvedor/advertencias', [DesenvolvedorController::class, 'advertencias']);
$router->post('/desenvolvedor/advertencias', [DesenvolvedorController::class, 'salvarAdvertencias']);
$router->post('/desenvolvedor/advertencias/limpar', [DesenvolvedorController::class, 'limparAdvertencias']);
$router->post('/desenvolvedor/bloqueios/atualizar', [DesenvolvedorController::class, 'atualizarBloqueio']);
$router->post('/desenvolvedor/bloqueios/excluir', [DesenvolvedorController::class, 'excluirBloqueio']);

$router->get('/administrativo', [AdministrativoController::class, 'index']);
$router->get('/administrativo/reservas-salas', [AdministrativoController::class, 'reservasSalas']);
$router->get('/administrativo/emprestimos-portaria', [AdministrativoController::class, 'emprestimosPortaria']);
$router->get('/administrativo/retiradas', [AdministrativoController::class, 'retiradas']);
$router->get('/administrativo/historico', [AdministrativoController::class, 'historico']);
$router->get('/administrativo/disponibilidade-salas', [AdministrativoController::class, 'disponibilidadeSalas']);

$router->get('/secretario', [SecretarioController::class, 'index']);
$router->get('/secretario/periodos-academicos', [SecretarioController::class, 'periodos']);
$router->post('/secretario/periodos-academicos', [SecretarioController::class, 'salvarPeriodo']);
$router->post('/secretario/periodos-academicos/atualizar', [SecretarioController::class, 'atualizarPeriodo']);
$router->post('/secretario/periodos-academicos/status', [SecretarioController::class, 'atualizarPeriodo']);
$router->post('/secretario/periodos-academicos/excluir', [SecretarioController::class, 'excluirPeriodo']);
$router->get('/secretario/materias', [SecretarioController::class, 'materias']);
$router->post('/secretario/materias', [SecretarioController::class, 'salvarMateria']);
$router->post('/secretario/materias/curso/atualizar', [SecretarioController::class, 'atualizarCurso']);
$router->post('/secretario/materias/curso/excluir', [SecretarioController::class, 'excluirCurso']);
$router->post('/secretario/materias/disciplina/atualizar', [SecretarioController::class, 'atualizarDisciplina']);
$router->post('/secretario/materias/disciplina/excluir', [SecretarioController::class, 'excluirDisciplina']);
$router->get('/secretario/bolsistas', [SecretarioController::class, 'bolsistas']);
$router->post('/secretario/bolsistas', [SecretarioController::class, 'salvarBolsista']);
$router->post('/secretario/bolsistas/aprovar', [SecretarioController::class, 'atualizarBolsista']);
$router->post('/secretario/bolsistas/atualizar', [SecretarioController::class, 'atualizarBolsista']);
$router->post('/secretario/bolsistas/status', [SecretarioController::class, 'atualizarBolsista']);
$router->post('/secretario/bolsistas/excluir', [SecretarioController::class, 'excluirBolsista']);
$router->get('/secretario/reservas-aulas', [SecretarioController::class, 'reservasAulas']);
$router->post('/secretario/reservas-aulas', [SecretarioController::class, 'salvarReservaAula']);
$router->post('/secretario/reservas-aulas/atualizar', [SecretarioController::class, 'atualizarReservaAula']);
$router->post('/secretario/reservas-aulas/status', [SecretarioController::class, 'atualizarReservaAula']);
$router->post('/secretario/reservas-aulas/excluir', [SecretarioController::class, 'excluirReservaAula']);
$router->get('/secretario/reservas-curso', [SecretarioController::class, 'reservasCurso']);
$router->get('/secretario/disponibilidade-salas', [SecretarioController::class, 'disponibilidade']);
$router->get('/secretario/salas', [SecretarioController::class, 'salas']);
$router->post('/secretario/salas', [SecretarioController::class, 'salvarSala']);
$router->post('/secretario/salas/atualizar', [SecretarioController::class, 'atualizarSala']);
$router->post('/secretario/salas/status', [SecretarioController::class, 'atualizarSala']);
$router->post('/secretario/salas/excluir', [SecretarioController::class, 'excluirSala']);
$router->get('/secretario/chaves-autorizadas', [SecretarioController::class, 'chavesAutorizadas']);
$router->post('/secretario/chaves-autorizadas', [SecretarioController::class, 'salvarChaveAutorizada']);
$router->post('/secretario/chaves-autorizadas/atualizar', [SecretarioController::class, 'atualizarChaveAutorizada']);
$router->post('/secretario/chaves-autorizadas/revogar', [SecretarioController::class, 'excluirChaveAutorizada']);
$router->get('/secretario/retirada-chaves', [SecretarioController::class, 'retiradaChaves']);
$router->post('/secretario/retirada-chaves/retirar', [ProfessorController::class, 'retirarChave']);
$router->post('/secretario/retirada-chaves/retirar-item', [ProfessorController::class, 'retirarItem']);
$router->get('/secretario/itens', [SecretarioController::class, 'itens']);
$router->post('/secretario/itens', [SecretarioController::class, 'salvarItem']);
$router->post('/secretario/itens/atualizar', [SecretarioController::class, 'atualizarItem']);
$router->post('/secretario/itens/status', [SecretarioController::class, 'atualizarItem']);
$router->post('/secretario/itens/excluir', [SecretarioController::class, 'excluirItem']);

$router->get('/portaria', [PortariaController::class, 'index']);
$router->get('/portaria/retiradas', [PortariaController::class, 'retiradas']);
$router->post('/portaria/retiradas/devolver-chave', [PortariaController::class, 'devolverChave']);
$router->post('/portaria/retiradas/devolver-item', [PortariaController::class, 'devolverItem']);
$router->get('/portaria/permissoes', [PortariaController::class, 'permissoes']);
$router->get('/portaria/visitantes', [PortariaController::class, 'visitantes']);
$router->post('/portaria/visitantes', [PortariaController::class, 'salvarVisitante']);
$router->post('/portaria/visitantes/atualizar', [PortariaController::class, 'atualizarVisitante']);
$router->post('/portaria/visitantes/excluir', [PortariaController::class, 'excluirVisitante']);
$router->get('/portaria/salas-hoje', [PortariaController::class, 'salasHoje']);
$router->get('/portaria/historico', [PortariaController::class, 'historico']);

$router->get('/professor', [ProfessorController::class, 'index']);
$router->get('/professor/disponibilidade-salas', [ProfessorController::class, 'disponibilidadeSalas']);
$router->get('/professor/reservas-salas', [ProfessorController::class, 'reservasSalas']);
$router->post('/professor/reservas-salas', [ProfessorController::class, 'salvarReservaSala']);
$router->post('/professor/reservas-salas/atualizar', [ProfessorController::class, 'atualizarReservaSala']);
$router->post('/professor/reservas-salas/excluir', [ProfessorController::class, 'excluirReservaSala']);
$router->get('/professor/aulas-semestre', [ProfessorController::class, 'aulasSemestre']);
$router->get('/professor/orientandos-bolsistas', [ProfessorController::class, 'orientandosBolsistas']);
$router->post('/professor/orientandos-bolsistas', [ProfessorController::class, 'salvarOrientando']);
$router->post('/professor/orientandos-bolsistas/atualizar', [ProfessorController::class, 'atualizarOrientando']);
$router->post('/professor/orientandos-bolsistas/excluir', [ProfessorController::class, 'excluirOrientando']);
$router->post('/professor/orientandos-bolsistas/liberar-chave', [SecretarioController::class, 'salvarChaveAutorizada']);
$router->get('/professor/retiradas', [ProfessorController::class, 'retiradas']);
$router->post('/professor/retiradas/chave', [ProfessorController::class, 'retirarChave']);
$router->post('/professor/retiradas/item', [ProfessorController::class, 'retirarItem']);

$router->get('/bolsista', [AlunoBolsistaController::class, 'index']);
$router->get('/bolsista/sala-pesquisa', [AlunoBolsistaController::class, 'salaPesquisa']);
$router->get('/bolsista/retiradas', [AlunoBolsistaController::class, 'retiradas']);
$router->post('/bolsista/retiradas/chave', [ProfessorController::class, 'retirarChave']);
$router->post('/bolsista/retiradas/item', [ProfessorController::class, 'retirarItem']);

$router->get('/aluno', [AlunoController::class, 'index']);
$router->get('/aluno/consulta-salas', [AlunoController::class, 'consultaSalas']);

$router->get('/visitante', [VisitanteController::class, 'index']);
$router->get('/visitante/chave', [VisitanteController::class, 'chave']);
$router->post('/visitante/chave/retirar', [ProfessorController::class, 'retirarChave']);

$router->get('/servicos-gerais', [ServicosGeraisController::class, 'index']);
$router->get('/servicos-gerais/retiradas', [ServicosGeraisController::class, 'retiradas']);
$router->post('/servicos-gerais/retiradas/chave', [ProfessorController::class, 'retirarChave']);
$router->post('/servicos-gerais/retiradas/item', [ProfessorController::class, 'retirarItem']);

return $router;
