-- GUIA DE MANUTENCAO: Este arquivo define migração incremental; deve poder ser auditada e aplicada uma única vez no banco correto.
-- Ponto de atencao: faca backup antes de executar. ROLLBACK escrito depois do desastre continua sendo ficcao cientifica.

ALTER TABLE usuarios
  ADD COLUMN IF NOT EXISTS matricula VARCHAR(80) NULL AFTER email;

ALTER TABLE permissoes_salas
  ADD COLUMN IF NOT EXISTS horario_inicio TIME NULL AFTER expira_em,
  ADD COLUMN IF NOT EXISTS horario_fim TIME NULL AFTER horario_inicio;
