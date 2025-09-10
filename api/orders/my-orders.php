<?php
/**
 * API Endpoint: Obtener Pedidos del Usuario
 * GET /api/orders/my-orders.php
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
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
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
        $decoded = JWT::decode($token, new Key(JWT_SECRET, 'HS256'));
        $userId = $decoded->userId;
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(['error' => 'Token inválido']);
        exit();
    }
    
    $orderRepository = new OrderRepository($pdo);
    
    // Parámetros de consulta
    $userType = $_GET['type'] ?? 'buyer'; // 'buyer' o 'seller'
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;
    $status = $_GET['status'] ?? null;
    $includeItems = isset($_GET['include_items']) && $_GET['include_items'] === 'true';
    
    // Obtener pedidos
    $orders = $orderRepository->findByUser($userId, $userType, $limit, $offset, $status);
    
    // Cargar items si se solicita
    if ($includeItems) {
        foreach ($orders as $order) {
            $order->items = $orderRepository->getOrderItems($order->id);
        }
    }
    
    // Convertir a array
    $ordersArray = [];
    foreach ($orders as $order) {
        $ordersArray[] = $order->toArray($includeItems);
    }
    
    // Obtener estadísticas del usuario
    $stats = null;
    if ($userType === 'seller') {
        $stats = $orderRepository->getSalesStats($userId);
    }
    
    echo json_encode([
        'success' => true,
        'orders' => $ordersArray,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total_items' => count($ordersArray),
            'has_more' => count($ordersArray) === $limit
        ],
        'user_type' => $userType,
        'stats' => $stats
    ]);
    
} catch (Exception $e) {
    error_log("Error en my orders: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
}
