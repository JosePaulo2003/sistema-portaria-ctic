ALTER TABLE movimentacoes
  ADD COLUMN IF NOT EXISTS usuario_nome_manual VARCHAR(180) NULL AFTER usuario_id;
