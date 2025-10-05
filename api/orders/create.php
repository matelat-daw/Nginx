<?php
/**
 * API Endpoint: Crear Pedido
 * POST /api/orders/create.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

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
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/OrderItem.php';
require_once __DIR__ . '/../repositories/OrderRepository.php';
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
        $buyerId = $decoded->userId;
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(['error' => 'Token inválido']);
        exit();
    }
    
    // Obtener datos del pedido
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input)) {
        http_response_code(400);
        echo json_encode(['error' => 'Datos del pedido requeridos']);
        exit();
    }
    
    // Validar que hay items
    if (empty($input['items']) || !is_array($input['items'])) {
        http_response_code(400);
        echo json_encode(['error' => 'El pedido debe incluir al menos un artículo']);
        exit();
    }
    
    $orderRepository = new OrderRepository($pdo);
    $productRepository = new ProductRepository($pdo);
    
    // Validar cupón si se proporciona
    $couponDiscount = 0;
    if (!empty($input['coupon_code'])) {
        $couponValidation = $orderRepository->validateCoupon(
            $input['coupon_code'], 
            $input['subtotal'] ?? 0, 
            $buyerId
        );
        
        if (!$couponValidation['valid']) {
            http_response_code(400);
            echo json_encode(['error' => $couponValidation['error']]);
            exit();
        }
        
        $coupon = $couponValidation['coupon'];
        if ($coupon['discount_type'] === 'percentage') {
            $couponDiscount = ($input['subtotal'] * $coupon['discount_value']) / 100;
        } elseif ($coupon['discount_type'] === 'fixed_amount') {
            $couponDiscount = $coupon['discount_value'];
        }
    }
    
    // Crear pedido
    $orderData = $input;
    $orderData['buyer_id'] = $buyerId;
    $orderData['coupon_discount'] = $couponDiscount;
    $orderData['status'] = 'pending';
    $orderData['payment_status'] = 'pending';
    
    $order = new Order($orderData);
    $order->calculateTotal();
    
    // Procesar items del pedido
    $totalCalculated = 0;
    foreach ($input['items'] as $itemData) {
        // Obtener producto para validar precio y stock
        $product = $productRepository->findById($itemData['product_id']);
        
        if (!$product) {
            http_response_code(400);
            echo json_encode(['error' => 'Producto no encontrado: ' . $itemData['product_id']]);
            exit();
        }
        
        if (!$product->isAvailable()) {
            http_response_code(400);
            echo json_encode(['error' => 'Producto no disponible: ' . $product->name]);
            exit();
        }
        
        if (!$product->unlimitedStock && $product->stockQuantity < $itemData['quantity']) {
            http_response_code(400);
            echo json_encode(['error' => 'Stock insuficiente para: ' . $product->name]);
            exit();
        }
        
        // Crear item del pedido
        $orderItem = new OrderItem([
            'product_id' => $product->id,
            'seller_id' => $product->sellerId,
            'product_name' => $product->name,
            'product_description' => $product->shortDescription,
            'unit_price' => $product->price,
            'original_price' => $product->originalPrice,
            'quantity' => $itemData['quantity'],
            'variant_info' => $itemData['variant_info'] ?? null,
            'platform_commission_rate' => 5.0, // 5% comisión por defecto
        ]);
        
        $orderItem->calculateLineTotal();
        $orderItem->calculateCommission();
        
        if (!$orderItem->isValid()) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Datos inválidos en artículo',
                'details' => $orderItem->getValidationErrors()
            ]);
            exit();
        }
        
        $order->addItem($orderItem);
        $totalCalculated += $orderItem->lineTotal;
    }
    
    // Recalcular totales
    $order->recalculateSubtotal();
    
    // Validar pedido completo
    if (!$order->isValid()) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Datos del pedido inválidos',
            'details' => $order->getValidationErrors()
        ]);
        exit();
    }
    
    // Guardar pedido
    $savedOrder = $orderRepository->create($order);
    
    if ($savedOrder) {
        // Marcar cupón como usado si se aplicó
        if (!empty($input['coupon_code'])) {
            $stmt = $pdo->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE code = ?");
            $stmt->execute([$input['coupon_code']]);
        }
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Pedido creado exitosamente',
            'order' => $savedOrder->toArray(true)
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al crear el pedido']);
    }
    
} catch (Exception $e) {
    error_log("Error en create order: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
}
