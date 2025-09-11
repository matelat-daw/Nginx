<?php
/**
 * Actualizar contraseña del usuario
 */

require_once 'api/config.php';

try {
    $pdo = getDBConnection();
    
    $email = 'cesarmatelat@gmail.com';
    $newPassword = 'Anubis@68';
    
    echo "<h1>🔄 Actualizando contraseña para: $email</h1>";
    
    // Generar nuevo hash
    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Actualizar contraseña
    $stmt = $pdo->prepare("UPDATE ecc_users SET password_hash = ? WHERE email = ?");
    $result = $stmt->execute([$newHash, $email]);
    
    if ($result) {
        echo "<h2>✅ Contraseña actualizada exitosamente</h2>";
        echo "<p><strong>Nueva contraseña:</strong> $newPassword</p>";
        echo "<p><strong>Nuevo hash:</strong> " . substr($newHash, 0, 60) . "...</p>";
        
        // Verificar que funciona
        $verified = password_verify($newPassword, $newHash);
        echo "<p><strong>Verificación:</strong> " . ($verified ? "✅ CORRECTA" : "❌ ERROR") . "</p>";
        
        echo "<h3>🎯 Ahora puedes hacer login con:</h3>";
        echo "<ul>";
        echo "<li><strong>Email:</strong> cesarmatelat@gmail.com</li>";
        echo "<li><strong>Password:</strong> Anubis@68</li>";
        echo "</ul>";
        
    } else {
        echo "<h2>❌ Error al actualizar contraseña</h2>";
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Error: " . $e->getMessage() . "</h2>";
}
?>
