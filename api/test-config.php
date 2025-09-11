<?php
// Test rápido de conexión con la contraseña actual
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://localhost');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    echo json_encode([
        'success' => true,
        'message' => 'Configuración cargada correctamente',
        'config' => [
            'DB_HOST' => DB_HOST,
            'DB_NAME' => DB_NAME,
            'DB_USER' => DB_USER,
            'DB_PASS_LENGTH' => strlen(DB_PASS),
            'DB_PASS_FIRST_3' => substr(DB_PASS, 0, 3) . '...',
            'DB_PORT' => DB_PORT
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
