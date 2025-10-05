<?php
/**
 * API Endpoint: Actualizar Producto del Vendedor
 * PUT /api/products/update.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight OPTIONS
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
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../repositories/ProductRepository.php';

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
        $sellerId = $decoded->userId;
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(['error' => 'Token inválido']);
        exit();
    }
    
    // Obtener datos del input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input) || empty($input['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'ID del producto requerido']);
        exit();
    }
    
    $productId = $input['id'];
    
    // Verificar que el producto pertenece al usuario
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND seller_id = ?");
    $stmt->execute([$productId, $sellerId]);
    $existingProduct = $stmt->fetch();
    
    if (!$existingProduct) {
        http_response_code(404);
        echo json_encode(['error' => 'Producto no encontrado o no tienes permisos para editarlo']);
        exit();
    }
    
    // Preparar datos para actualización
    $updateData = $input;
    $updateData['seller_id'] = $sellerId; // Asegurar que el seller_id no cambie
    unset($updateData['id']); // No actualizar el ID
    
    // Crear objeto producto con los datos actuales y las actualizaciones
    $productData = array_merge($existingProduct, $updateData);
    $product = new Product($productData);
    
    // Validar producto
    if (!$product->isValid()) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Datos inválidos',
            'details' => $product->getValidationErrors()
        ]);
        exit();
    }
    
    // Actualizar en base de datos
    $productRepository = new ProductRepository($pdo);
    $updatedProduct = $productRepository->update($productId, $product);
    
    if ($updatedProduct) {
        // Si se cambió el status a 'active', actualizar published_at
        if (isset($updateData['status']) && $updateData['status'] === 'active' && $existingProduct['status'] !== 'active') {
            $stmt = $pdo->prepare("UPDATE products SET published_at = NOW() WHERE id = ?");
            $stmt->execute([$productId]);
        }
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Producto actualizado exitosamente',
            'product' => $updatedProduct->toArray(true) // Incluir datos privados para el propietario
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al actualizar el producto']);
    }
    
} catch (Exception $e) {
    error_log("Error en update product: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
}
