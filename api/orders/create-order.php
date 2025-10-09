<?php
/**
 * Crear Pedido y Enviar Email de Confirmación
 * Endpoint: POST /api/orders/create-order.php
 */

// Desactivar output buffering completamente
while (ob_get_level()) {
    ob_end_clean();
}

// Prevenir cualquier output buffer adicional
ini_set('output_buffering', 'off');
ini_set('zlib.output_compression', 'off');

// Configurar headers primero antes de cualquier output
header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? 'http://localhost'));
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json; charset=utf-8');

// Manejar preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit();
}

// Ahora cargar config.php DESPUÉS de los headers
require_once '../config.php';

try {
    // Leer datos del pedido
    $rawInput = file_get_contents('php://input');
    $orderData = json_decode($rawInput, true);
    
    if (!$orderData) {
        throw new Exception('Datos de pedido inválidos');
    }
    
    // Validar datos requeridos
    $requiredFields = ['orderId', 'items', 'subtotal', 'customerInfo', 'paymentMethod', 'paymentResult'];
    foreach ($requiredFields as $field) {
        if (!isset($orderData[$field])) {
            throw new Exception("Campo requerido faltante: $field");
        }
    }
    
    // Obtener userId desde el JWT o desde la sesión
    $userId = null;
    
    // Primero intentar desde la sesión
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        logMessage('INFO', "Usuario obtenido de sesión: {$userId}");
    }
    
    // Si no hay sesión, intentar validar JWT desde cookie
    if (!$userId && isset($_COOKIE[COOKIE_NAME])) {
        try {
            $jwt = $_COOKIE[COOKIE_NAME];
            $decoded = JWT::decode($jwt, JWT_SECRET);
            
            if ($decoded && isset($decoded->userId)) {
                $userId = $decoded->userId;
                // Establecer en sesión para futuras peticiones
                $_SESSION['user_id'] = $userId;
                logMessage('INFO', "Usuario obtenido de JWT: {$userId}");
            }
        } catch (Exception $e) {
            logMessage('WARNING', "Error decodificando JWT: " . $e->getMessage());
        }
    }
    
    // Validar que tenemos un usuario autenticado
    if (!$userId) {
        throw new Exception('Usuario no autenticado. Por favor, inicia sesión nuevamente.');
    }
    
    // Validar que el userId coincide con el del pedido
    if ($orderData['customerInfo']['userId'] != $userId) {
        throw new Exception('Usuario no autorizado para crear este pedido');
    }
    
    logMessage('INFO', "Procesando pedido {$orderData['orderId']} para usuario ID: {$userId}");
    
    // Iniciar transacción (PDO usa beginTransaction, no begin_transaction)
    $db = getDBConnection();
    $db->beginTransaction();
    
    try {
        // 1. Insertar pedido en la tabla orders (usando PDO)
        $stmt = $db->prepare("
            INSERT INTO orders (
                order_number,
                buyer_id,
                status,
                subtotal,
                total_amount,
                delivery_method,
                payment_method,
                payment_status,
                payment_reference,
                billing_name,
                shipping_phone,
                buyer_notes,
                created_at
            ) VALUES (:order_number, :buyer_id, 'pending', :subtotal, :total_amount, 
                     'shipping', :payment_method, 'paid', :payment_reference, 
                     :billing_name, :shipping_phone, '', NOW())
        ");
        
        $transactionId = $orderData['paymentResult']['transactionId'] ?? null;
        $customerName = $orderData['customerInfo']['name'];
        $customerEmail = $orderData['customerInfo']['email'];
        $customerPhone = $orderData['customerInfo']['phone'] ?? '';
        $paymentMethod = $orderData['paymentMethod'];
        $totalAmount = $orderData['subtotal'];
        
        $stmt->execute([
            ':order_number' => $orderData['orderId'],
            ':buyer_id' => $userId,
            ':subtotal' => $totalAmount,
            ':total_amount' => $totalAmount,
            ':payment_method' => $paymentMethod,
            ':payment_reference' => $transactionId,
            ':billing_name' => $customerName,
            ':shipping_phone' => $customerPhone
        ]);
        
        $orderDbId = $db->lastInsertId();
        
        logMessage('INFO', "Pedido insertado en DB con ID: {$orderDbId}");
        
        // 2. Insertar items del pedido (usando PDO con NULL permitido en FK)
        $stmt = $db->prepare("
            INSERT INTO order_items (
                order_id,
                product_id,
                seller_id,
                product_name,
                unit_price,
                quantity,
                line_total,
                item_status
            ) VALUES (:order_id, :product_id, :seller_id, :product_name, 
                     :unit_price, :quantity, :line_total, 'confirmed')
        ");
        
        foreach ($orderData['items'] as $item) {
            $itemSubtotal = $item['price'] * $item['quantity'];
            
            // Intentar obtener product_id
            $productId = $item['product_id'] ?? $item['id'] ?? null;
            $sellerId = null; // Por defecto NULL hasta que encontremos uno válido
            
            // Si tenemos product_id, intentar validarlo y obtener el seller real
            if ($productId) {
                try {
                    // Verificar si el producto existe
                    $checkStmt = $db->prepare("SELECT id, user_id FROM products WHERE id = :id");
                    $checkStmt->execute([':id' => $productId]);
                    $product = $checkStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($product) {
                        // Producto existe, usar su seller real
                        $sellerId = $product['user_id'];
                        logMessage('INFO', "Producto ID {$productId} encontrado, seller: {$sellerId}");
                    } else {
                        // Producto no existe, buscar por nombre
                        logMessage('WARNING', "Producto ID {$productId} no encontrado, buscando por nombre");
                        $searchStmt = $db->prepare("SELECT id, user_id FROM products WHERE name LIKE :name LIMIT 1");
                        $searchStmt->execute([':name' => '%' . $item['name'] . '%']);
                        $foundProduct = $searchStmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($foundProduct) {
                            $productId = $foundProduct['id'];
                            $sellerId = $foundProduct['user_id'];
                            logMessage('INFO', "Producto encontrado por nombre: ID {$productId}");
                        } else {
                            // No se encontró el producto, usar NULL
                            logMessage('WARNING', "Producto '{$item['name']}' no encontrado en BD, usando NULL");
                            $productId = null;
                            $sellerId = null;
                        }
                    }
                } catch (Exception $e) {
                    logMessage('ERROR', "Error buscando producto: " . $e->getMessage());
                    $productId = null;
                    $sellerId = null;
                }
            }
            
            // Si tenemos sellerId, validar que existe
            if ($sellerId !== null) {
                $checkSellerStmt = $db->prepare("SELECT id FROM users WHERE id = :id");
                $checkSellerStmt->execute([':id' => $sellerId]);
                if (!$checkSellerStmt->fetch()) {
                    logMessage('WARNING', "Seller ID {$sellerId} no existe, usando NULL");
                    $sellerId = null;
                }
            }
            
            // Si no tenemos seller, intentar usar el userId del comprador (si existe en users)
            if ($sellerId === null) {
                $checkUserStmt = $db->prepare("SELECT id FROM users WHERE id = :id");
                $checkUserStmt->execute([':id' => $userId]);
                if ($checkUserStmt->fetch()) {
                    $sellerId = $userId;
                    logMessage('INFO', "Usando userId del comprador como seller: {$userId}");
                } else {
                    logMessage('WARNING', "UserId {$userId} tampoco existe, seller será NULL");
                }
            }
            
            $stmt->execute([
                ':order_id' => $orderDbId,
                ':product_id' => $productId,
                ':seller_id' => $sellerId,
                ':product_name' => $item['name'],
                ':unit_price' => $item['price'],
                ':quantity' => $item['quantity'],
                ':line_total' => $itemSubtotal
            ]);
        }
        
        logMessage('INFO', "Items del pedido insertados: " . count($orderData['items']));
        
        // 3. Enviar email de confirmación
        logMessage('INFO', "Enviando email de confirmación a {$customerEmail}");
        
        $emailResult = sendOrderConfirmationEmail($orderData);
        
        // Commit de la transacción
        $db->commit();
        
        logMessage('INFO', "Pedido {$orderData['orderId']} procesado exitosamente");
        
        // Respuesta exitosa
        echo json_encode([
            'success' => true,
            'message' => 'Pedido creado exitosamente',
            'orderId' => $orderData['orderId'],
            'orderDbId' => $orderDbId,
            'emailSent' => is_bool($emailResult) ? $emailResult : ($emailResult['sent'] ?? false),
            'emailInfo' => is_array($emailResult) ? $emailResult : null
        ]);
        
        exit(); // Terminar ejecución limpiamente
        
    } catch (Exception $e) {
        // Rollback en caso de error (PDO usa rollBack con B mayúscula)
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    logMessage('ERROR', "Error procesando pedido: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al procesar el pedido',
        'error' => DEBUG_MODE ? $e->getMessage() : 'Error interno del servidor'
    ]);
}

// Asegurar que no hay output adicional
exit();
?>
