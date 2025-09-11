<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><title>Detectar Configuración MySQL</title></head><body>";
echo "<h1>🔍 Detectar Configuración de MySQL</h1>";

// Configuraciones comunes para probar
$commonConfigs = [
    ['user' => 'root', 'pass' => '', 'desc' => 'Root sin contraseña (XAMPP por defecto)'],
    ['user' => 'root', 'pass' => 'root', 'desc' => 'Root con contraseña "root"'],
    ['user' => 'root', 'pass' => 'mysql', 'desc' => 'Root con contraseña "mysql"'],
    ['user' => 'root', 'pass' => 'password', 'desc' => 'Root con contraseña "password"'],
    ['user' => 'root', 'pass' => '123456', 'desc' => 'Root con contraseña "123456"'],
    ['user' => 'root', 'pass' => 'admin', 'desc' => 'Root con contraseña "admin"'],
];

echo "<h2>🔌 Probando configuraciones de MySQL...</h2>";

$workingConfig = null;

foreach ($commonConfigs as $i => $config) {
    echo "<h3>Configuración " . ($i + 1) . ": {$config['desc']}</h3>";
    
    try {
        $dsn = "mysql:host=localhost;port=3306;charset=utf8mb4";
        $pdo = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]);
        
        echo "<p style='color: green; font-weight: bold;'>✅ ¡CONEXIÓN EXITOSA!</p>";
        
        // Mostrar versión de MySQL
        $stmt = $pdo->query("SELECT VERSION() as version");
        $version = $stmt->fetch();
        echo "<p>📊 Versión MySQL: " . $version['version'] . "</p>";
        
        // Verificar privilegios
        $stmt = $pdo->query("SHOW GRANTS FOR CURRENT_USER()");
        $grants = $stmt->fetchAll();
        echo "<p>🔐 Privilegios disponibles:</p><ul>";
        foreach ($grants as $grant) {
            echo "<li>" . htmlspecialchars(array_values($grant)[0]) . "</li>";
        }
        echo "</ul>";
        
        $workingConfig = $config;
        break; // Si funciona, no probar más
        
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    echo "<hr>";
}

if ($workingConfig) {
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h2>🎉 ¡Configuración encontrada!</h2>";
    echo "<p><strong>Usuario:</strong> " . $workingConfig['user'] . "</p>";
    echo "<p><strong>Contraseña:</strong> " . ($workingConfig['pass'] ? '"' . $workingConfig['pass'] . '"' : '(vacía)') . "</p>";
    echo "</div>";
    
    // Crear nuevo contenido para .env
    $envContent = "# Entorno de desarrollo/producción
ENVIRONMENT=development

# Configuración de base de datos MySQL LOCAL (FUNCIONA!)
DB_HOST=localhost
DB_NAME=canarias_ec
DB_USER={$workingConfig['user']}
DB_PASS={$workingConfig['pass']}
DB_CHARSET=utf8mb4
DB_PORT=3306

# Configuración de JWT (cambia esta clave en producción)
JWT_SECRET=fallback_secret_key_change_in_production

# Configuración de CORS para HTTPS local
CORS_ORIGIN=https://localhost";

    // Guardar configuración
    if (file_put_contents(__DIR__ . '/../.env', $envContent)) {
        echo "<div style='background: #cce5ff; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3>✅ Archivo .env actualizado automáticamente</h3>";
        echo "<p>La configuración correcta se ha guardado en el archivo .env</p>";
        echo "</div>";
        
        // Probar la nueva configuración
        echo "<h2>🔄 Probando nueva configuración...</h2>";
        try {
            // Recargar configuración
            unset($_ENV);
            $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
                list($key, $value) = explode('=', $line, 2);
                $_ENV[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
            }
            
            $testDsn = "mysql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};charset={$_ENV['DB_CHARSET']}";
            $testPdo = new PDO($testDsn, $_ENV['DB_USER'], $_ENV['DB_PASS'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            
            echo "<p style='color: green;'>✅ <strong>Configuración .env confirmada y funcionando!</strong></p>";
            
            // Verificar/crear base de datos
            echo "<h3>📊 Configurando base de datos...</h3>";
            $testPdo->exec("CREATE DATABASE IF NOT EXISTS `{$_ENV['DB_NAME']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            echo "<p>✅ Base de datos '{$_ENV['DB_NAME']}' lista</p>";
            
            echo "<p><a href='setup-local-db.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔧 Configurar Tablas</a></p>";
            echo "<p><a href='test-auth.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;'>🧪 Probar Autenticación</a></p>";
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Error al probar nueva configuración: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Error al guardar el archivo .env</p>";
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px;'>";
        echo "<h3>📝 Actualiza manualmente el archivo .env:</h3>";
        echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 3px;'>";
        echo htmlspecialchars($envContent);
        echo "</pre>";
        echo "</div>";
    }
    
} else {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h2>❌ No se pudo conectar a MySQL</h2>";
    echo "<h3>🔧 Posibles soluciones:</h3>";
    echo "<ol>";
    echo "<li><strong>Verificar que MySQL esté corriendo:</strong> Abre XAMPP Control Panel y asegúrate de que MySQL esté iniciado</li>";
    echo "<li><strong>Comprobar puerto:</strong> Verifica que MySQL esté en el puerto 3306</li>";
    echo "<li><strong>Configurar contraseña:</strong> Si instalaste MySQL separadamente, puede que tengas una contraseña diferente</li>";
    echo "<li><strong>Reinstalar XAMPP:</strong> Si persisten los problemas, considera reinstalar XAMPP</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<h3>🔑 Configuración manual:</h3>";
    echo "<p>Si conoces tu contraseña de MySQL, actualiza manualmente el archivo .env:</p>";
    echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 3px;'>";
    echo "DB_PASS=tu_contraseña_aqui";
    echo "</pre>";
}

echo "<hr>";
echo "<p><a href='../index.html'>🏠 Volver a la aplicación</a></p>";
echo "</body></html>";
?>
