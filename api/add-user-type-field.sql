-- Script para actualizar la tabla users con campos faltantes
-- Ejecutar este script para sincronizar la estructura de la base de datos

-- ============================================
-- 1. Agregar campo user_type si no existe
-- ============================================
SET @dbname = DATABASE();
SET @tablename = 'users';
SET @columnname = 'user_type';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT ''El campo user_type ya existe'' AS resultado;',
  'ALTER TABLE users ADD COLUMN user_type VARCHAR(50) DEFAULT ''particular'' AFTER city;'
));

PREPARE alterStatement FROM @preparedStatement;
EXECUTE alterStatement;
DEALLOCATE PREPARE alterStatement;

-- ============================================
-- 2. Renombrar email_confirmed a email_verified si existe
-- ============================================
SET @columnname_old = 'email_confirmed';
SET @columnname_new = 'email_verified';
SET @preparedStatement2 = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname_old)
  ) > 0,
  'ALTER TABLE users CHANGE email_confirmed email_verified TINYINT(1) DEFAULT 0;',
  'SELECT ''El campo email_verified ya existe o email_confirmed no existe'' AS resultado;'
));

PREPARE alterStatement2 FROM @preparedStatement2;
EXECUTE alterStatement2;
DEALLOCATE PREPARE alterStatement2;

-- ============================================
-- 3. Verificar estructura final
-- ============================================
DESCRIBE users;

-- ============================================
-- 4. Actualizar usuarios existentes
-- ============================================
UPDATE users SET user_type = 'particular' WHERE user_type IS NULL OR user_type = '';

-- ============================================
-- 5. Mostrar resultados
-- ============================================
SELECT 'Estructura de tabla actualizada exitosamente' AS resultado;
SELECT id, email, user_type, email_verified FROM users LIMIT 5;
