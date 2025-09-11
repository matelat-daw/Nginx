<?php
header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><title>Verificar MySQL Local</title></head><body>";
echo "<h1>🔍 Verificación de MySQL Local</h1>";

// Verificar si las extensiones de PHP están disponibles
echo "<h2>📋 Verificación de PHP:</h2>";
echo "<ul>";
echo "<li>Extensión PDO: " . (extension_loaded('pdo') ? '✅ Disponible' : '❌ No disponible') . "</li>";
echo "<li>Extensión PDO MySQL: " . (extension_loaded('pdo_mysql') ? '✅ Disponible' : '❌ No disponible') . "</li>";
echo "<li>Versión PHP: " . phpversion() . "</li>";
echo "</ul>";

// Intentar diferentes configuraciones de conexión
$configs = [
    ['host' => 'localhost', 'port' => 3306, 'user' => 'root', 'pass' => ''],
    ['host' => '127.0.0.1', 'port' => 3306, 'user' => 'root', 'pass' => ''],
    ['host' => 'localhost', 'port' => 3307, 'user' => 'root', 'pass' => ''], // XAMPP alternativo
];

echo "<h2>🔌 Probando conexiones MySQL:</h2>";

foreach ($configs as $i => $config) {
    echo "<h3>Configuración " . ($i + 1) . ":</h3>";
    echo "<ul>";
    echo "<li>Host: " . $config['host'] . "</li>";
    echo "<li>Puerto: " . $config['port'] . "</li>";
    echo "<li>Usuario: " . $config['user'] . "</li>";
    echo "<li>Contraseña: " . (empty($config['pass']) ? '(vacía)' : '(configurada)') . "</li>";
    echo "</ul>";
    
    try {
        $dsn = "mysql:host={$config['host']};port={$config['port']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]);
        
        echo "<p style='color: green;'>✅ <strong>¡Conexión exitosa!</strong></p>";
        
        // Mostrar versión de MySQL
        $stmt = $pdo->query("SELECT VERSION() as version");
        $version = $stmt->fetch();
        echo "<p>Versión MySQL: " . $version['version'] . "</p>";
        
        // Mostrar bases de datos
        $stmt = $pdo->query("SHOW DATABASES");
        $databases = $stmt->fetchAll();
        echo "<p>Bases de datos disponibles:</p><ul>";
        foreach ($databases as $db) {
            echo "<li>" . $db['Database'] . "</li>";
        }
        echo "</ul>";
        
        // Esta configuración funciona, guardarla en .env
        $envContent = "# Entorno de desarrollo/producción
ENVIRONMENT=development

# Configuración de base de datos MySQL LOCAL (¡FUNCIONA!)
DB_HOST={$config['host']}
DB_NAME=canarias_ec
DB_USER={$config['user']}
DB_PASS={$config['pass']}
DB_CHARSET=utf8mb4
DB_PORT={$config['port']}

# Configuración de JWT
JWT_SECRET=fallback_secret_key_change_in_production

# Configuración de CORS para HTTPS local
CORS_ORIGIN=https://localhost";

        file_put_contents(__DIR__ . '/../.env', $envContent);
        echo "<p style='background: #d4edda; padding: 10px; border-radius: 5px;'>✅ Configuración guardada en .env</p>";
        
        echo "<p><a href='setup-local-db.php' style='background: #007bff; color: white; padding: 10px; text-decoration: none; border-radius: 5px;'>🔧 Configurar Base de Datos</a></p>";
        
        break; // Si funciona, no probar más configuraciones
        
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    echo "<hr>";
}

echo "<h2>💡 Instrucciones:</h2>";
echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px;'>";
echo "<h3>Si no tienes MySQL instalado:</h3>";
echo "<ol>";
echo "<li>Descarga e instala <a href='https://www.apachefriends.org/'>XAMPP</a></li>";
echo "<li>Inicia XAMPP Control Panel</li>";
echo "<li>Haz clic en 'Start' junto a MySQL</li>";
echo "<li>Recarga esta página</li>";
echo "</ol>";

echo "<h3>Si XAMPP está instalado pero MySQL no inicia:</h3>";
echo "<ol>";
echo "<li>Abre XAMPP Control Panel como administrador</li>";
echo "<li>Verifica que no haya otro servicio usando el puerto 3306</li>";
echo "<li>Intenta cambiar el puerto de MySQL a 3307 en la configuración de XAMPP</li>";
echo "</ol>";
echo "</div>";

echo "</body></html>";
?>
