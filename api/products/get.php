<?php
/**
 * API Endpoint: Obtener Producto por ID o Slug
 * GET /api/products/get.php?id=123 o GET /api/products/get.php?slug=producto-ejemplo
 * Versión 2.0 - Optimizado
 */

require_once __DIR__ . '/../config.php';

setCorsHeaders();
handlePreflight();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(null, 405, 'Método no permitido');
}

try {
    // Conexión DB centralizada
    $pdo = getDBConnection();
    
    $productRepository = new ProductRepository($pdo);
    
    // Obtener parámetros
    $id = $_GET['id'] ?? null;
    $slug = $_GET['slug'] ?? null;
    $includeSeller = isset($_GET['include_seller']) && $_GET['include_seller'] === 'true';
    
    if (empty($id) && empty($slug)) {
        http_response_code(400);
        echo json_encode(['error' => 'ID o slug del producto requerido']);
        exit();
    }
    
    // Buscar producto
    $product = null;
    if (!empty($id)) {
        $product = $productRepository->findById($id, $includeSeller);
    } elseif (!empty($slug)) {
        $product = $productRepository->findBySlug($slug, $includeSeller);
    }
    
    if (!$product) {
        http_response_code(404);
        echo json_encode(['error' => 'Producto no encontrado']);
        exit();
    }
    
    // Verificar si el usuario es el propietario (para datos privados)
    $isOwner = false;
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    if (!empty($authHeader) && str_starts_with($authHeader, 'Bearer ')) {
        $token = substr($authHeader, 7);
        try {
            $decoded = JWT::decode($token, JWT_SECRET);
            $userId = $decoded->userId;
            $isOwner = ($userId == $product->sellerId);
        } catch (Exception $e) {
            // Token inválido, continuar sin datos privados
        }
    }
    
    echo json_encode([
        'success' => true,
        'product' => $product->toArray($isOwner)
    ]);
    
} catch (Exception $e) {
    error_log("Error en get product: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
}
