<?php
/**
 * Script para crear la tabla ecc_users en la base de datos users existente
 */

// Configuración de base de datos
$host = 'localhost';
$user = 'root';
$pass = 'Anubis@68';

try {
    // Conectar a la base de datos users
    $pdo = new PDO("mysql:host=$host;dbname=users;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    echo "✅ Conectado a la base de datos 'users'\n";

    // Leer el archivo SQL
    $sqlFile = __DIR__ . '/../sql/create_ecc_users_table.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("Archivo SQL no encontrado: $sqlFile");
    }

    $sql = file_get_contents($sqlFile);
    
    // Ejecutar la consulta SQL
    $pdo->exec($sql);
    echo "✅ Tabla 'ecc_users' creada exitosamente\n";

    // Verificar que la tabla se creó correctamente
    $stmt = $pdo->query("DESCRIBE ecc_users");
    $columns = $stmt->fetchAll();
    
    echo "\n📊 Estructura de la tabla 'ecc_users':\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']}: {$column['Type']}\n";
    }

    echo "\n🎉 ¡Configuración completada!\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
