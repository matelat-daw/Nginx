<?php
/**
 * API Endpoint: Crear Pedido Simple
 * POST /api/orders/create-simple.php
 * Versión simplificada para el carrito de compras
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
        echo json_encode(['success' => false, 'message' => 'Token de autorización requerido']);
        exit();
    }
    
    $token = substr($authHeader, 7);
    
    try {
        $decoded = JWT::decode($token, JWT_SECRET);
        $buyerId = $decoded->userId;
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Token inválido']);
        exit();
    }
    
    // Obtener datos del pedido
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Datos del pedido requeridos']);
        exit();
    }
    
    // Validar que hay items
    if (empty($input['items']) || !is_array($input['items'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El pedido debe incluir al menos un artículo']);
        exit();
    }
    
    // Iniciar transacción
    $pdo->beginTransaction();
    
    // Calcular totales
    $subtotal = 0;
    $validatedItems = [];
    $stockAlreadyConfirmed = $input['stock_confirmed'] ?? false;
    
    foreach ($input['items'] as $item) {
        // Validar que el producto existe y está disponible
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
        $stmt->execute([$item['id']]);
        $product = $stmt->fetch();
        
        if (!$product) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Producto no encontrado: ' . $item['name']]);
            exit();
        }
        
        // Si el stock ya está confirmado (reservas procesadas), saltamos la verificación
        if (!$stockAlreadyConfirmed) {
            // Verificar stock solo si no se procesaron reservas previamente
            if ($product['stock_quantity'] < $item['quantity']) {
                $pdo->rollBack();
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Stock insuficiente para: ' . $product['name']]);
                exit();
            }
        }
        
        $lineTotal = $product['price'] * $item['quantity'];
        $subtotal += $lineTotal;
        
        $validatedItems[] = [
            'product_id' => $product['id'],
            'seller_id' => $product['seller_id'],
            'product_name' => $product['name'],
            'product_description' => $product['short_description'],
            'unit_price' => $product['price'],
            'quantity' => $item['quantity'],
            'line_total' => $lineTotal,
            'stock_was_reserved' => $stockAlreadyConfirmed
        ];
    }
    
    // Calcular costos adicionales
    $shippingCost = $input['shipping_cost'] ?? 0;
    $taxAmount = round($subtotal * 0.07, 2); // IGIC 7% para Canarias
    $totalAmount = $subtotal + $shippingCost + $taxAmount;
    
    // Generar número de pedido único
    $year = date('Y');
    $stmt = $pdo->prepare("SELECT COUNT(*) + 1 as next_number FROM orders WHERE order_number LIKE ?");
    $stmt->execute(["ECC-$year-%"]);
    $nextNumber = $stmt->fetch()['next_number'];
    $orderNumber = sprintf("ECC-%s-%06d", $year, $nextNumber);
    
    // Crear el pedido
    $stmt = $pdo->prepare("
        INSERT INTO orders (
            order_number, buyer_id, status, payment_status, payment_method,
            subtotal, shipping_cost, tax_amount, total_amount,
            delivery_method, shipping_address, shipping_island, shipping_city, 
            shipping_postal_code, shipping_phone, buyer_notes
        ) VALUES (?, ?, 'pending', 'pending', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([
        $orderNumber,
        $buyerId,
        $input['payment_method'],
        $subtotal,
        $shippingCost,
        $taxAmount,
        $totalAmount,
        $input['delivery_method'],
        $input['shipping_address'] ?? null,
        $input['shipping_island'] ?? null,
        $input['shipping_city'] ?? null,
        $input['shipping_postal_code'] ?? null,
        $input['shipping_phone'] ?? null,
        $input['buyer_notes'] ?? null
    ]);
    
    if (!$result) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al crear el pedido']);
        exit();
    }
    
    $orderId = $pdo->lastInsertId();
    
    // Crear los items del pedido
    $stmt = $pdo->prepare("
        INSERT INTO order_items (
            order_id, product_id, seller_id, product_name, product_description,
            unit_price, quantity, line_total, platform_commission_rate, platform_commission_amount, seller_payout
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 5.0, ?, ?)
    ");
    
    foreach ($validatedItems as $item) {
        $commissionAmount = round($item['line_total'] * 0.05, 2); // 5% comisión
        $sellerPayout = $item['line_total'] - $commissionAmount;
        
        $stmt->execute([
            $orderId,
            $item['product_id'],
            $item['seller_id'],
            $item['product_name'],
            $item['product_description'],
            $item['unit_price'],
            $item['quantity'],
            $item['line_total'],
            $commissionAmount,
            $sellerPayout
        ]);
    }
    
    // Crear registro de pago pendiente
    $stmt = $pdo->prepare("
        INSERT INTO payments (order_id, payment_method, amount, status) 
        VALUES (?, ?, ?, 'pending')
    ");
    $stmt->execute([$orderId, $input['payment_method'], $totalAmount]);
    
    // Confirmar transacción
    $pdo->commit();
    
    // Responder con éxito
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Pedido creado exitosamente',
        'order' => [
            'id' => $orderId,
            'order_number' => $orderNumber,
            'total_amount' => $totalAmount,
            'payment_method' => $input['payment_method'],
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (Exception $e) {
    // Rollback en caso de error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Error en create-simple order: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor: ' . $e->getMessage()]);
}
?>
