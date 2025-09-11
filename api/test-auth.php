<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><title>Test Login/Register</title></head><body>";
echo "<h1>🧪 Test de Login y Registro</h1>";

try {
    // Test de conexión
    $pdo = getDBConnection();
    echo "✅ <strong>Conexión DB exitosa</strong><br>";
    
    // Verificar tabla ecc_users
    $stmt = $pdo->query("SHOW TABLES LIKE 'ecc_users'");
    if ($stmt->fetch()) {
        echo "✅ <strong>Tabla ecc_users existe</strong><br>";
        
        // Contar usuarios
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM ecc_users");
        $count = $stmt->fetch();
        echo "📊 <strong>Usuarios registrados:</strong> " . $count['total'] . "<br><br>";
        
    } else {
        echo "❌ <strong>Tabla ecc_users NO existe</strong><br>";
        echo "<p><a href='setup-database.php'>🔧 Crear tabla</a></p>";
    }
    
    // Test de registro
    echo "<h2>📝 Test de Registro</h2>";
    echo "<form method='POST' action='auth/register.php' style='margin: 20px 0;'>";
    echo "<table>";
    echo "<tr><td>Nombre de usuario:</td><td><input type='text' name='username' value='testuser' required></td></tr>";
    echo "<tr><td>Email:</td><td><input type='email' name='email' value='test@example.com' required></td></tr>";
    echo "<tr><td>Contraseña:</td><td><input type='password' name='password' value='test123456' required></td></tr>";
    echo "<tr><td>Nombre:</td><td><input type='text' name='first_name' value='Test'></td></tr>";
    echo "<tr><td>Apellido:</td><td><input type='text' name='last_name' value='User'></td></tr>";
    echo "<tr><td colspan='2'><button type='submit'>Registrar Usuario</button></td></tr>";
    echo "</table>";
    echo "</form>";
    
    // Test de login
    echo "<h2>🔐 Test de Login</h2>";
    echo "<form method='POST' action='auth/login.php' style='margin: 20px 0;'>";
    echo "<table>";
    echo "<tr><td>Email:</td><td><input type='email' name='email' value='admin@test.com' required></td></tr>";
    echo "<tr><td>Contraseña:</td><td><input type='password' name='password' value='test123' required></td></tr>";
    echo "<tr><td colspan='2'><button type='submit'>Iniciar Sesión</button></td></tr>";
    echo "</table>";
    echo "</form>";
    
    // Mostrar usuarios existentes
    if ($count['total'] > 0) {
        echo "<h2>👥 Usuarios Existentes</h2>";
        $stmt = $pdo->query("SELECT id, username, email, email_confirmed, created_at FROM ecc_users ORDER BY created_at DESC LIMIT 10");
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
    
} catch (Exception $e) {
    echo "❌ <strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
}

echo "<hr>";
echo "<p><a href='test-db.php'>🔍 Test BD</a> | <a href='setup-database.php'>🔧 Setup BD</a> | <a href='../index.html'>🏠 App</a></p>";
echo "</body></html>";
?>
