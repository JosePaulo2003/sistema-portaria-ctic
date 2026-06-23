USE sgrp;

INSERT INTO perfis (nome, nivel)
SELECT 'Diretor', 85
WHERE NOT EXISTS (
    SELECT 1 FROM perfis WHERE nome = 'Diretor'
);
