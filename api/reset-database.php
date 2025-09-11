<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><title>Limpiar y Recrear Tabla</title></head><body>";
echo "<h1>🧹 Limpiar y Recrear Tabla ecc_users</h1>";

if ($_POST['confirm'] ?? '' === 'yes') {
    try {
        $pdo = getDBConnection();
        echo "✅ Conexión exitosa<br>";
        
        // Eliminar tabla existente
        echo "<h2>🗑️ Eliminando tabla existente...</h2>";
        $pdo->exec("DROP TABLE IF EXISTS ecc_users");
        echo "✅ Tabla eliminada<br>";
        
        // Crear nueva tabla
        echo "<h2>🔨 Creando nueva tabla...</h2>";
        $sql = "
        CREATE TABLE `ecc_users` (
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
        ";
        
        $pdo->exec($sql);
        echo "✅ Tabla creada exitosamente<br>";
        
        // Insertar usuario de prueba
        echo "<h2>👤 Creando usuario de prueba...</h2>";
        $stmt = $pdo->prepare("
            INSERT INTO ecc_users 
            (username, email, password_hash, first_name, last_name, email_confirmed) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $hashedPassword = password_hash('test123', PASSWORD_DEFAULT);
        $stmt->execute(['admin', 'admin@test.com', $hashedPassword, 'Admin', 'Test', 1]);
        echo "✅ Usuario admin creado (email: admin@test.com, password: test123)<br>";
        
        // Verificar
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM ecc_users");
        $count = $stmt->fetch();
        echo "<p><strong>✅ Todo listo! Usuarios en la tabla: " . $count['total'] . "</strong></p>";
        
        echo "<p><a href='test-auth.php'>🧪 Probar autenticación</a> | <a href='../index.html'>🏠 Ir a la aplicación</a></p>";
        
    } catch (Exception $e) {
        echo "❌ Error: " . htmlspecialchars($e->getMessage()) . "<br>";
    }
} else {
    echo "<p>⚠️ <strong>ATENCIÓN:</strong> Esto eliminará todos los usuarios existentes y recreará la tabla.</p>";
    echo "<form method='POST'>";
    echo "<input type='hidden' name='confirm' value='yes'>";
    echo "<button type='submit' style='background: red; color: white; padding: 10px; font-size: 16px;'>SÍ, ELIMINAR Y RECREAR TABLA</button>";
    echo "</form>";
    echo "<p><a href='test-auth.php'>❌ Cancelar</a></p>";
}

echo "</body></html>";
?>
