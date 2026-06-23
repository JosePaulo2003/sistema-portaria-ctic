USE sgrp;

INSERT INTO perfis (nome, nivel) VALUES
('Desenvolvedor', 100),
('Administrativo', 80),
('Diretor', 85),
('Secretário de Curso', 70),
('Agente de Portaria', 60),
('Professor', 50),
('Aluno Bolsista', 40),
('Aluno', 20),
('Visitante', 10),
('Serviços Gerais', 30);

SET @senha = '$2y$10$aqKk/G3aji8m9SP7AC1C4OSvtyDN9s1S3CrwlKIaScqF1UD1q5Tg2';

INSERT INTO usuarios (nome, email, senha_hash, perfil_id, situacao)
SELECT 'Desenvolvedor Demo', 'desenvolvedor@sgrp.local', @senha, id, 'ativo' FROM perfis WHERE nome = 'Desenvolvedor';
INSERT INTO usuarios (nome, email, senha_hash, perfil_id, situacao)
SELECT 'Administrativo Demo', 'administrativo@sgrp.local', @senha, id, 'ativo' FROM perfis WHERE nome = 'Administrativo';
INSERT INTO usuarios (nome, email, senha_hash, perfil_id, situacao)
SELECT 'Diretor Demo', 'diretor@sgrp.local', @senha, id, 'ativo' FROM perfis WHERE nome = 'Diretor';
INSERT INTO usuarios (nome, email, senha_hash, perfil_id, situacao)
SELECT 'Secretário Demo', 'secretario@sgrp.local', @senha, id, 'ativo' FROM perfis WHERE nome = 'Secretário de Curso';
INSERT INTO usuarios (nome, email, senha_hash, perfil_id, situacao)
SELECT 'Portaria Demo', 'portaria@sgrp.local', @senha, id, 'ativo' FROM perfis WHERE nome = 'Agente de Portaria';
INSERT INTO usuarios (nome, email, senha_hash, perfil_id, situacao)
SELECT 'Professor Demo', 'professor@sgrp.local', @senha, id, 'ativo' FROM perfis WHERE nome = 'Professor';
INSERT INTO usuarios (nome, email, senha_hash, perfil_id, situacao, professor_indicador_id, projeto_pesquisa)
SELECT 'Bolsista Demo', 'bolsista@sgrp.local', @senha, p.id, 'ativo', u.id, 'Projeto de Pesquisa Aplicada'
FROM perfis p JOIN usuarios u ON u.email = 'professor@sgrp.local' WHERE p.nome = 'Aluno Bolsista';
INSERT INTO usuarios (nome, email, senha_hash, perfil_id, situacao)
SELECT 'Aluno Demo', 'aluno@sgrp.local', @senha, id, 'ativo' FROM perfis WHERE nome = 'Aluno';
INSERT INTO usuarios (nome, email, senha_hash, perfil_id, situacao)
SELECT 'Visitante Demo', 'visitante@sgrp.local', @senha, id, 'ativo' FROM perfis WHERE nome = 'Visitante';
INSERT INTO usuarios (nome, email, senha_hash, perfil_id, situacao)
SELECT 'Serviços Gerais Demo', 'servicos@sgrp.local', @senha, id, 'ativo' FROM perfis WHERE nome = 'Serviços Gerais';

INSERT INTO salas (nome, codigo, tipo_ambiente, situacao, capacidade) VALUES
('Sala 01', 'S01', 'institucional', 'disponivel', 35),
('Sala 02', 'S02', 'institucional', 'disponivel', 35),
('Sala 03', 'S03', 'institucional', 'disponivel', 35),
('Sala 04', 'S04', 'institucional', 'disponivel', 35),
('Sala 05', 'S05', 'institucional', 'disponivel', 35),
('Sala 06', 'S06', 'institucional', 'disponivel', 35),
('Sala 07', 'S07', 'institucional', 'disponivel', 35),
('Sala 08', 'S08', 'institucional', 'disponivel', 35),
('Biblioteca', 'BIB', 'institucional', 'disponivel', 60),
('Almoxarifado', 'ALM', 'setor', 'disponivel', NULL),
('Coordenação', 'COORD', 'administrativo', 'disponivel', NULL),
('Diretoria', 'DIR', 'administrativo', 'disponivel', NULL),
('Secretaria Acadêmica', 'SEC-AC', 'administrativo', 'disponivel', NULL),
('Secretaria Administrativa', 'SEC-AD', 'administrativo', 'disponivel', NULL),
('Laboratório de Informática 1', 'LAB-INF-1', 'laboratorio', 'disponivel', 30),
('Laboratório de Informática 2', 'LAB-INF-2', 'laboratorio', 'disponivel', 30),
('Laboratório de Biologia', 'LAB-BIO', 'laboratorio', 'disponivel', 25),
('Laboratório Maker', 'LAB-MAKER', 'laboratorio', 'disponivel', 20),
('Laboratório de Mídias', 'LAB-MID', 'laboratorio', 'disponivel', 20),
('Laboratório de Designer', 'LAB-DES', 'laboratorio', 'disponivel', 20),
('Herbário', 'HERB', 'laboratorio', 'disponivel', 15),
('CTIC', 'CTIC', 'setor', 'disponivel', NULL),
('Lab-OPC', 'LAB-OPC', 'laboratorio', 'disponivel', 20);

INSERT INTO itens_portaria (nome, codigo, categoria, quantidade, situacao) VALUES
('Projetor multimídia', 'PROJ-01', 'Equipamento', 2, 'disponivel'),
('Caixa de som', 'SOM-01', 'Equipamento', 1, 'disponivel'),
('Notebook institucional', 'NOTE-01', 'Equipamento', 1, 'disponivel');

INSERT INTO periodos_academicos (nome, data_inicio, data_fim, situacao)
VALUES ('2026.1', '2026-02-01', '2026-07-31', 'ativo');

INSERT INTO cursos (nome, codigo, situacao)
VALUES ('Sistemas de Informação', 'SI', 'ativo');

INSERT INTO disciplinas (curso_id, nome, periodo_referencia, professor_id, situacao)
SELECT c.id, 'Programação Web', '2026.1', u.id, 'ativa'
FROM cursos c JOIN usuarios u ON u.email = 'professor@sgrp.local';

INSERT INTO configuracoes_sistema (chave, valor, descricao)
VALUES ('dias_bloqueio_advertencia', '7', 'Quantidade de dias de bloqueio após mais de três advertências.');
