ALTER TABLE reservas
  ADD COLUMN IF NOT EXISTS solicitante_nome_manual VARCHAR(180) NULL AFTER usuario_id;
