-- GUIA DE MANUTENCAO: Este arquivo define migração incremental; deve poder ser auditada e aplicada uma única vez no banco correto.
-- Ponto de atencao: faca backup antes de executar. ROLLBACK escrito depois do desastre continua sendo ficcao cientifica.

USE sgrp;

INSERT IGNORE INTO perfis (nome, nivel) VALUES
('Coordenador de Curso', 75),
('Estagiario', 35);
