USE sgrp;

INSERT INTO perfis (nome, nivel)
VALUES ('Motorista', 25)
ON DUPLICATE KEY UPDATE nivel = VALUES(nivel);
