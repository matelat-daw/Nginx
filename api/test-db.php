<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

echo "<!DOCTYPE html><html><head><title>Test Conexión DB</title></head><body>";
echo "<h1>🔍 Test de Conexión a Base de Datos</h1>";

try {
    echo "<h2>📋 Configuración actual:</h2>";
    echo "<ul>";
    echo "<li><strong>Host:</strong> " . DB_HOST . "</li>";
    echo "<li><strong>Puerto:</strong> " . DB_PORT . "</li>";
    echo "<li><strong>Base de datos:</strong> " . DB_NAME . "</li>";
    echo "<li><strong>Usuario:</strong> " . DB_USER . "</li>";
    echo "<li><strong>Charset:</strong> " . DB_CHARSET . "</li>";
    echo "<li><strong>Debug Mode:</strong> " . (DEBUG_MODE ? 'ON' : 'OFF') . "</li>";
    echo "</ul>";
    
    echo "<h2>🔌 Probando conexión...</h2>";
    
    // Intentar conexión
    $pdo = getDBConnection();
    echo "✅ <strong>Conexión exitosa!</strong><br>";
    
    // Verificar que la tabla existe
    echo "<h2>📊 Verificando tabla ecc_users...</h2>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'ecc_users'");
    $tableExists = $stmt->fetch();
    
    if ($tableExists) {
        echo "✅ <strong>Tabla 'ecc_users' encontrada!</strong><br>";
        
        // Verificar estructura de la tabla
        echo "<h3>🏗️ Estructura de la tabla:</h3>";
        $stmt = $pdo->query("DESCRIBE ecc_users");
        $columns = $stmt->fetchAll();
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Clave</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Contar registros
        echo "<h3>📈 Número de usuarios registrados:</h3>";
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM ecc_users");
        $count = $stmt->fetch();
        echo "Total de usuarios: <strong>" . $count['total'] . "</strong><br>";
        
        // Verificar usuarios recientes
        if ($count['total'] > 0) {
            echo "<h3>👥 Últimos 5 usuarios:</h3>";
            $stmt = $pdo->query("SELECT id, username, email, created_at, email_confirmed FROM ecc_users ORDER BY created_at DESC LIMIT 5");
            $users = $stmt->fetchAll();
            
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr><th>ID</th><th>Usuario</th><th>Email</th><th>Creado</th><th>Email Confirmado</th></tr>";
            foreach ($users as $user) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($user['id']) . "</td>";
                echo "<td>" . htmlspecialchars($user['username']) . "</td>";
                echo "<td>" . htmlspecialchars($user['email']) . "</td>";
                echo "<td>" . htmlspecialchars($user['created_at']) . "</td>";
                echo "<td>" . ($user['email_confirmed'] ? '✅' : '❌') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } else {
        echo "❌ <strong>Error: Tabla 'ecc_users' NO encontrada!</strong><br>";
        echo "<p>📝 <strong>Solución:</strong> Necesitas ejecutar el script de creación de tabla.</p>";
        
        // Mostrar todas las tablas disponibles
        echo "<h3>📋 Tablas disponibles en la base de datos:</h3>";
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll();
        
        if (empty($tables)) {
            echo "❌ No hay tablas en la base de datos.<br>";
        } else {
            echo "<ul>";
            foreach ($tables as $table) {
                echo "<li>" . htmlspecialchars(array_values($table)[0]) . "</li>";
            }
            echo "</ul>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ <strong>Error de conexión:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "<p>🔧 <strong>Posibles causas:</strong></p>";
    echo "<ul>";
    echo "<li>El servidor MySQL no está corriendo</li>";
    echo "<li>Las credenciales en el archivo .env son incorrectas</li>";
    echo "<li>El host/puerto no es accesible</li>";
    echo "<li>La base de datos '" . DB_NAME . "' no existe</li>";
    echo "</ul>";
}

echo "</body></html>";
?>
