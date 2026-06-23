CREATE DATABASE IF NOT EXISTS sgrp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sgrp;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS bloqueios_chaves;
DROP TABLE IF EXISTS advertencias_chaves;
DROP TABLE IF EXISTS notificacoes_portaria;
DROP TABLE IF EXISTS logs_sistema;
DROP TABLE IF EXISTS logs_auditoria;
DROP TABLE IF EXISTS solicitacoes_usuarios;
DROP TABLE IF EXISTS movimentacoes;
DROP TABLE IF EXISTS permissoes_itens;
DROP TABLE IF EXISTS permissoes_salas;
DROP TABLE IF EXISTS recursos_curso;
DROP TABLE IF EXISTS itens_portaria;
DROP TABLE IF EXISTS reservas_aula;
DROP TABLE IF EXISTS reservas;
DROP TABLE IF EXISTS disciplinas;
DROP TABLE IF EXISTS cursos;
DROP TABLE IF EXISTS periodos_academicos;
DROP TABLE IF EXISTS salas;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS perfis;
DROP TABLE IF EXISTS configuracoes_sistema;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE perfis (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL UNIQUE,
  nivel INT NOT NULL DEFAULT 1,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(150) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  senha_hash VARCHAR(255) NOT NULL,
  perfil_id INT NOT NULL,
  situacao ENUM('ativo','pendente','inativo','bloqueado') NOT NULL DEFAULT 'pendente',
  foto_perfil_url VARCHAR(255) NULL,
  professor_indicador_id INT NULL,
  projeto_pesquisa VARCHAR(255) NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  ultimo_login_em DATETIME NULL,
  CONSTRAINT fk_usuarios_perfil FOREIGN KEY (perfil_id) REFERENCES perfis(id),
  CONSTRAINT fk_usuarios_professor FOREIGN KEY (professor_indicador_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE solicitacoes_usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(150) NOT NULL,
  email VARCHAR(190) NOT NULL,
  perfil_solicitado VARCHAR(100) NOT NULL,
  telefone VARCHAR(40) NULL,
  matricula VARCHAR(80) NULL,
  observacao TEXT NULL,
  origem VARCHAR(80) NOT NULL DEFAULT 'google_forms',
  payload_json JSON NULL,
  situacao ENUM('pendente','aprovada','recusada') NOT NULL DEFAULT 'pendente',
  aprovado_por INT NULL,
  aprovado_em DATETIME NULL,
  usuario_id INT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_sol_usuario_aprovador FOREIGN KEY (aprovado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
  CONSTRAINT fk_sol_usuario_criado FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE salas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(150) NOT NULL,
  codigo VARCHAR(50) NULL,
  bloco VARCHAR(80) NULL,
  descricao TEXT NULL,
  capacidade INT NULL,
  tipo_ambiente ENUM('laboratorio','institucional','administrativo','setor') NOT NULL,
  situacao ENUM('disponivel','manutencao','bloqueada') NOT NULL DEFAULT 'disponivel',
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE periodos_academicos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  data_inicio DATE NOT NULL,
  data_fim DATE NOT NULL,
  situacao ENUM('ativo','inativo','encerrado') NOT NULL DEFAULT 'ativo',
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cursos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(150) NOT NULL,
  codigo VARCHAR(50) NULL,
  situacao ENUM('ativo','inativo') NOT NULL DEFAULT 'ativo',
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE disciplinas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  curso_id INT NOT NULL,
  nome VARCHAR(150) NOT NULL,
  periodo_referencia VARCHAR(50) NOT NULL,
  professor_id INT NULL,
  observacao TEXT NULL,
  situacao ENUM('ativa','inativa') NOT NULL DEFAULT 'ativa',
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_disciplinas_curso FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
  CONSTRAINT fk_disciplinas_professor FOREIGN KEY (professor_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE reservas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  sala_id INT NULL,
  acesso_total TINYINT(1) NOT NULL DEFAULT 0,
  periodo_academico_id INT NULL,
  titulo VARCHAR(180) NOT NULL,
  finalidade TEXT NULL,
  tipo_reserva VARCHAR(80) NOT NULL DEFAULT 'sala',
  inicio_em DATETIME NOT NULL,
  fim_em DATETIME NOT NULL,
  situacao ENUM('pendente','confirmada','cancelada','encerrada') NOT NULL DEFAULT 'pendente',
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_reservas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
  CONSTRAINT fk_reservas_sala FOREIGN KEY (sala_id) REFERENCES salas(id),
  CONSTRAINT fk_reservas_periodo FOREIGN KEY (periodo_academico_id) REFERENCES periodos_academicos(id) ON DELETE SET NULL,
  INDEX idx_reservas_periodo (inicio_em, fim_em, situacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE reservas_aula (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  professor_id INT NOT NULL,
  disciplina_id INT NOT NULL,
  aluno_bolsista_id INT NULL,
  periodo_academico VARCHAR(80) NOT NULL,
  sala_nome VARCHAR(150) NOT NULL,
  turma VARCHAR(80) NOT NULL,
  dia_semana VARCHAR(30) NOT NULL,
  horario_inicio TIME NOT NULL,
  horario_fim TIME NOT NULL,
  disciplina VARCHAR(150) NOT NULL,
  observacao TEXT NULL,
  situacao ENUM('ativa','inativa','cancelada') NOT NULL DEFAULT 'ativa',
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_reservas_aula_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
  CONSTRAINT fk_reservas_aula_professor FOREIGN KEY (professor_id) REFERENCES usuarios(id),
  CONSTRAINT fk_reservas_aula_disciplina FOREIGN KEY (disciplina_id) REFERENCES disciplinas(id),
  CONSTRAINT fk_reservas_aula_bolsista FOREIGN KEY (aluno_bolsista_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE itens_portaria (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(150) NOT NULL,
  codigo VARCHAR(50) NULL,
  categoria VARCHAR(80) NULL,
  descricao TEXT NULL,
  quantidade INT NOT NULL DEFAULT 1,
  situacao ENUM('disponivel','indisponivel','manutencao') NOT NULL DEFAULT 'disponivel',
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE recursos_curso (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  nome VARCHAR(150) NOT NULL,
  tipo_recurso VARCHAR(80) NOT NULL,
  categoria VARCHAR(80) NULL,
  descricao TEXT NULL,
  situacao ENUM('ativo','inativo') NOT NULL DEFAULT 'ativo',
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_recursos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE permissoes_salas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  sala_id INT NOT NULL,
  autorizado_por INT NOT NULL,
  inicio_autorizacao DATETIME NULL,
  expira_em DATETIME NULL,
  dias_semana VARCHAR(120) NULL,
  observacao TEXT NULL,
  situacao ENUM('ativa','revogada','expirada') NOT NULL DEFAULT 'ativa',
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_permissoes_salas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
  CONSTRAINT fk_permissoes_salas_sala FOREIGN KEY (sala_id) REFERENCES salas(id),
  CONSTRAINT fk_permissoes_salas_autorizador FOREIGN KEY (autorizado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE permissoes_itens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  item_portaria_id INT NULL,
  recurso_nome VARCHAR(150) NULL,
  autorizado_por INT NOT NULL,
  inicio_autorizacao DATETIME NULL,
  expira_em DATETIME NULL,
  observacao TEXT NULL,
  situacao ENUM('ativa','revogada','expirada') NOT NULL DEFAULT 'ativa',
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_permissoes_itens_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
  CONSTRAINT fk_permissoes_itens_item FOREIGN KEY (item_portaria_id) REFERENCES itens_portaria(id) ON DELETE SET NULL,
  CONSTRAINT fk_permissoes_itens_autorizador FOREIGN KEY (autorizado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE movimentacoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  sala_id INT NULL,
  item_portaria_id INT NULL,
  tipo_movimentacao ENUM('retirada_chave','devolucao_chave','retirada_item','devolucao_item','retirada_recurso','devolucao_recurso') NOT NULL,
  situacao ENUM('aberta','finalizada','cancelada') NOT NULL DEFAULT 'aberta',
  retirada_em DATETIME NULL,
  devolucao_prevista_em DATETIME NULL,
  devolucao_real_em DATETIME NULL,
  devolvido_por_usuario_id INT NULL,
  registrado_por_usuario_id INT NULL,
  observacao TEXT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_mov_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
  CONSTRAINT fk_mov_sala FOREIGN KEY (sala_id) REFERENCES salas(id) ON DELETE SET NULL,
  CONSTRAINT fk_mov_item FOREIGN KEY (item_portaria_id) REFERENCES itens_portaria(id) ON DELETE SET NULL,
  CONSTRAINT fk_mov_devolvido_por FOREIGN KEY (devolvido_por_usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
  CONSTRAINT fk_mov_registrado_por FOREIGN KEY (registrado_por_usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE logs_auditoria (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NULL,
  modulo VARCHAR(80) NOT NULL,
  acao VARCHAR(80) NOT NULL,
  descricao TEXT NOT NULL,
  contexto_json JSON NULL,
  ip_origem VARCHAR(80) NULL,
  user_agent VARCHAR(255) NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_logs_aud_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE logs_sistema (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NULL,
  nivel VARCHAR(30) NOT NULL,
  origem VARCHAR(100) NOT NULL,
  mensagem TEXT NOT NULL,
  contexto_json JSON NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_logs_sis_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notificacoes_portaria (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tipo VARCHAR(80) NOT NULL,
  mensagem TEXT NOT NULL,
  contexto_json JSON NULL,
  lida_em DATETIME NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE advertencias_chaves (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  movimentacao_id INT NULL,
  agente_portaria_id INT NOT NULL,
  motivo VARCHAR(255) NOT NULL,
  observacao TEXT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_adv_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
  CONSTRAINT fk_adv_mov FOREIGN KEY (movimentacao_id) REFERENCES movimentacoes(id) ON DELETE SET NULL,
  CONSTRAINT fk_adv_agente FOREIGN KEY (agente_portaria_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE bloqueios_chaves (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  advertencia_id INT NULL,
  inicio_em DATETIME NOT NULL,
  fim_em DATETIME NOT NULL,
  situacao ENUM('ativo','encerrado','cancelado') NOT NULL DEFAULT 'ativo',
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_bloq_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
  CONSTRAINT fk_bloq_adv FOREIGN KEY (advertencia_id) REFERENCES advertencias_chaves(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE configuracoes_sistema (
  id INT AUTO_INCREMENT PRIMARY KEY,
  chave VARCHAR(120) NOT NULL UNIQUE,
  valor TEXT NOT NULL,
  descricao TEXT NULL,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
