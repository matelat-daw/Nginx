<?php
/**
 * API Endpoint: Obtener Categorías de Productos
 * GET /api/products/categories.php
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
require_once __DIR__ . '/../repositories/ProductRepository.php';

try {
    // Conectar a la base de datos
    $pdo = getDBConnection();
    
    $productRepository = new ProductRepository($pdo);
    
    // Obtener parámetro para subcategorías
    $parentId = $_GET['parent_id'] ?? null;
    $includeSubcategories = $_GET['include_subcategories'] ?? 'false';
    
    if ($parentId !== null) {
        $parentId = (int)$parentId;
    }
    
    // Obtener categorías
    $categories = $productRepository->getCategories($parentId);
    
    // Si se solicita incluir subcategorías, cargarlas para cada categoría principal
    if ($includeSubcategories === 'true' && $parentId === null) {
        foreach ($categories as &$category) {
            $category['subcategories'] = $productRepository->getCategories($category['id']);
        }
    }
    
    echo json_encode([
        'success' => true,
        'categories' => $categories
    ]);
    
} catch (Exception $e) {
    error_log("Error en get categories: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
}
