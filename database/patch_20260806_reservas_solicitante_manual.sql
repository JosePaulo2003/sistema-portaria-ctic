-- GUIA DE MANUTENCAO: Este arquivo define migração incremental; deve poder ser auditada e aplicada uma única vez no banco correto.
-- Ponto de atencao: faca backup antes de executar. ROLLBACK escrito depois do desastre continua sendo ficcao cientifica.

ALTER TABLE reservas
  ADD COLUMN IF NOT EXISTS solicitante_nome_manual VARCHAR(180) NULL AFTER usuario_id;
