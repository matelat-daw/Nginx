<?php
/**
 * Crear Pedido - VERSIÓN DEBUG
 */

error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar errores en pantalla
ini_set('log_errors', 1);

// Limpiar cualquier output
while (ob_get_level()) {
    ob_end_clean();
}

// Iniciar output buffer limpio
ob_start();

try {
    require_once '../config.php';
    
    header('Access-Control-Allow-Origin: ' . SITE_URL);
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    header('Access-Control-Allow-Credentials: true');
    header('Content-Type: application/json; charset=utf-8');
    
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        ob_end_clean();
        exit();
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        ob_end_clean();
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit();
    }
    
    // Leer datos
    $rawInput = file_get_contents('php://input');
    $orderData = json_decode($rawInput, true);
    
    if (!$orderData) {
        throw new Exception('Datos de pedido inválidos');
    }
    
    // Validar campos requeridos
    $requiredFields = ['orderId', 'items', 'subtotal', 'customerInfo', 'paymentMethod', 'paymentResult'];
    foreach ($requiredFields as $field) {
        if (!isset($orderData[$field])) {
            throw new Exception("Campo requerido faltante: $field");
        }
    }
    
    // Obtener userId
    $userId = null;
    
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
    } elseif (isset($_COOKIE[COOKIE_NAME])) {
        try {
            $jwt = $_COOKIE[COOKIE_NAME];
            $decoded = JWT::decode($jwt, JWT_SECRET);
            if ($decoded && isset($decoded->userId)) {
                $userId = $decoded->userId;
                $_SESSION['user_id'] = $userId;
            }
        } catch (Exception $e) {
            // Log pero continuar
        }
    }
    
    if (!$userId) {
        throw new Exception('Usuario no autenticado. Por favor, inicia sesión nuevamente.');
    }
    
    if ($orderData['customerInfo']['userId'] != $userId) {
        throw new Exception('Usuario no autorizado para crear este pedido');
    }
    
    // TODO: Insertar en DB y enviar email
    // Por ahora, solo responder éxito
    
    ob_end_clean();
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Pedido procesado (modo debug)',
        'orderId' => $orderData['orderId'],
        'userId' => $userId,
        'emailSent' => false,
        'debug' => true
    ]);
    
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al procesar el pedido',
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>
