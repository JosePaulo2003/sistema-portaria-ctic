-- GUIA DE MANUTENCAO: Este arquivo define migração incremental; deve poder ser auditada e aplicada uma única vez no banco correto.
-- Ponto de atencao: faca backup antes de executar. ROLLBACK escrito depois do desastre continua sendo ficcao cientifica.

USE sgrp;

ALTER TABLE usuarios
  ADD COLUMN IF NOT EXISTS curso_id INT NULL AFTER projeto_pesquisa;

SET @fk_usuarios_curso := (
  SELECT COUNT(*)
  FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'usuarios'
    AND CONSTRAINT_NAME = 'fk_usuarios_curso'
);

SET @sql_usuarios_curso := IF(
  @fk_usuarios_curso = 0,
  'ALTER TABLE usuarios ADD CONSTRAINT fk_usuarios_curso FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE SET NULL',
  'SELECT 1'
);

PREPARE stmt_usuarios_curso FROM @sql_usuarios_curso;
EXECUTE stmt_usuarios_curso;
DEALLOCATE PREPARE stmt_usuarios_curso;
