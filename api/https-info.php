<?php
header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><title>Configuración HTTPS - Canarias EC</title></head><body>";
echo "<h1>🔒 Configuración HTTPS - Economía Circular Canarias</h1>";

echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h2>✅ Configuración actualizada para HTTPS</h2>";
echo "<p>El proyecto ha sido configurado para funcionar con HTTPS en localhost.</p>";
echo "</div>";

// Mostrar configuración actual
require_once 'config.php';

echo "<h2>📋 Configuración actual:</h2>";
echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
echo "<tr><th style='padding: 8px;'>Parámetro</th><th style='padding: 8px;'>Valor</th></tr>";
echo "<tr><td style='padding: 8px;'>Base de datos</td><td style='padding: 8px;'>" . DB_HOST . ":" . DB_PORT . "/" . DB_NAME . "</td></tr>";
echo "<tr><td style='padding: 8px;'>Protocolo</td><td style='padding: 8px;'>HTTPS (localhost)</td></tr>";
echo "<tr><td style='padding: 8px;'>SITE_URL</td><td style='padding: 8px;'>" . SITE_URL . "</td></tr>";
echo "<tr><td style='padding: 8px;'>Entorno</td><td style='padding: 8px;'>" . (DEBUG_MODE ? 'Desarrollo (Debug ON)' : 'Producción') . "</td></tr>";
echo "</table>";

echo "<h2>🌐 URLs de acceso:</h2>";
echo "<ul>";
echo "<li><strong>Aplicación principal:</strong> <a href='https://localhost/Canarias-EC/'>https://localhost/Canarias-EC/</a></li>";
echo "<li><strong>API de prueba:</strong> <a href='https://localhost/Canarias-EC/api/test-auth.php'>https://localhost/Canarias-EC/api/test-auth.php</a></li>";
echo "<li><strong>Verificar BD:</strong> <a href='https://localhost/Canarias-EC/api/check-mysql.php'>https://localhost/Canarias-EC/api/check-mysql.php</a></li>";
echo "</ul>";

echo "<h2>🔧 Orígenes CORS permitidos:</h2>";
echo "<ul>";
echo "<li>https://localhost</li>";
echo "<li>https://localhost:443</li>";
echo "<li>https://127.0.0.1</li>";
echo "<li>https://127.0.0.1:443</li>";
echo "</ul>";
echo "<p><strong>Nota:</strong> Solo se permiten conexiones HTTPS para mayor seguridad.</p>";

// Test de conexión rápida
echo "<h2>🔍 Test rápido de conexión:</h2>";
try {
    $pdo = getDBConnection();
    echo "<p style='color: green;'>✅ <strong>Conexión a MySQL exitosa</strong></p>";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'ecc_users'");
    if ($stmt->fetch()) {
        echo "<p style='color: green;'>✅ <strong>Tabla ecc_users existe</strong></p>";
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM ecc_users");
        $count = $stmt->fetch();
        echo "<p>👥 <strong>Usuarios registrados:</strong> " . $count['total'] . "</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ <strong>Tabla ecc_users no existe</strong> - <a href='setup-local-db.php'>Crear tabla</a></p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ <strong>Error de conexión:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><a href='check-mysql.php'>🔧 Diagnosticar MySQL</a></p>";
}

echo "<h2>📝 Diferencias entre proyectos:</h2>";
echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px;'>";
echo "<h3>🌟 Nexus Astralis:</h3>";
echo "<ul>";
echo "<li>Base de datos: SQL Server</li>";
echo "<li>Servidor: IP pública + HTTPS</li>";
echo "</ul>";

echo "<h3>🌿 Economía Circular Canarias:</h3>";
echo "<ul>";
echo "<li>Base de datos: MySQL (localhost)</li>";
echo "<li>Servidor: localhost + HTTPS</li>";
echo "<li>CORS: Configurado para localhost</li>";
echo "</ul>";
echo "</div>";

echo "<hr>";
echo "<p><a href='../index.html'>🏠 Ir a la aplicación</a></p>";
echo "</body></html>";
?>
