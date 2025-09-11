<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');
header('Access-Control-Allow-Origin: *');

echo "<!DOCTYPE html><html><head><title>Crear Tabla ecc_users</title></head><body>";
echo "<h1>🔧 Creación de Tabla ecc_users</h1>";

try {
    echo "<h2>📋 Configuración de conexión:</h2>";
    echo "<ul>";
    echo "<li><strong>Host:</strong> " . DB_HOST . "</li>";
    echo "<li><strong>Base de datos:</strong> " . DB_NAME . "</li>";
    echo "<li><strong>Usuario:</strong> " . DB_USER . "</li>";
    echo "</ul>";
    
    // Conectar a la base de datos
    $pdo = getDBConnection();
    echo "✅ <strong>Conexión exitosa!</strong><br><br>";
    
    // Leer el archivo SQL
    $sqlFile = __DIR__ . '/create-users-table.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("Archivo SQL no encontrado: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    $statements = explode(';', $sql);
    
    echo "<h2>🛠️ Ejecutando comandos SQL...</h2>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px;'>";
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (empty($statement)) continue;
        
        try {
            $result = $pdo->exec($statement);
            echo "✅ Ejecutado: " . substr($statement, 0, 50) . "...\n";
        } catch (PDOException $e) {
            echo "⚠️  Error: " . $e->getMessage() . "\n";
            echo "   SQL: " . substr($statement, 0, 100) . "...\n";
        }
    }
    
    echo "</pre>";
    
    // Verificar que la tabla existe
    echo "<h2>🔍 Verificando tabla...</h2>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'ecc_users'");
    $tableExists = $stmt->fetch();
    
    if ($tableExists) {
        echo "✅ <strong>Tabla 'ecc_users' creada exitosamente!</strong><br>";
        
        // Mostrar estructura
        echo "<h3>🏗️ Estructura de la tabla:</h3>";
        $stmt = $pdo->query("DESCRIBE ecc_users");
        $columns = $stmt->fetchAll();
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Clave</th><th>Default</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Contar usuarios
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM ecc_users");
        $count = $stmt->fetch();
        echo "<p><strong>Total de usuarios:</strong> " . $count['total'] . "</p>";
        
        if ($count['total'] > 0) {
            echo "<h3>👥 Usuarios registrados:</h3>";
            $stmt = $pdo->query("SELECT id, username, email, email_confirmed, created_at FROM ecc_users ORDER BY created_at DESC");
            $users = $stmt->fetchAll();
            
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
        
    } else {
        echo "❌ <strong>Error: La tabla no se pudo crear</strong>";
    }
    
} catch (Exception $e) {
    echo "❌ <strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
}

echo "<hr>";
echo "<p><a href='test-db.php'>🔍 Probar conexión a BD</a> | <a href='../index.html'>🏠 Volver a la aplicación</a></p>";
echo "</body></html>";
?>
