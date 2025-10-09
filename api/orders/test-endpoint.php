<?php
// Test simple del endpoint
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "TEST 1: Archivo cargado correctamente\n";

// Limpiar buffers
if (ob_get_level()) {
    ob_end_clean();
}

echo "TEST 2: Buffers limpiados\n";

require_once '../config.php';

echo "TEST 3: Config cargado\n";

header('Content-Type: application/json');

$testResponse = [
    'success' => true,
    'message' => 'Endpoint funciona correctamente',
    'session_exists' => isset($_SESSION),
    'cookie_exists' => isset($_COOKIE[COOKIE_NAME]),
    'user_id_in_session' => $_SESSION['user_id'] ?? 'no existe'
];

echo json_encode($testResponse, JSON_PRETTY_PRINT);
?>
