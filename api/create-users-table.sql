-- Script de creación de tabla users
-- Base de datos: users
-- Tabla optimizada para el sistema de Economía Circular Canarias

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `island` varchar(50) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `email_confirmed` tinyint(1) DEFAULT 0,
  `email_confirmation_token` varchar(255) DEFAULT NULL,
  `email_confirmation_expires` datetime DEFAULT NULL,
  `account_locked` tinyint(1) DEFAULT 0,
  `failed_login_attempts` int(11) DEFAULT 0,
  `last_failed_login` datetime DEFAULT NULL,
  `last_successful_login` datetime DEFAULT NULL,
  `password_reset_token` varchar(255) DEFAULT NULL,
  `password_reset_expires` datetime DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`),
  KEY `idx_username` (`username`),
  KEY `idx_email_confirmed` (`email_confirmed`),
  KEY `idx_account_locked` (`account_locked`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar usuario de prueba (contraseña: test123)
INSERT IGNORE INTO `users` 
(`username`, `email`, `password_hash`, `first_name`, `last_name`, `email_confirmed`) 
VALUES 
('admin', 'admin@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'Test', 1);

-- Verificar que se creó correctamente
SELECT 'Tabla users creada exitosamente' as resultado;
SELECT COUNT(*) as total_usuarios FROM users;
