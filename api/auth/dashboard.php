<?php
/**
 * API Endpoint: Dashboard del Usuario
 * GET /api/auth/dashboard.php
 * Proporciona datos del dashboard según el tipo de usuario
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
    
    // Obtener información del usuario
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'Usuario no encontrado']);
        exit();
    }
    
    $response = [
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'full_name' => $user['full_name'],
            'phone' => $user['phone'],
            'address' => $user['address'],
            'city' => $user['city'],
            'island' => $user['island'],
            'postal_code' => $user['postal_code'],
            'profile_image' => $user['profile_image'],
            'created_at' => $user['created_at']
        ]
    ];
    
    // Estadísticas como comprador
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_orders,
            SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as completed_orders,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
            COALESCE(SUM(CASE WHEN status = 'delivered' THEN total_amount ELSE 0 END), 0) as total_spent
        FROM orders 
        WHERE buyer_id = ?
    ");
    $stmt->execute([$userId]);
    $buyerStats = $stmt->fetch();
    
    // Estadísticas como vendedor
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT p.id) as total_products,
            SUM(CASE WHEN p.status = 'active' THEN 1 ELSE 0 END) as active_products,
            COALESCE(SUM(p.sales_count), 0) as total_sales,
            COALESCE(SUM(p.views_count), 0) as total_views
        FROM products p 
        WHERE p.seller_id = ?
    ");
    $stmt->execute([$userId]);
    $sellerStats = $stmt->fetch();
    
    // Ventas recientes (últimos 30 días)
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as recent_sales,
            COALESCE(SUM(oi.line_total), 0) as recent_revenue
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        WHERE oi.seller_id = ? 
        AND o.status IN ('paid', 'processing', 'delivered')
        AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $stmt->execute([$userId]);
    $recentSales = $stmt->fetch();
    
    // Productos favoritos del usuario
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as favorite_products
        FROM product_favorites pf
        WHERE pf.user_id = ?
    ");
    $stmt->execute([$userId]);
    $favoriteCount = $stmt->fetch();
    
    $response['buyer_stats'] = [
        'total_orders' => (int)$buyerStats['total_orders'],
        'completed_orders' => (int)$buyerStats['completed_orders'],
        'pending_orders' => (int)$buyerStats['pending_orders'],
        'total_spent' => (float)$buyerStats['total_spent'],
        'favorite_products' => (int)$favoriteCount['favorite_products']
    ];
    
    $response['seller_stats'] = [
        'total_products' => (int)$sellerStats['total_products'],
        'active_products' => (int)$sellerStats['active_products'],
        'total_sales' => (int)$sellerStats['total_sales'],
        'total_views' => (int)$sellerStats['total_views'],
        'recent_sales' => (int)$recentSales['recent_sales'],
        'recent_revenue' => (float)$recentSales['recent_revenue']
    ];
    
    // Últimas compras (como comprador)
    $stmt = $pdo->prepare("
        SELECT 
            o.id,
            o.order_number,
            o.status,
            o.total_amount,
            o.created_at,
            COUNT(oi.id) as item_count
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.buyer_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $response['recent_purchases'] = $stmt->fetchAll();
    
    // Últimas ventas (como vendedor)
    $stmt = $pdo->prepare("
        SELECT 
            o.id,
            o.order_number,
            o.status,
            oi.line_total,
            oi.quantity,
            oi.product_name,
            o.created_at
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        WHERE oi.seller_id = ?
        ORDER BY o.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $response['recent_sales'] = $stmt->fetchAll();
    
    // Productos del vendedor que necesitan atención
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.name,
            p.stock_quantity,
            p.stock_alert_level,
            p.status
        FROM products p
        WHERE p.seller_id = ?
        AND (
            p.stock_quantity <= p.stock_alert_level
            OR p.status = 'draft'
        )
        ORDER BY p.stock_quantity ASC
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $response['products_attention'] = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => $response
    ]);
    
} catch (Exception $e) {
    error_log("Error en dashboard: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
}
