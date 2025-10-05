<?php
/**
 * API Endpoint: Eliminar Producto del Vendedor
 * DELETE /api/products/delete.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
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
        $sellerId = $decoded->userId;
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(['error' => 'Token inválido']);
        exit();
    }
    
    // Obtener ID del producto
    $productId = $_GET['id'] ?? '';
    
    if (empty($productId)) {
        http_response_code(400);
        echo json_encode(['error' => 'ID del producto requerido']);
        exit();
    }
    
    // Verificar que el producto pertenece al usuario
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND seller_id = ?");
    $stmt->execute([$productId, $sellerId]);
    $product = $stmt->fetch();
    
    if (!$product) {
        http_response_code(404);
        echo json_encode(['error' => 'Producto no encontrado o no tienes permisos para eliminarlo']);
        exit();
    }
    
    // Verificar si hay ventas asociadas al producto
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as order_count 
        FROM order_items oi 
        JOIN orders o ON oi.order_id = o.id 
        WHERE oi.product_id = ? 
        AND o.status NOT IN ('cancelled', 'refunded')
    ");
    $stmt->execute([$productId]);
    $orderCount = $stmt->fetch()['order_count'];
    
    if ($orderCount > 0) {
        // No eliminar completamente, solo marcar como inactivo
        $stmt = $pdo->prepare("UPDATE products SET status = 'inactive' WHERE id = ?");
        $success = $stmt->execute([$productId]);
        
        if ($success) {
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Producto marcado como inactivo debido a ventas existentes',
                'action' => 'deactivated'
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al desactivar el producto']);
        }
    } else {
        // Eliminar completamente el producto
        try {
            $pdo->beginTransaction();
            
            // Eliminar imágenes del producto si existen
            if ($product['main_image']) {
                $imagePath = __DIR__ . '/../uploads/' . basename($product['main_image']);
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
            
            if ($product['gallery_images']) {
                $galleryImages = json_decode($product['gallery_images'], true);
                if (is_array($galleryImages)) {
                    foreach ($galleryImages as $imageUrl) {
                        $imagePath = __DIR__ . '/../uploads/' . basename($imageUrl);
                        if (file_exists($imagePath)) {
                            unlink($imagePath);
                        }
                    }
                }
            }
            
            // Las tablas relacionadas se eliminan automáticamente por CASCADE
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $success = $stmt->execute([$productId]);
            
            if ($success) {
                $pdo->commit();
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'message' => 'Producto eliminado exitosamente',
                    'action' => 'deleted'
                ]);
            } else {
                $pdo->rollBack();
                http_response_code(500);
                echo json_encode(['error' => 'Error al eliminar el producto']);
            }
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
    
} catch (Exception $e) {
    error_log("Error en delete product: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
}
