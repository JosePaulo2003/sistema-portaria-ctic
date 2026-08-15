<?php
declare(strict_types=1);

use App\Controllers\AdministrativoController;
use App\Controllers\AlunoBolsistaController;
use App\Controllers\AlunoController;
use App\Controllers\AuthController;
use App\Controllers\CoordenadorController;
use App\Controllers\DesenvolvedorController;
use App\Controllers\DiretorController;
use App\Controllers\FormularioUsuarioController;
use App\Controllers\MotoristaController;
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
$router->get('/login/portaria', [AuthController::class, 'portariaForm']);
$router->post('/login/portaria', [AuthController::class, 'loginPortaria']);
$router->get('/recuperar-senha', [AuthController::class, 'forgotForm']);
$router->post('/recuperar-senha', [AuthController::class, 'forgot']);
$router->post('/logout', [AuthController::class, 'logout']);
$router->post('/integracoes/google-form/usuarios', [FormularioUsuarioController::class, 'receber']);
$router->get('/perfil', [PerfilController::class, 'edit']);
$router->post('/perfil', [PerfilController::class, 'update']);
$router->get('/calendario-salas', [SalaController::class, 'calendario']);
$router->get('/salas/detalhes', [SalaController::class, 'detalhes']);
$router->get('/salas/atividade', [SalaController::class, 'atividade']);

$router->get('/desenvolvedor', [DesenvolvedorController::class, 'index']);
$router->get('/desenvolvedor/usuarios', [UsuarioController::class, 'index']);
$router->get('/desenvolvedor/usuarios/solicitacoes', [UsuarioController::class, 'solicitacoes']);
$router->post('/desenvolvedor/usuarios/solicitacoes/aprovar', [UsuarioController::class, 'aprovarSolicitacao']);
$router->post('/desenvolvedor/usuarios/solicitacoes/recusar', [UsuarioController::class, 'recusarSolicitacao']);
$router->post('/desenvolvedor/usuarios/solicitacoes/limpar-analisadas', [UsuarioController::class, 'limparSolicitacoesAnalisadas']);
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
$router->get('/desenvolvedor/vinculos-bolsistas', [PortariaController::class, 'vinculosBolsistas']);
$router->post('/desenvolvedor/vinculos-bolsistas', [PortariaController::class, 'salvarVinculoBolsista']);

$router->get('/diretor', [DiretorController::class, 'index']);
$router->get('/diretor/chaves', [DiretorController::class, 'chaves']);
$router->post('/diretor/chaves/retirar', [ProfessorController::class, 'retirarChave']);
$router->get('/diretor/reservas', [DiretorController::class, 'reservas']);
$router->post('/diretor/reservas/status', [DiretorController::class, 'atualizarReservaStatus']);
$router->get('/diretor/movimentacoes', [DiretorController::class, 'movimentacoes']);
$router->get('/diretor/relatorios', [DiretorController::class, 'relatorios']);
$router->get('/diretor/relatorios/exportar', [DiretorController::class, 'exportarRelatorio']);
$router->get('/diretor/disponibilidade', [DiretorController::class, 'disponibilidade']);

$router->get('/coordenador', [CoordenadorController::class, 'index']);
$router->get('/coordenador/materias', [CoordenadorController::class, 'materias']);
$router->post('/coordenador/materias', [CoordenadorController::class, 'salvarMateria']);
$router->post('/coordenador/materias/disciplina/atualizar', [CoordenadorController::class, 'atualizarDisciplina']);
$router->post('/coordenador/materias/disciplina/excluir', [CoordenadorController::class, 'excluirDisciplina']);
$router->get('/coordenador/reservas-aulas', [CoordenadorController::class, 'reservasAulas']);
$router->post('/coordenador/reservas-aulas', [CoordenadorController::class, 'salvarReservaAula']);
$router->post('/coordenador/reservas-aulas/atualizar', [CoordenadorController::class, 'atualizarReservaAula']);
$router->post('/coordenador/reservas-aulas/excluir', [CoordenadorController::class, 'excluirReservaAula']);
$router->get('/coordenador/chaves-autorizadas', [CoordenadorController::class, 'chavesAutorizadas']);

$router->get('/administrativo', [AdministrativoController::class, 'index']);
$router->get('/administrativo/reservas-salas', [AdministrativoController::class, 'reservasSalas']);
$router->post('/administrativo/reservas-salas', [AdministrativoController::class, 'salvarReservaSala']);
$router->get('/administrativo/retiradas', [AdministrativoController::class, 'retiradas']);
$router->post('/administrativo/retiradas/chave', [ProfessorController::class, 'retirarChave']);
$router->post('/administrativo/retiradas/item', [ProfessorController::class, 'retirarItem']);
$router->get('/administrativo/disponibilidade-salas', [AdministrativoController::class, 'disponibilidadeSalas']);
$router->get('/administrativo/chaves-autorizadas', [AdministrativoController::class, 'chavesAutorizadas']);
$router->post('/administrativo/chaves-autorizadas', [AdministrativoController::class, 'salvarChaveAutorizada']);
$router->post('/administrativo/chaves-autorizadas/atualizar', [AdministrativoController::class, 'atualizarChaveAutorizada']);
$router->post('/administrativo/chaves-autorizadas/revogar', [AdministrativoController::class, 'excluirChaveAutorizada']);

$router->get('/secretario', [SecretarioController::class, 'index']);
$router->get('/secretario/cursos', [SecretarioController::class, 'cursos']);
$router->post('/secretario/cursos', [SecretarioController::class, 'salvarCurso']);
$router->post('/secretario/cursos/atualizar', [SecretarioController::class, 'atualizarCurso']);
$router->post('/secretario/cursos/excluir', [SecretarioController::class, 'excluirCurso']);
$router->get('/secretario/periodos-academicos', [SecretarioController::class, 'periodos']);
$router->post('/secretario/periodos-academicos', [SecretarioController::class, 'salvarPeriodo']);
$router->post('/secretario/periodos-academicos/atualizar', [SecretarioController::class, 'atualizarPeriodo']);
$router->post('/secretario/periodos-academicos/status', [SecretarioController::class, 'atualizarPeriodo']);
$router->post('/secretario/periodos-academicos/excluir', [SecretarioController::class, 'excluirPeriodo']);
$router->get('/secretario/reservas-curso', [SecretarioController::class, 'reservasCurso']);
$router->post('/secretario/reservas-curso', [SecretarioController::class, 'salvarReservaCurso']);
$router->get('/secretario/disponibilidade-salas', [SecretarioController::class, 'disponibilidade']);
$router->get('/secretario/retirada-chaves', [SecretarioController::class, 'retiradaChaves']);
$router->post('/secretario/retirada-chaves/retirar', [ProfessorController::class, 'retirarChave']);
$router->post('/secretario/retirada-chaves/retirar-item', [ProfessorController::class, 'retirarItem']);

$router->get('/portaria', [PortariaController::class, 'index']);
$router->get('/portaria/salas', [PortariaController::class, 'salas']);
$router->post('/portaria/salas', [PortariaController::class, 'salvarSala']);
$router->post('/portaria/salas/atualizar', [PortariaController::class, 'atualizarSala']);
$router->post('/portaria/salas/status', [PortariaController::class, 'atualizarSala']);
$router->post('/portaria/salas/excluir', [PortariaController::class, 'excluirSala']);
$router->get('/portaria/itens', [PortariaController::class, 'itens']);
$router->post('/portaria/itens', [PortariaController::class, 'salvarItem']);
$router->post('/portaria/itens/atualizar', [PortariaController::class, 'atualizarItem']);
$router->post('/portaria/itens/status', [PortariaController::class, 'atualizarItem']);
$router->post('/portaria/itens/excluir', [PortariaController::class, 'excluirItem']);
$router->get('/portaria/retiradas', [PortariaController::class, 'retiradas']);
$router->post('/portaria/retiradas/registrar-chave', [PortariaController::class, 'registrarRetiradaChave']);
$router->post('/portaria/retiradas/devolver-chave', [PortariaController::class, 'devolverChave']);
$router->post('/portaria/retiradas/devolver-item', [PortariaController::class, 'devolverItem']);
$router->get('/portaria/reservas', [PortariaController::class, 'reservas']);
$router->post('/portaria/reservas', [PortariaController::class, 'salvarReserva']);
$router->post('/portaria/reservas/atualizar', [PortariaController::class, 'atualizarReserva']);
$router->post('/portaria/reservas/excluir-historico', [PortariaController::class, 'excluirReservaHistorico']);
$router->get('/portaria/permissoes', [PortariaController::class, 'permissoes']);
$router->post('/portaria/permissoes/chaves', [PortariaController::class, 'salvarPermissaoChave']);
$router->post('/portaria/permissoes/chaves/atualizar', [PortariaController::class, 'atualizarPermissaoChave']);
$router->post('/portaria/permissoes/chaves/revogar', [PortariaController::class, 'revogarPermissaoChave']);
$router->post('/portaria/permissoes/chaves/limpar-revogadas', [PortariaController::class, 'limparPermissoesRevogadas']);
$router->get('/portaria/vinculos-bolsistas', [PortariaController::class, 'vinculosBolsistas']);
$router->post('/portaria/vinculos-bolsistas', [PortariaController::class, 'salvarVinculoBolsista']);
$router->get('/portaria/visitantes', [PortariaController::class, 'visitantes']);
$router->post('/portaria/visitantes', [PortariaController::class, 'salvarVisitante']);
$router->post('/portaria/visitantes/atualizar', [PortariaController::class, 'atualizarVisitante']);
$router->post('/portaria/visitantes/excluir', [PortariaController::class, 'excluirVisitante']);
$router->get('/portaria/salas-hoje', [PortariaController::class, 'salasHoje']);
$router->get('/portaria/historico', [PortariaController::class, 'historico']);
$router->get('/portaria/relatorio-movimentacoes', [PortariaController::class, 'relatorioMovimentacoes']);

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
$router->post('/professor/orientandos-bolsistas/liberar-chave', [ProfessorController::class, 'liberarChaveOrientando']);
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
$router->get('/retiradas-autorizadas', [AlunoController::class, 'retiradasAutorizadas']);
$router->post('/retiradas-autorizadas/chave', [AlunoController::class, 'retirarChaveAutorizada']);

$router->get('/visitante', [VisitanteController::class, 'index']);
$router->get('/visitante/chave', [VisitanteController::class, 'chave']);
$router->post('/visitante/chave/retirar', [ProfessorController::class, 'retirarChave']);

$router->get('/motorista', [MotoristaController::class, 'index']);
$router->get('/motorista/retiradas', [MotoristaController::class, 'retiradas']);
$router->post('/motorista/retiradas/chave', [ProfessorController::class, 'retirarChave']);

$router->get('/servicos-gerais', [ServicosGeraisController::class, 'index']);
$router->get('/servicos-gerais/retiradas', [ServicosGeraisController::class, 'retiradas']);
$router->post('/servicos-gerais/retiradas/chave', [ProfessorController::class, 'retirarChave']);
$router->post('/servicos-gerais/retiradas/item', [ProfessorController::class, 'retirarItem']);

return $router;
