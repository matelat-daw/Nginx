<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><title>Setup Base de Datos Local</title></head><body>";
echo "<h1>🔧 Configuración de Base de Datos Local</h1>";

// Cargar configuración
require_once 'config.php';

echo "<h2>📋 Configuración actual:</h2>";
echo "<ul>";
echo "<li><strong>Host:</strong> " . DB_HOST . "</li>";
echo "<li><strong>Base de datos:</strong> " . DB_NAME . "</li>";
echo "<li><strong>Usuario:</strong> " . DB_USER . "</li>";
echo "<li><strong>Puerto:</strong> " . DB_PORT . "</li>";
echo "</ul>";

try {
    // Primero intentar conectar sin especificar base de datos
    echo "<h2>🔌 Conectando al servidor MySQL...</h2>";
    
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "✅ <strong>Conexión al servidor MySQL exitosa!</strong><br>";
    
    // Verificar si la base de datos existe
    echo "<h2>🔍 Verificando base de datos...</h2>";
    $stmt = $pdo->query("SHOW DATABASES LIKE '" . DB_NAME . "'");
    $dbExists = $stmt->fetch();
    
    if (!$dbExists) {
        echo "📝 Creando base de datos '" . DB_NAME . "'...<br>";
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "✅ Base de datos creada exitosamente!<br>";
    } else {
        echo "✅ La base de datos '" . DB_NAME . "' ya existe.<br>";
    }
    
    // Ahora conectar a la base de datos específica
    $pdo = null; // Cerrar conexión anterior
    $pdo = getDBConnection();
    echo "✅ Conectado a la base de datos '" . DB_NAME . "'<br>";
    
    // Verificar si la tabla ecc_users existe
    echo "<h2>📊 Verificando tabla ecc_users...</h2>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'ecc_users'");
    $tableExists = $stmt->fetch();
    
    if (!$tableExists) {
        echo "📝 Creando tabla ecc_users...<br>";
        
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
        echo "✅ Tabla ecc_users creada exitosamente!<br>";
        
        // Insertar usuario de prueba
        echo "👤 Creando usuario administrador...<br>";
        $stmt = $pdo->prepare("
            INSERT INTO ecc_users 
            (username, email, password_hash, first_name, last_name, email_confirmed) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt->execute(['admin', 'admin@canarias-ec.com', $hashedPassword, 'Administrador', 'Sistema', 1]);
        echo "✅ Usuario administrador creado!<br>";
        
        // Usuario de prueba adicional
        $hashedPassword2 = password_hash('test123', PASSWORD_DEFAULT);
        $stmt->execute(['testuser', 'test@canarias-ec.com', $hashedPassword2, 'Usuario', 'Prueba', 1]);
        echo "✅ Usuario de prueba creado!<br>";
        
    } else {
        echo "✅ La tabla ecc_users ya existe.<br>";
    }
    
    // Mostrar usuarios existentes
    echo "<h2>👥 Usuarios en la base de datos:</h2>";
    $stmt = $pdo->query("SELECT id, username, email, email_confirmed, created_at FROM ecc_users ORDER BY created_at DESC");
    $users = $stmt->fetchAll();
    
    if (empty($users)) {
        echo "<p>No hay usuarios registrados.</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>Usuario</th><th>Email</th><th>Email Confirmado</th><th>Creado</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($user['id']) . "</td>";
            echo "<td>" . htmlspecialchars($user['username']) . "</td>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td>" . ($user['email_confirmed'] ? '✅' : '❌') . "</td>";
            echo "<td>" . htmlspecialchars($user['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h2>🎉 ¡Configuración completada!</h2>";
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>Credenciales de acceso:</h3>";
    echo "<p><strong>Usuario Administrador:</strong><br>";
    echo "Email: <code>admin@canarias-ec.com</code><br>";
    echo "Contraseña: <code>admin123</code></p>";
    echo "<p><strong>Usuario de Prueba:</strong><br>";
    echo "Email: <code>test@canarias-ec.com</code><br>";
    echo "Contraseña: <code>test123</code></p>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<h2>❌ Error de conexión</h2>";
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    
    echo "<h3>🔧 Posibles soluciones:</h3>";
    echo "<ol>";
    echo "<li><strong>Instalar XAMPP/WAMP:</strong> Si no tienes un servidor MySQL local, instala XAMPP desde <a href='https://www.apachefriends.org/'>https://www.apachefriends.org/</a></li>";
    echo "<li><strong>Iniciar MySQL:</strong> Abre el panel de control de XAMPP y inicia el servicio MySQL</li>";
    echo "<li><strong>Verificar puerto:</strong> Asegúrate de que MySQL esté corriendo en el puerto 3306</li>";
    echo "<li><strong>Configurar contraseña:</strong> Si tu MySQL local tiene contraseña, actualiza el archivo .env</li>";
    echo "</ol>";
    echo "</div>";
} catch (Exception $e) {
    echo "❌ <strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
}

echo "<hr>";
echo "<p><a href='test-auth.php'>🧪 Probar autenticación</a> | <a href='../index.html'>🏠 Ir a la aplicación</a></p>";
echo "</body></html>";
?>
