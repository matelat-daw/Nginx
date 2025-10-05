<?php
// Script para corregir el campo user_type
require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "<h1>🔧 Corrección del campo user_type</h1>";
    
    // 1. Ver tipo actual
    echo "<h2>1. Tipo de campo actual:</h2>";
    $stmt = $pdo->query("
        SELECT COLUMN_TYPE, COLUMN_DEFAULT
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = '" . DB_NAME . "'
        AND TABLE_NAME = 'users' 
        AND COLUMN_NAME = 'user_type'
    ");
    $columnInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($columnInfo);
    echo "</pre>";
    
    // 2. Cambiar ENUM a VARCHAR
    echo "<h2>2. Cambiando ENUM a VARCHAR(50)...</h2>";
    try {
        $pdo->exec("ALTER TABLE users MODIFY COLUMN user_type VARCHAR(50) DEFAULT 'particular'");
        echo "<p style='color: green;'>✅ Campo modificado exitosamente a VARCHAR(50)</p>";
    } catch (PDOException $e) {
        echo "<p style='color: orange;'>⚠️ " . $e->getMessage() . "</p>";
    }
    
    // 3. Actualizar valores existentes
    echo "<h2>3. Actualizando valores existentes...</h2>";
    
    // Contar registros antes
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE user_type IN ('individual', 'business', 'organization', '', 'NULL') OR user_type IS NULL");
    $beforeCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "<p>Registros a actualizar: $beforeCount</p>";
    
    // Actualizar de inglés a español
    $updates = [
        "UPDATE users SET user_type = 'particular' WHERE user_type = 'individual' OR user_type = '' OR user_type IS NULL",
        "UPDATE users SET user_type = 'empresa' WHERE user_type = 'business'",
        "UPDATE users SET user_type = 'organizacion' WHERE user_type = 'organization'"
    ];
    
    foreach ($updates as $sql) {
        $stmt = $pdo->exec($sql);
        echo "<p>✅ Ejecutado: $sql (Filas afectadas: $stmt)</p>";
    }
    
    // 4. Verificar resultados
    echo "<h2>4. Usuarios actualizados:</h2>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>ID</th><th>Email</th><th>Nombre</th><th>user_type</th><th>updated_at</th></tr>";
    $stmt = $pdo->query("SELECT id, email, first_name, user_type, updated_at FROM users ORDER BY id");
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
    
    // 5. Verificar el nuevo tipo de campo
    echo "<h2>5. Tipo de campo después de los cambios:</h2>";
    $stmt = $pdo->query("
        SELECT COLUMN_TYPE, COLUMN_DEFAULT
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = '" . DB_NAME . "'
        AND TABLE_NAME = 'users' 
        AND COLUMN_NAME = 'user_type'
    ");
    $columnInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($columnInfo);
    echo "</pre>";
    
    echo "<h2 style='color: green;'>✅ Corrección completada exitosamente</h2>";
    echo "<p><a href='/api/verify-user-type.php'>🔍 Verificar cambios</a></p>";
    echo "<p><a href='/'>🏠 Volver a la aplicación</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
