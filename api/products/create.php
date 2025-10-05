<?php
/**
 * API Endpoint: Crear Producto
 * POST /api/products/create.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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
    
    // Obtener datos del producto
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input)) {
        http_response_code(400);
        echo json_encode(['error' => 'Datos del producto requeridos']);
        exit();
    }
    
    // Agregar el ID del vendedor
    $input['seller_id'] = $sellerId;
    
    // Crear producto
    $product = new Product($input);
    
    // Validar producto
    if (!$product->isValid()) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Datos inválidos',
            'details' => $product->getValidationErrors()
        ]);
        exit();
    }
    
    // Guardar en base de datos
    $productRepository = new ProductRepository($pdo);
    $savedProduct = $productRepository->create($product);
    
    if ($savedProduct) {
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Producto creado exitosamente',
            'product' => $savedProduct->toArray(true) // Incluir datos privados para el propietario
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al crear el producto']);
    }
    
} catch (Exception $e) {
    error_log("Error en create product: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
}
