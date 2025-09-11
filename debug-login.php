<?php
/**
 * Debug Login - Verificar datos de usuario específico
 */

require_once 'api/config.php';

try {
    $pdo = getDBConnection();
    
    $email = 'cesarmatetal@gmail.com';
    $password = 'Anubis@68';
    
    echo "<h1>🔍 Debug Login para: $email</h1>";
    
    // Buscar en ecc_users
    $stmt = $pdo->prepare("SELECT * FROM ecc_users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "<h2>✅ Usuario encontrado en ecc_users:</h2>";
        echo "<table border='1'>";
        echo "<tr><th>Campo</th><th>Valor</th></tr>";
        foreach ($user as $key => $value) {
            if ($key === 'password_hash') {
                echo "<tr><td>$key</td><td>" . substr($value, 0, 20) . "...</td></tr>";
            } else {
                echo "<tr><td>$key</td><td>$value</td></tr>";
            }
        }
        echo "</table>";
        
        // Verificar contraseña
        echo "<h3>🔐 Verificación de contraseña:</h3>";
        echo "<p>Contraseña ingresada: $password</p>";
        echo "<p>Hash almacenado: " . substr($user['password_hash'], 0, 30) . "...</p>";
        
        $verified = password_verify($password, $user['password_hash']);
        echo "<p>password_verify resultado: " . ($verified ? "✅ CORRECTO" : "❌ INCORRECTO") . "</p>";
        
        // Intentar comparación directa
        $direct = ($password === $user['password_hash']);
        echo "<p>Comparación directa: " . ($direct ? "✅ CORRECTO" : "❌ INCORRECTO") . "</p>";
        
        // Verificar si el email está verificado
        echo "<p>Email verificado: " . ($user['email_verified'] ? "✅ SÍ" : "❌ NO") . "</p>";
        
    } else {
        echo "<h2>❌ Usuario NO encontrado en ecc_users</h2>";
        
        // Buscar en tabla user
        $stmt = $pdo->prepare("SELECT * FROM user WHERE email = ?");
        $stmt->execute([$email]);
        $user2 = $stmt->fetch();
        
        if ($user2) {
            echo "<h2>✅ Usuario encontrado en tabla 'user':</h2>";
            echo "<table border='1'>";
            echo "<tr><th>Campo</th><th>Valor</th></tr>";
            foreach ($user2 as $key => $value) {
                echo "<tr><td>$key</td><td>$value</td></tr>";
            }
            echo "</table>";
        } else {
            echo "<h2>❌ Usuario NO encontrado en ninguna tabla</h2>";
        }
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Error: " . $e->getMessage() . "</h2>";
}
?>
