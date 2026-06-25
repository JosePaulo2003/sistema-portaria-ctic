USE sgrp;

INSERT INTO perfis (nome, nivel) VALUES
('Desenvolvedor', 100),
('Diretor', 85),
('Administrativo', 80),
('Secretário de Curso', 70),
('Agente de Portaria', 60),
('Professor', 50),
('Aluno Bolsista', 40),
('Serviços Gerais', 30),
('Aluno', 20),
('Visitante', 10);

SET @senha_desenvolvedor = '$2y$10$0zy/5kkBuMKdZR6qrIJ3KOZoUgSXvf8MdCGKOIN2gbuHwSvuSjYpK';

INSERT INTO usuarios (nome, email, senha_hash, perfil_id, situacao)
SELECT 'Desenvolvedor', 'desenvolvedor@sgrp.local', @senha_desenvolvedor, id, 'ativo'
FROM perfis
WHERE nome = 'Desenvolvedor';

INSERT INTO configuracoes_sistema (chave, valor, descricao)
VALUES ('dias_bloqueio_advertencia', '7', 'Quantidade de dias de bloqueio após mais de três advertências.');
