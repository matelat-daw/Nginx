<?php
// Script de verificación de user_type
require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "<h1>Verificación de campo user_type</h1>";
    
    // 1. Verificar estructura de la tabla
    echo "<h2>1. Estructura de la tabla users:</h2>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Default</th></tr>";
    $stmt = $pdo->query("DESCRIBE users");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $highlight = ($row['Field'] === 'user_type') ? "style='background-color: #ffff00;'" : "";
        echo "<tr $highlight>";
        echo "<td><strong>{$row['Field']}</strong></td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 2. Verificar datos actuales
    echo "<h2>2. Usuarios actuales (primeros 5):</h2>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>ID</th><th>Email</th><th>Nombre</th><th>user_type</th><th>updated_at</th></tr>";
    $stmt = $pdo->query("SELECT id, email, first_name, user_type, updated_at FROM users LIMIT 5");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['email']}</td>";
        echo "<td>{$row['first_name']}</td>";
        echo "<td><strong>" . ($row['user_type'] ?? 'NULL') . "</strong></td>";
        echo "<td>{$row['updated_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 3. Probar UPDATE directo
    echo "<h2>3. Test de UPDATE directo:</h2>";
    
    // Obtener el primer usuario
    $stmt = $pdo->query("SELECT id, user_type FROM users LIMIT 1");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "<p>Usuario seleccionado: ID = {$user['id']}, user_type actual = '{$user['user_type']}'</p>";
        
        $newType = ($user['user_type'] === 'particular') ? 'empresa' : 'particular';
        echo "<p>Intentando cambiar a: '$newType'</p>";
        
        $stmt = $pdo->prepare("UPDATE users SET user_type = ?, updated_at = NOW() WHERE id = ?");
        $result = $stmt->execute([$newType, $user['id']]);
        $rowCount = $stmt->rowCount();
        
        echo "<p>Resultado del UPDATE: " . ($result ? 'ÉXITO' : 'FALLÓ') . "</p>";
        echo "<p>Filas afectadas: $rowCount</p>";
        
        // Verificar el cambio
        $stmt = $pdo->prepare("SELECT id, user_type, updated_at FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);
        $userAfter = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<p>Valor después del UPDATE: '{$userAfter['user_type']}'</p>";
        echo "<p>updated_at: {$userAfter['updated_at']}</p>";
        
        if ($userAfter['user_type'] === $newType) {
            echo "<p style='color: green; font-weight: bold;'>✅ El UPDATE funcionó correctamente</p>";
        } else {
            echo "<p style='color: red; font-weight: bold;'>❌ El UPDATE NO funcionó</p>";
        }
        
        // Revertir el cambio
        $stmt = $pdo->prepare("UPDATE users SET user_type = ? WHERE id = ?");
        $stmt->execute([$user['user_type'], $user['id']]);
        echo "<p style='color: blue;'>Cambio revertido</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
