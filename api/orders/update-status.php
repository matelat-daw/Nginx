<?php
/**
 * API Endpoint: Actualizar Estado del Pedido
 * PUT /api/orders/update-status.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
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
    
    // Obtener datos
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['order_id']) || empty($input['new_status'])) {
        http_response_code(400);
        echo json_encode(['error' => 'ID del pedido y nuevo estado requeridos']);
        exit();
    }
    
    $orderRepository = new OrderRepository($pdo);
    
    // Verificar que el pedido existe y el usuario tiene permisos
    $order = $orderRepository->findById($input['order_id']);
    
    if (!$order) {
        http_response_code(404);
        echo json_encode(['error' => 'Pedido no encontrado']);
        exit();
    }
    
    // Verificar permisos según el nuevo estado
    $newStatus = $input['new_status'];
    $validStatuses = ['pending', 'paid', 'processing', 'ready_pickup', 'shipped', 'delivered', 'cancelled', 'refunded'];
    
    if (!in_array($newStatus, $validStatuses)) {
        http_response_code(400);
        echo json_encode(['error' => 'Estado no válido']);
        exit();
    }
    
    // Lógica de permisos
    $canUpdate = false;
    $reason = $input['reason'] ?? null;
    
    if ($order->buyerId == $userId) {
        // El comprador puede cancelar pedidos pendientes
        $canUpdate = in_array($newStatus, ['cancelled']) && in_array($order->status, ['pending', 'paid']);
    } elseif ($order->sellerId == $userId) {
        // El vendedor puede cambiar ciertos estados
        $canUpdate = in_array($newStatus, ['processing', 'ready_pickup', 'shipped', 'delivered', 'cancelled']);
    }
    
    if (!$canUpdate) {
        http_response_code(403);
        echo json_encode(['error' => 'No tienes permisos para cambiar este estado']);
        exit();
    }
    
    // Validar transiciones de estado válidas
    $validTransitions = [
        'pending' => ['paid', 'cancelled'],
        'paid' => ['processing', 'cancelled'],
        'processing' => ['ready_pickup', 'shipped', 'cancelled'],
        'ready_pickup' => ['delivered', 'cancelled'],
        'shipped' => ['delivered'],
        'delivered' => [], // Estado final
        'cancelled' => ['refunded'], // Solo admin puede hacer refunded
        'refunded' => [] // Estado final
    ];
    
    if (!in_array($newStatus, $validTransitions[$order->status])) {
        http_response_code(400);
        echo json_encode(['error' => 'Transición de estado no válida']);
        exit();
    }
    
    // Actualizar estado
    $result = $orderRepository->updateStatus($input['order_id'], $newStatus, $userId, $reason);
    
    if ($result) {
        // Obtener pedido actualizado
        $updatedOrder = $orderRepository->findById($input['order_id'], true, true);
        
        echo json_encode([
            'success' => true,
            'message' => 'Estado actualizado correctamente',
            'order' => $updatedOrder->toArray(true)
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al actualizar el estado']);
    }
    
} catch (Exception $e) {
    error_log("Error en update status: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
}
