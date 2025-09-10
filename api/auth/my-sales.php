<?php
/**
 * API Endpoint: Obtener Ventas del Vendedor
 * GET /api/auth/my-sales.php
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
    $limit = min((int)($_GET['limit'] ?? 20), 50);
    $offset = ($page - 1) * $limit;
    $status = $_GET['status'] ?? '';
    $period = $_GET['period'] ?? 'all'; // all, today, week, month, year
    
    // Construir consulta base
    $whereClause = "WHERE oi.seller_id = ?";
    $params = [$userId];
    
    if ($status && $status !== 'all') {
        $whereClause .= " AND o.status = ?";
        $params[] = $status;
    }
    
    // Filtro por período
    switch ($period) {
        case 'today':
            $whereClause .= " AND DATE(o.created_at) = CURDATE()";
            break;
        case 'week':
            $whereClause .= " AND o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $whereClause .= " AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
        case 'year':
            $whereClause .= " AND o.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            break;
    }
    
    // Contar total de ventas
    $countSql = "
        SELECT COUNT(*) as total 
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        $whereClause
    ";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $totalSales = $stmt->fetch()['total'];
    
    // Obtener ventas con detalles
    $sql = "
        SELECT 
            oi.id,
            oi.order_id,
            oi.product_id,
            oi.product_name,
            oi.unit_price,
            oi.quantity,
            oi.line_total,
            oi.item_status,
            oi.platform_commission_rate,
            oi.platform_commission_amount,
            oi.seller_payout,
            oi.tracking_number,
            oi.delivered_at,
            oi.variant_info,
            o.order_number,
            o.status as order_status,
            o.payment_status,
            o.payment_method,
            o.delivery_method,
            o.shipping_island,
            o.shipping_city,
            o.buyer_notes,
            o.created_at,
            o.updated_at,
            b.username as buyer_username,
            b.full_name as buyer_name,
            b.email as buyer_email,
            p.main_image,
            p.slug as product_slug,
            p.status as product_status
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        LEFT JOIN ecc_users b ON o.buyer_id = b.id
        LEFT JOIN products p ON oi.product_id = p.id
        $whereClause
        ORDER BY o.created_at DESC
        LIMIT $limit OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $sales = $stmt->fetchAll();
    
    // Procesar variant_info JSON
    foreach ($sales as &$sale) {
        if ($sale['variant_info']) {
            $sale['variant_info'] = json_decode($sale['variant_info'], true);
        }
    }
    
    // Estadísticas del período
    $statsSql = "
        SELECT 
            COUNT(*) as total_items_sold,
            SUM(oi.quantity) as total_quantity,
            SUM(oi.line_total) as total_revenue,
            SUM(oi.platform_commission_amount) as total_commission,
            SUM(oi.seller_payout) as total_payout,
            AVG(oi.line_total) as average_sale
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        $whereClause
        AND o.status IN ('paid', 'processing', 'delivered')
    ";
    
    $stmt = $pdo->prepare($statsSql);
    $stmt->execute($params);
    $stats = $stmt->fetch();
    
    // Productos más vendidos en el período
    $topProductsSql = "
        SELECT 
            p.id,
            p.name,
            p.main_image,
            p.slug,
            SUM(oi.quantity) as total_sold,
            SUM(oi.line_total) as total_revenue,
            COUNT(DISTINCT oi.order_id) as order_count
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        JOIN products p ON oi.product_id = p.id
        $whereClause
        AND o.status IN ('paid', 'processing', 'delivered')
        GROUP BY p.id, p.name, p.main_image, p.slug
        ORDER BY total_sold DESC
        LIMIT 5
    ";
    
    $stmt = $pdo->prepare($topProductsSql);
    $stmt->execute($params);
    $topProducts = $stmt->fetchAll();
    
    $totalPages = ceil($totalSales / $limit);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'sales' => $sales,
            'statistics' => [
                'total_items_sold' => (int)$stats['total_items_sold'],
                'total_quantity' => (int)$stats['total_quantity'],
                'total_revenue' => (float)$stats['total_revenue'],
                'total_commission' => (float)$stats['total_commission'],
                'total_payout' => (float)$stats['total_payout'],
                'average_sale' => (float)$stats['average_sale']
            ],
            'top_products' => $topProducts,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => (int)$totalSales,
                'items_per_page' => $limit,
                'has_next' => $page < $totalPages,
                'has_prev' => $page > 1
            ]
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error en my-sales: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
}
