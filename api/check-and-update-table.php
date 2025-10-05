<?php
/**
 * Script para verificar y actualizar la estructura de la tabla users
 * Ejecutar este script una vez para asegurar que todos los campos necesarios existen
 */

require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $results = [];
    $errors = [];

    // Verificar y agregar campo user_type si no existe
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'user_type'");
    if ($stmt->rowCount() === 0) {
        try {
            $pdo->exec("ALTER TABLE users ADD COLUMN user_type VARCHAR(50) DEFAULT 'particular' AFTER city");
            $results[] = "✅ Campo user_type agregado exitosamente";
            
            // Actualizar usuarios existentes
            $pdo->exec("UPDATE users SET user_type = 'particular' WHERE user_type IS NULL OR user_type = ''");
            $results[] = "✅ Usuarios existentes actualizados con user_type='particular'";
        } catch (Exception $e) {
            $errors[] = "❌ Error al agregar user_type: " . $e->getMessage();
        }
    } else {
        $results[] = "ℹ️ Campo user_type ya existe";
    }

    // Verificar si existe email_confirmed (nombre antiguo)
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'email_confirmed'");
    if ($stmt->rowCount() > 0) {
        try {
            $pdo->exec("ALTER TABLE users CHANGE email_confirmed email_verified TINYINT(1) DEFAULT 0");
            $results[] = "✅ Campo renombrado: email_confirmed → email_verified";
        } catch (Exception $e) {
            $errors[] = "❌ Error al renombrar email_confirmed: " . $e->getMessage();
        }
    } else {
        // Verificar si email_verified existe
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'email_verified'");
        if ($stmt->rowCount() > 0) {
            $results[] = "ℹ️ Campo email_verified ya existe";
        } else {
            try {
                $pdo->exec("ALTER TABLE users ADD COLUMN email_verified TINYINT(1) DEFAULT 0 AFTER user_type");
                $results[] = "✅ Campo email_verified agregado";
            } catch (Exception $e) {
                $errors[] = "❌ Error al agregar email_verified: " . $e->getMessage();
            }
        }
    }

    // Obtener estructura actual de la tabla
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Verificar usuarios de ejemplo
    $stmt = $pdo->query("SELECT id, email, user_type, email_verified FROM users LIMIT 5");
    $sampleUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => count($errors) === 0,
        'results' => $results,
        'errors' => $errors,
        'columns' => $columns,
        'sampleUsers' => $sampleUsers
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos',
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
