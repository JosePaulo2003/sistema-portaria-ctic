ALTER TABLE usuarios
  ADD COLUMN IF NOT EXISTS matricula VARCHAR(80) NULL AFTER email;

ALTER TABLE permissoes_salas
  ADD COLUMN IF NOT EXISTS horario_inicio TIME NULL AFTER expira_em,
  ADD COLUMN IF NOT EXISTS horario_fim TIME NULL AFTER horario_inicio;
