CREATE TABLE IF NOT EXISTS recuperacoes_senha (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expira_em DATETIME NOT NULL,
  usado_em DATETIME NULL,
  tentativas TINYINT UNSIGNED NOT NULL DEFAULT 0,
  solicitado_ip_hash CHAR(64) NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_recuperacoes_senha_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  UNIQUE KEY uk_recuperacoes_senha_token (token_hash),
  INDEX idx_recuperacoes_senha_usuario (usuario_id, usado_em, expira_em),
  INDEX idx_recuperacoes_senha_ip (solicitado_ip_hash, criado_em),
  INDEX idx_recuperacoes_senha_criado (criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
