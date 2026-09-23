-- GUIA DE MANUTENCAO: Este arquivo define migração incremental; deve poder ser auditada e aplicada uma única vez no banco correto.
-- Ponto de atencao: faca backup antes de executar. ROLLBACK escrito depois do desastre continua sendo ficcao cientifica.

ALTER TABLE recuperacoes_senha
  ADD COLUMN IF NOT EXISTS tentativas TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER usado_em;
