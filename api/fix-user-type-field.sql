-- Script para corregir el campo user_type
-- El problema es que el ENUM tiene valores en inglés pero la app usa español

USE users;

-- Verificar el tipo actual
SELECT COLUMN_TYPE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'users' 
AND COLUMN_NAME = 'user_type';

-- Opción 1: Cambiar ENUM a VARCHAR para mayor flexibilidad
ALTER TABLE users MODIFY COLUMN user_type VARCHAR(50) DEFAULT 'particular';

-- Actualizar valores existentes de inglés a español
UPDATE users SET user_type = 'particular' WHERE user_type = 'individual' OR user_type = '' OR user_type IS NULL;
UPDATE users SET user_type = 'empresa' WHERE user_type = 'business';
UPDATE users SET user_type = 'organizacion' WHERE user_type = 'organization';

-- Verificar cambios
SELECT id, email, first_name, user_type FROM users;

SELECT 'Campo user_type actualizado correctamente' AS resultado;
