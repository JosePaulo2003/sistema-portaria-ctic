-- GUIA DE MANUTENCAO: Este arquivo define dados iniciais sem credenciais reais.
-- Ponto de atencao: faca backup antes de executar. ROLLBACK escrito depois do desastre continua sendo ficcao cientifica.

USE sgrp;

INSERT INTO perfis (nome, nivel) VALUES
('Desenvolvedor', 100),
('Diretor', 85),
('Administrativo', 80),
('Secretário de Curso', 70),
('Agente de Portaria', 60),
('Professor', 50),
('Aluno Bolsista', 40),
('Coordenador de Curso', 75),
('Estagiario', 35),
('Serviços Gerais', 30),
('Motorista', 25),
('Aluno', 20),
('Visitante', 10);

INSERT INTO configuracoes_sistema (chave, valor, descricao)
VALUES ('dias_bloqueio_advertencia', '7', 'Quantidade de dias de bloqueio após mais de três advertências.');
