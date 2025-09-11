<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><title>Crear Usuario de Prueba</title></head><body>";
echo "<h1>👤 Crear Usuario de Prueba</h1>";

try {
    $pdo = getDBConnection();
    echo "✅ Conexión a BD exitosa<br>";
    
    // Verificar si existe la tabla
    $stmt = $pdo->query("SHOW TABLES LIKE 'ecc_users'");
    if (!$stmt->fetch()) {
        echo "❌ Tabla ecc_users no existe. <a href='setup-local-db.php'>Crear tabla</a><br>";
        exit;
    }
    
    // Crear usuario de prueba simple
    $email = 'test@test.com';
    $password = '123456';
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    // Verificar si el usuario ya existe
    $stmt = $pdo->prepare("SELECT id FROM ecc_users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        echo "⚠️ Usuario ya existe. Actualizando contraseña...<br>";
        $stmt = $pdo->prepare("UPDATE ecc_users SET password_hash = ? WHERE email = ?");
        $stmt->execute([$passwordHash, $email]);
        echo "✅ Contraseña actualizada<br>";
    } else {
        echo "📝 Creando nuevo usuario...<br>";
        $stmt = $pdo->prepare("
            INSERT INTO ecc_users 
            (username, email, password_hash, first_name, last_name, email_confirmed) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute(['testuser', $email, $passwordHash, 'Test', 'User', 1]);
        echo "✅ Usuario creado<br>";
    }
    
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>🔑 Credenciales de prueba:</h3>";
    echo "<p><strong>Email:</strong> <code>$email</code></p>";
    echo "<p><strong>Contraseña:</strong> <code>$password</code></p>";
    echo "</div>";
    
    // Test de verificación de contraseña
    echo "<h2>🔍 Test de verificación de contraseña:</h2>";
    $testResult = password_verify($password, $passwordHash);
    echo "<p>password_verify('$password', hash): " . ($testResult ? '✅ TRUE' : '❌ FALSE') . "</p>";
    echo "<p>Hash generado: <code>$passwordHash</code></p>";
    
    // Probar login directamente
    echo "<h2>🔐 Test de login directo:</h2>";
    $stmt = $pdo->prepare("SELECT * FROM ecc_users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "<p>✅ Usuario encontrado en BD</p>";
        echo "<p>Email en BD: <code>{$user['email']}</code></p>";
        echo "<p>Hash en BD: <code>{$user['password_hash']}</code></p>";
        
        $loginTest = password_verify($password, $user['password_hash']);
        echo "<p>Test login: " . ($loginTest ? '✅ EXITOSO' : '❌ FALLÓ') . "</p>";
    }
    
    // Mostrar todos los usuarios
    echo "<h2>👥 Usuarios en la base de datos:</h2>";
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
    
} catch (Exception $e) {
    echo "❌ Error: " . htmlspecialchars($e->getMessage()) . "<br>";
}

echo "<hr>";
echo "<p><a href='test-password-toggle.html'>🔍 Test Toggle</a> | <a href='../index.html'>🏠 App</a></p>";
echo "</body></html>";
?>
