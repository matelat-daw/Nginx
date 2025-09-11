<?php
/**
 * Debug contraseña específica
 */

require_once 'api/config.php';

try {
    $pdo = getDBConnection();
    
    $email = 'cesarmatelat@gmail.com';
    $password = 'Anubis@68';
    
    echo "<h1>🔐 Debug contraseña para: $email</h1>";
    
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, password_hash, email_verified FROM ecc_users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "<h2>✅ Usuario encontrado:</h2>";
        echo "<p><strong>ID:</strong> {$user['id']}</p>";
        echo "<p><strong>Nombre:</strong> {$user['first_name']} {$user['last_name']}</p>";
        echo "<p><strong>Email:</strong> {$user['email']}</p>";
        echo "<p><strong>Email verificado:</strong> " . ($user['email_verified'] ? 'SÍ' : 'NO') . "</p>";
        
        echo "<h3>🔐 Test de contraseña:</h3>";
        echo "<p><strong>Contraseña a probar:</strong> $password</p>";
        echo "<p><strong>Hash almacenado:</strong> " . substr($user['password_hash'], 0, 60) . "...</p>";
        
        // Test password_verify
        $verified = password_verify($password, $user['password_hash']);
        echo "<p><strong>password_verify:</strong> " . ($verified ? "✅ VÁLIDA" : "❌ INVÁLIDA") . "</p>";
        
        // Test comparación directa
        $direct = ($password === $user['password_hash']);
        echo "<p><strong>Comparación directa:</strong> " . ($direct ? "✅ VÁLIDA" : "❌ INVÁLIDA") . "</p>";
        
        // Generar nuevo hash para comparar
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        echo "<p><strong>Nuevo hash generado:</strong> " . substr($newHash, 0, 60) . "...</p>";
        
        $newVerify = password_verify($password, $newHash);
        echo "<p><strong>Verificación del nuevo hash:</strong> " . ($newVerify ? "✅ VÁLIDA" : "❌ INVÁLIDA") . "</p>";
        
    } else {
        echo "<h2>❌ Usuario no encontrado</h2>";
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Error: " . $e->getMessage() . "</h2>";
}
?>
