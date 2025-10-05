<?php
/**
 * API Endpoint: Obtener Productos
 * GET /api/products/list.php
 * Versión 2.0 - Optimizado
 */

require_once __DIR__ . '/../config.php';

// Headers CORS y seguridad
setCorsHeaders();
handlePreflight();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(null, 405, 'Método no permitido');
}

try {
    // Conexión DB centralizada
    $pdo = getDBConnection();
    
    $productRepository = new ProductRepository($pdo);
    
    // Obtener parámetros de consulta
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;
    
    $orderBy = $_GET['order_by'] ?? 'created_at';
    $orderDir = $_GET['order_dir'] ?? 'DESC';
    
    // Construir filtros
    $filters = [];
    
    if (!empty($_GET['category_id'])) {
        $filters['category_id'] = (int)$_GET['category_id'];
    }
    
    if (!empty($_GET['subcategory_id'])) {
        $filters['subcategory_id'] = (int)$_GET['subcategory_id'];
    }
    
    if (!empty($_GET['island'])) {
        $filters['island'] = $_GET['island'];
    }
    
    if (!empty($_GET['city'])) {
        $filters['city'] = $_GET['city'];
    }
    
    if (!empty($_GET['min_price'])) {
        $filters['min_price'] = (float)$_GET['min_price'];
    }
    
    if (!empty($_GET['max_price'])) {
        $filters['max_price'] = (float)$_GET['max_price'];
    }
    
    if (!empty($_GET['condition'])) {
        $filters['condition'] = $_GET['condition'];
    }
    
    if (!empty($_GET['search'])) {
        $filters['search'] = $_GET['search'];
    }
    
    // Filtros booleanos
    if (isset($_GET['is_organic'])) {
        $filters['is_organic'] = $_GET['is_organic'] === '1' || $_GET['is_organic'] === 'true';
    }
    
    if (isset($_GET['is_local'])) {
        $filters['is_local'] = $_GET['is_local'] === '1' || $_GET['is_local'] === 'true';
    }
    
    if (isset($_GET['is_handmade'])) {
        $filters['is_handmade'] = $_GET['is_handmade'] === '1' || $_GET['is_handmade'] === 'true';
    }
    
    if (isset($_GET['shipping_available'])) {
        $filters['shipping_available'] = $_GET['shipping_available'] === '1' || $_GET['shipping_available'] === 'true';
    }
    
    if (isset($_GET['featured'])) {
        $filters['featured'] = $_GET['featured'] === '1' || $_GET['featured'] === 'true';
    }
    
    // Obtener productos
    $products = $productRepository->search($filters, $limit, $offset, $orderBy, $orderDir);
    
    // Convertir a array para JSON
    $productsArray = [];
    foreach ($products as $product) {
        $productsArray[] = $product->toArray();
    }
    
    // Respuesta
    echo json_encode([
        'success' => true,
        'products' => $productsArray,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total_items' => count($productsArray),
            'has_more' => count($productsArray) === $limit
        ],
        'filters_applied' => $filters
    ]);
    
} catch (Exception $e) {
    error_log("Error en list products: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
}
