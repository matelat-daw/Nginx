<?php
/**
 * API Endpoint: Obtener Compras del Usuario
 * GET /api/auth/my-orders.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight OPTIONS
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
require_once __DIR__ . '/../jwt.php';

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
    
    // Parámetros de paginación y filtros
    $page = (int)($_GET['page'] ?? 1);
    $limit = min((int)($_GET['limit'] ?? 10), 50);
    $offset = ($page - 1) * $limit;
    $status = $_GET['status'] ?? '';
    
    // Construir consulta base
    $whereClause = "WHERE o.buyer_id = ?";
    $params = [$userId];
    
    if ($status && $status !== 'all') {
        $whereClause .= " AND o.status = ?";
        $params[] = $status;
    }
    
    // Contar total de pedidos
    $countSql = "SELECT COUNT(*) as total FROM orders o $whereClause";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $totalOrders = $stmt->fetch()['total'];
    
    // Obtener pedidos con detalles
    $sql = "
        SELECT 
            o.id,
            o.order_number,
            o.status,
            o.payment_status,
            o.payment_method,
            o.subtotal,
            o.shipping_cost,
            o.tax_amount,
            o.total_amount,
            o.delivery_method,
            o.shipping_island,
            o.shipping_city,
            o.estimated_delivery_date,
            o.actual_delivery_date,
            o.buyer_notes,
            o.created_at,
            o.updated_at
        FROM orders o
        $whereClause
        ORDER BY o.created_at DESC
        LIMIT $limit OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
    
    // Obtener items para cada pedido
    foreach ($orders as &$order) {
        $stmt = $pdo->prepare("
            SELECT 
                oi.id,
                oi.product_id,
                oi.product_name,
                oi.product_description,
                oi.unit_price,
                oi.quantity,
                oi.line_total,
                oi.item_status,
                oi.tracking_number,
                oi.delivered_at,
                oi.variant_info,
                p.main_image,
                p.slug as product_slug,
                s.username as seller_username,
                s.full_name as seller_name
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.id
            LEFT JOIN ecc_users s ON oi.seller_id = s.id
            WHERE oi.order_id = ?
            ORDER BY oi.id
        ");
        $stmt->execute([$order['id']]);
        $order['items'] = $stmt->fetchAll();
        
        // Procesar variant_info JSON
        foreach ($order['items'] as &$item) {
            if ($item['variant_info']) {
                $item['variant_info'] = json_decode($item['variant_info'], true);
            }
        }
        
        // Obtener historial de estados
        $stmt = $pdo->prepare("
            SELECT 
                osh.from_status,
                osh.to_status,
                osh.changed_by_role,
                osh.reason,
                osh.notes,
                osh.created_at
            FROM order_status_history osh
            WHERE osh.order_id = ?
            ORDER BY osh.created_at ASC
        ");
        $stmt->execute([$order['id']]);
        $order['status_history'] = $stmt->fetchAll();
    }
    
    $totalPages = ceil($totalOrders / $limit);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'orders' => $orders,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => (int)$totalOrders,
                'items_per_page' => $limit,
                'has_next' => $page < $totalPages,
                'has_prev' => $page > 1
            ]
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error en my-orders: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
}
