<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><title>Diagnóstico Configuración</title></head><body>";
echo "<h1>🔍 Diagnóstico de Configuración</h1>";

echo "<h2>📋 Variables de entorno desde .env:</h2>";

// Leer archivo .env directamente
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    echo "<h3>📄 Contenido del archivo .env:</h3>";
    echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 3px;'>";
    echo htmlspecialchars(file_get_contents($envFile));
    echo "</pre>";
    
    // Cargar variables manualmente
    echo "<h3>🔧 Carga manual de variables:</h3>";
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $envVars = [];
    
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
        
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        
        $envVars[$key] = $value;
        echo "<p><strong>$key:</strong> " . ($key === 'DB_PASS' ? '"' . $value . '"' : $value) . "</p>";
    }
    
} else {
    echo "<p style='color: red;'>❌ Archivo .env no encontrado en: $envFile</p>";
}

echo "<hr>";

// Cargar configuración usando config.php
echo "<h2>⚙️ Configuración cargada por config.php:</h2>";
require_once 'config.php';

echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
echo "<tr><th style='padding: 8px;'>Constante</th><th style='padding: 8px;'>Valor</th></tr>";
echo "<tr><td style='padding: 8px;'>DB_HOST</td><td style='padding: 8px;'>" . DB_HOST . "</td></tr>";
echo "<tr><td style='padding: 8px;'>DB_NAME</td><td style='padding: 8px;'>" . DB_NAME . "</td></tr>";
echo "<tr><td style='padding: 8px;'>DB_USER</td><td style='padding: 8px;'>" . DB_USER . "</td></tr>";
echo "<tr><td style='padding: 8px;'>DB_PASS</td><td style='padding: 8px;'><code>\"" . DB_PASS . "\"</code> (longitud: " . strlen(DB_PASS) . ")</td></tr>";
echo "<tr><td style='padding: 8px;'>DB_CHARSET</td><td style='padding: 8px;'>" . DB_CHARSET . "</td></tr>";
echo "<tr><td style='padding: 8px;'>DB_PORT</td><td style='padding: 8px;'>" . DB_PORT . "</td></tr>";
echo "</table>";

echo "<hr>";

// Probar conexión con la configuración actual
echo "<h2>🔌 Prueba de conexión:</h2>";

try {
    echo "<p>Intentando conectar con:</p>";
    echo "<ul>";
    echo "<li>Host: " . DB_HOST . "</li>";
    echo "<li>Puerto: " . DB_PORT . "</li>";
    echo "<li>Usuario: " . DB_USER . "</li>";
    echo "<li>Contraseña: \"" . DB_PASS . "\" (longitud: " . strlen(DB_PASS) . ")</li>";
    echo "<li>Base de datos: " . DB_NAME . "</li>";
    echo "</ul>";
    
    $pdo = getDBConnection();
    echo "<p style='color: green; font-weight: bold;'>✅ ¡CONEXIÓN EXITOSA!</p>";
    
    // Verificar versión
    $stmt = $pdo->query("SELECT VERSION() as version");
    $version = $stmt->fetch();
    echo "<p>📊 Versión MySQL: " . $version['version'] . "</p>";
    
    // Verificar base de datos
    $stmt = $pdo->query("SELECT DATABASE() as current_db");
    $currentDb = $stmt->fetch();
    echo "<p>🗄️ Base de datos actual: " . ($currentDb['current_db'] ?? 'ninguna') . "</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ <strong>Error de conexión:</strong></p>";
    echo "<p style='background: #f8d7da; padding: 10px; border-radius: 3px;'>";
    echo htmlspecialchars($e->getMessage());
    echo "</p>";
    
    // Intentar conexión sin especificar base de datos
    echo "<h3>🔄 Intentando conexión sin base de datos específica:</h3>";
    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        
        echo "<p style='color: green;'>✅ Conexión al servidor MySQL exitosa</p>";
        echo "<p>El problema puede ser que la base de datos '" . DB_NAME . "' no existe.</p>";
        
        // Mostrar bases de datos disponibles
        $stmt = $pdo->query("SHOW DATABASES");
        $databases = $stmt->fetchAll();
        echo "<p><strong>Bases de datos disponibles:</strong></p><ul>";
        foreach ($databases as $db) {
            echo "<li>" . $db['Database'] . "</li>";
        }
        echo "</ul>";
        
        echo "<p><a href='setup-local-db.php' style='background: #007bff; color: white; padding: 10px; text-decoration: none; border-radius: 5px;'>🔧 Configurar Base de Datos</a></p>";
        
    } catch (Exception $e2) {
        echo "<p style='color: red;'>❌ Error de servidor: " . htmlspecialchars($e2->getMessage()) . "</p>";
    }
}

echo "<hr>";
echo "<p><a href='../index.html'>🏠 Volver a la aplicación</a></p>";
echo "</body></html>";
?>
