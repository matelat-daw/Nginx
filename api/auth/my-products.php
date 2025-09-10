<?php
/**
 * API Endpoint: Obtener Productos del Vendedor
 * GET /api/auth/my-products.php
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
    $limit = min((int)($_GET['limit'] ?? 12), 50);
    $offset = ($page - 1) * $limit;
    $status = $_GET['status'] ?? '';
    $category = $_GET['category'] ?? '';
    $search = $_GET['search'] ?? '';
    
    // Construir consulta base
    $whereClause = "WHERE p.seller_id = ?";
    $params = [$userId];
    
    if ($status && $status !== 'all') {
        $whereClause .= " AND p.status = ?";
        $params[] = $status;
    }
    
    if ($category && $category !== 'all') {
        $whereClause .= " AND p.category_id = ?";
        $params[] = $category;
    }
    
    if ($search) {
        $whereClause .= " AND (p.name LIKE ? OR p.description LIKE ? OR p.tags LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    // Contar total de productos
    $countSql = "SELECT COUNT(*) as total FROM products p $whereClause";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $totalProducts = $stmt->fetch()['total'];
    
    // Obtener productos
    $sql = "
        SELECT 
            p.id,
            p.name,
            p.description,
            p.short_description,
            p.price,
            p.original_price,
            p.stock_quantity,
            p.stock_alert_level,
            p.unlimited_stock,
            p.category_id,
            p.status,
            p.is_featured,
            p.slug,
            p.main_image,
            p.views_count,
            p.favorites_count,
            p.sales_count,
            p.rating_average,
            p.rating_count,
            p.created_at,
            p.updated_at,
            p.published_at,
            pc.name as category_name,
            pc.slug as category_slug
        FROM products p
        LEFT JOIN product_categories pc ON p.category_id = pc.id
        $whereClause
        ORDER BY p.updated_at DESC
        LIMIT $limit OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
    
    // Para cada producto, obtener estadísticas adicionales
    foreach ($products as &$product) {
        // Ventas recientes (últimos 30 días)
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as recent_orders,
                SUM(oi.quantity) as recent_quantity,
                SUM(oi.line_total) as recent_revenue
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            WHERE oi.product_id = ? 
            AND o.status IN ('paid', 'processing', 'delivered')
            AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stmt->execute([$product['id']]);
        $recentStats = $stmt->fetch();
        
        $product['recent_stats'] = [
            'orders' => (int)$recentStats['recent_orders'],
            'quantity' => (int)$recentStats['recent_quantity'],
            'revenue' => (float)$recentStats['recent_revenue']
        ];
        
        // Estado del stock
        $product['stock_status'] = 'ok';
        if (!$product['unlimited_stock']) {
            if ($product['stock_quantity'] <= 0) {
                $product['stock_status'] = 'out_of_stock';
            } elseif ($product['stock_quantity'] <= $product['stock_alert_level']) {
                $product['stock_status'] = 'low_stock';
            }
        }
        
        // Número de variantes
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as variant_count
            FROM product_variants pv
            WHERE pv.product_id = ? AND pv.is_active = 1
        ");
        $stmt->execute([$product['id']]);
        $product['variant_count'] = (int)$stmt->fetch()['variant_count'];
        
        // Número de reseñas pendientes de moderación
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as pending_reviews
            FROM product_reviews pr
            WHERE pr.product_id = ? AND pr.is_approved = 0
        ");
        $stmt->execute([$product['id']]);
        $product['pending_reviews'] = (int)$stmt->fetch()['pending_reviews'];
    }
    
    // Estadísticas generales del vendedor
    $generalStatsSql = "
        SELECT 
            COUNT(*) as total_products,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_products,
            SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_products,
            SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_products,
            SUM(sales_count) as total_sales,
            SUM(views_count) as total_views,
            AVG(rating_average) as average_rating
        FROM products p
        WHERE p.seller_id = ?
    ";
    
    $stmt = $pdo->prepare($generalStatsSql);
    $stmt->execute([$userId]);
    $generalStats = $stmt->fetch();
    
    $totalPages = ceil($totalProducts / $limit);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'products' => $products,
            'general_stats' => [
                'total_products' => (int)$generalStats['total_products'],
                'active_products' => (int)$generalStats['active_products'],
                'draft_products' => (int)$generalStats['draft_products'],
                'inactive_products' => (int)$generalStats['inactive_products'],
                'total_sales' => (int)$generalStats['total_sales'],
                'total_views' => (int)$generalStats['total_views'],
                'average_rating' => (float)$generalStats['average_rating']
            ],
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => (int)$totalProducts,
                'items_per_page' => $limit,
                'has_next' => $page < $totalPages,
                'has_prev' => $page > 1
            ]
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error en my-products: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
}
