<?php
/**
 * Script para crear la base de datos y tabla de usuarios
 * Ejecutar una vez para configurar la base de datos
 */

// Configuración de base de datos
$host = 'localhost';
$user = 'root';
$pass = 'Anubis@68';

try {
    // Conectar sin especificar base de datos para poder crearla
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    echo "✅ Conectado al servidor MySQL\n";

    // Leer el archivo SQL
    $sqlFile = __DIR__ . '/../sql/create_users_table.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("Archivo SQL no encontrado: $sqlFile");
    }

    $sql = file_get_contents($sqlFile);
    
    // Ejecutar las consultas SQL
    $queries = explode(';', $sql);
    
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            $pdo->exec($query);
            echo "✅ Ejecutada consulta SQL\n";
        }
    }

    echo "\n🎉 Base de datos y tabla creadas exitosamente!\n";
    echo "Base de datos: users\n";
    echo "Tabla: user\n";

    // Verificar que la tabla se creó correctamente
    $pdo->exec("USE users");
    $stmt = $pdo->query("DESCRIBE user");
    $columns = $stmt->fetchAll();
    
    echo "\n📊 Estructura de la tabla 'user':\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']}: {$column['Type']}\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
