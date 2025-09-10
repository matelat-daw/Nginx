<?php
/**
 * Script de prueba para la base de datos de productos
 * Ejecutar desde: http://localhost:8080/api/test-products.php
 */

header('Content-Type: application/json');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/models/Product.php';
require_once __DIR__ . '/repositories/ProductRepository.php';

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
    
    $results = [];
    
    // 1. Verificar si las tablas existen
    $results['tables_check'] = [];
    
    $tables = ['products', 'product_categories', 'product_attributes', 'product_variants', 'product_reviews', 'product_favorites'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            $exists = $stmt->rowCount() > 0;
            $results['tables_check'][$table] = $exists ? '✅ Existe' : '❌ No existe';
        } catch (Exception $e) {
            $results['tables_check'][$table] = '❌ Error: ' . $e->getMessage();
        }
    }
    
    // 2. Verificar categorías
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM product_categories");
        $categoryCount = $stmt->fetch()['count'];
        $results['categories'] = "✅ $categoryCount categorías encontradas";
        
        // Mostrar algunas categorías
        $stmt = $pdo->query("SELECT name, slug FROM product_categories WHERE parent_id IS NULL LIMIT 5");
        $mainCategories = $stmt->fetchAll();
        $results['sample_categories'] = $mainCategories;
        
    } catch (Exception $e) {
        $results['categories'] = '❌ Error: ' . $e->getMessage();
    }
    
    // 3. Probar creación de producto de prueba
    try {
        $productRepository = new ProductRepository($pdo);
        
        // Crear producto de prueba
        $testProduct = new Product([
            'seller_id' => 1, // Asumiendo que existe un usuario con ID 1
            'name' => 'Producto de Prueba - Miel de La Palma',
            'description' => 'Deliciosa miel artesanal producida en los bosques de laurisilva de La Palma. 100% natural y sin aditivos.',
            'short_description' => 'Miel artesanal de La Palma',
            'price' => 15.50,
            'stock_quantity' => 10,
            'category_id' => 1, // Alimentos y Bebidas
            'origin' => 'La Palma, Canarias',
            'is_organic' => true,
            'is_local' => true,
            'pickup_island' => 'La Palma',
            'pickup_city' => 'Santa Cruz de La Palma',
            'status' => 'active',
            'tags' => 'miel, artesanal, canarias, la palma, natural',
            'search_keywords' => 'miel artesanal canaria natural'
        ]);
        
        if ($testProduct->isValid()) {
            $results['test_product_validation'] = '✅ Validación correcta';
            
            // Intentar guardar (comentado para no llenar la DB en pruebas)
            // $savedProduct = $productRepository->create($testProduct);
            // $results['test_product_creation'] = $savedProduct ? '✅ Producto creado' : '❌ Error al crear';
            
            $results['test_product_creation'] = '⚠️ Creación deshabilitada en pruebas';
            $results['test_product_data'] = $testProduct->toArray();
            
        } else {
            $results['test_product_validation'] = '❌ Errores de validación: ' . implode(', ', $testProduct->getValidationErrors());
        }
        
    } catch (Exception $e) {
        $results['test_product'] = '❌ Error: ' . $e->getMessage();
    }
    
    // 4. Probar búsqueda
    try {
        $productRepository = new ProductRepository($pdo);
        
        // Búsqueda vacía para obtener productos activos
        $products = $productRepository->search(['featured' => false], 5);
        $results['search_test'] = "✅ Búsqueda ejecutada, " . count($products) . " productos encontrados";
        
        if (count($products) > 0) {
            $results['sample_products'] = array_map(function($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'price' => $p->price,
                    'status' => $p->status
                ];
            }, $products);
        }
        
    } catch (Exception $e) {
        $results['search_test'] = '❌ Error: ' . $e->getMessage();
    }
    
    // 5. Verificar estructura de tabla products
    try {
        $stmt = $pdo->query("DESCRIBE products");
        $columns = $stmt->fetchAll();
        $results['products_structure'] = "✅ Tabla products tiene " . count($columns) . " columnas";
        $results['products_columns'] = array_column($columns, 'Field');
        
    } catch (Exception $e) {
        $results['products_structure'] = '❌ Error: ' . $e->getMessage();
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Pruebas de productos completadas',
        'timestamp' => date('Y-m-d H:i:s'),
        'results' => $results
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error en las pruebas: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
}
