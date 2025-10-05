<?php
/**
 * API Endpoint: Obtener Pedido por ID
 * GET /api/orders/get.php?id=123
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit();
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../repositories/OrderRepository.php';

try {
    // Conectar a la base de datos
    $pdo = getDBConnection();
    
    // Verificar autenticación
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
        http_response_code(401);
        echo json_encode(['error' => 'Token de autorización requerido']);
        exit();
    }
    
    $token = substr($authHeader, 7);
    
    try {
        $decoded = JWT::decode($token, JWT_SECRET);
        $userId = $decoded->userId;
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(['error' => 'Token inválido']);
        exit();
    }
    
    // Obtener parámetros
    $id = $_GET['id'] ?? null;
    $orderNumber = $_GET['order_number'] ?? null;
    
    if (empty($id) && empty($orderNumber)) {
        http_response_code(400);
        echo json_encode(['error' => 'ID o número de pedido requerido']);
        exit();
    }
    
    $orderRepository = new OrderRepository($pdo);
    
    // Buscar pedido
    $order = null;
    if (!empty($id)) {
        $order = $orderRepository->findById($id, true, true);
    } elseif (!empty($orderNumber)) {
        $order = $orderRepository->findByOrderNumber($orderNumber, true, true);
    }
    
    if (!$order) {
        http_response_code(404);
        echo json_encode(['error' => 'Pedido no encontrado']);
        exit();
    }
    
    // Verificar que el usuario tiene acceso al pedido
    if ($order->buyerId != $userId && $order->sellerId != $userId) {
        // Verificar si es admin (esto dependería de tu sistema de roles)
        http_response_code(403);
        echo json_encode(['error' => 'No tienes acceso a este pedido']);
        exit();
    }
    
    echo json_encode([
        'success' => true,
        'order' => $order->toArray(true)
    ]);
    
} catch (Exception $e) {
    error_log("Error en get order: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
}
