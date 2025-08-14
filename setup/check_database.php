<?php
try {
    $pdo = new PDO('mysql:host=localhost;charset=utf8mb4', 'root', 'Anubis@68');
    $stmt = $pdo->query('SHOW DATABASES');
    
    echo "📊 Bases de datos disponibles:\n";
    while ($row = $stmt->fetch()) {
        echo "- " . $row['Database'] . "\n";
    }
    
    // Verificar si existe la base de datos 'users'
    $stmt = $pdo->query("SHOW DATABASES LIKE 'users'");
    if ($stmt->fetch()) {
        echo "\n✅ La base de datos 'users' existe\n";
        
        // Conectar a la base de datos users y ver la estructura
        $pdo->exec("USE users");
        $stmt = $pdo->query("SHOW TABLES");
        echo "\n📋 Tablas en 'users':\n";
        while ($row = $stmt->fetch()) {
            echo "- " . $row['Tables_in_users'] . "\n";
        }
        
        // Ver estructura de la tabla user
        $stmt = $pdo->query("DESCRIBE user");
        echo "\n🏗️ Estructura de la tabla 'user':\n";
        while ($row = $stmt->fetch()) {
            echo "- {$row['Field']}: {$row['Type']}\n";
        }
    } else {
        echo "\n❌ La base de datos 'users' NO existe\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
