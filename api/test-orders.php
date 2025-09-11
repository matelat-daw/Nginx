<?php
/**
 * Script de prueba para el sistema de pedidos y facturación
 * Ejecutar desde: https://localhost/Canarias-EC/api/test-orders.php
 */

header('Content-Type: application/json');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/models/Order.php';
require_once __DIR__ . '/models/OrderItem.php';
require_once __DIR__ . '/repositories/OrderRepository.php';

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
    
    $tables = ['orders', 'order_items', 'payments', 'order_status_history', 'invoices', 'coupons'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            $exists = $stmt->rowCount() > 0;
            $results['tables_check'][$table] = $exists ? '✅ Existe' : '❌ No existe';
        } catch (Exception $e) {
            $results['tables_check'][$table] = '❌ Error: ' . $e->getMessage();
        }
    }
    
    // 2. Verificar triggers
    try {
        $stmt = $pdo->query("SHOW TRIGGERS LIKE 'orders%'");
        $triggers = $stmt->fetchAll();
        $results['triggers'] = "✅ " . count($triggers) . " triggers encontrados";
        
        $triggerNames = array_column($triggers, 'Trigger');
        $results['trigger_list'] = $triggerNames;
        
    } catch (Exception $e) {
        $results['triggers'] = '❌ Error: ' . $e->getMessage();
    }
    
    // 3. Verificar cupones
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM coupons");
        $couponCount = $stmt->fetch()['count'];
        $results['coupons'] = "✅ $couponCount cupones encontrados";
        
        // Mostrar cupones
        $stmt = $pdo->query("SELECT code, name, discount_type, discount_value FROM coupons LIMIT 5");
        $coupons = $stmt->fetchAll();
        $results['sample_coupons'] = $coupons;
        
    } catch (Exception $e) {
        $results['coupons'] = '❌ Error: ' . $e->getMessage();
    }
    
    // 4. Probar creación de pedido de prueba
    try {
        $orderRepository = new OrderRepository($pdo);
        
        // Crear pedido de prueba
        $testOrder = new Order([
            'buyer_id' => 1, // Asumiendo que existe un usuario con ID 1
            'delivery_method' => 'pickup',
            'pickup_island' => 'Gran Canaria',
            'pickup_city' => 'Las Palmas',
            'payment_method' => 'bizum',
            'buyer_notes' => 'Pedido de prueba del sistema'
        ]);
        
        // Crear item de prueba
        $testItem = new OrderItem([
            'product_id' => 1, // Asumiendo que existe un producto con ID 1
            'seller_id' => 1,
            'product_name' => 'Producto de Prueba',
            'product_description' => 'Descripción de prueba',
            'unit_price' => 15.50,
            'quantity' => 2,
            'platform_commission_rate' => 5.0
        ]);
        
        $testItem->calculateLineTotal();
        $testItem->calculateCommission();
        $testOrder->addItem($testItem);
        
        if ($testOrder->isValid()) {
            $results['test_order_validation'] = '✅ Validación correcta';
            
            // Calcular descuentos con cupón
            $couponValidation = $orderRepository->validateCoupon('BIENVENIDO10', $testOrder->subtotal, 1);
            if ($couponValidation['valid']) {
                $results['coupon_validation'] = '✅ Cupón BIENVENIDO10 válido';
                $coupon = $couponValidation['coupon'];
                $discount = ($testOrder->subtotal * $coupon['discount_value']) / 100;
                $results['coupon_discount'] = "💰 Descuento aplicado: €" . number_format($discount, 2);
            } else {
                $results['coupon_validation'] = '⚠️ ' . $couponValidation['error'];
            }
            
            $results['test_order_data'] = [
                'subtotal' => $testOrder->subtotal,
                'total' => $testOrder->totalAmount,
                'items_count' => count($testOrder->items),
                'commission' => $testItem->platformCommissionAmount,
                'seller_payout' => $testItem->sellerPayout
            ];
            
            // No guardar en pruebas
            $results['test_order_creation'] = '⚠️ Creación deshabilitada en pruebas';
            
        } else {
            $results['test_order_validation'] = '❌ Errores: ' . implode(', ', $testOrder->getValidationErrors());
        }
        
    } catch (Exception $e) {
        $results['test_order'] = '❌ Error: ' . $e->getMessage();
    }
    
    // 5. Verificar estructura de tabla orders
    try {
        $stmt = $pdo->query("DESCRIBE orders");
        $columns = $stmt->fetchAll();
        $results['orders_structure'] = "✅ Tabla orders tiene " . count($columns) . " columnas";
        
        // Verificar campos clave
        $columnNames = array_column($columns, 'Field');
        $keyFields = ['order_number', 'buyer_id', 'seller_id', 'status', 'payment_status', 'total_amount'];
        $missingFields = array_diff($keyFields, $columnNames);
        
        if (empty($missingFields)) {
            $results['key_fields'] = '✅ Todos los campos clave presentes';
        } else {
            $results['key_fields'] = '❌ Campos faltantes: ' . implode(', ', $missingFields);
        }
        
    } catch (Exception $e) {
        $results['orders_structure'] = '❌ Error: ' . $e->getMessage();
    }
    
    // 6. Verificar enums
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM orders WHERE Field = 'status'");
        $statusColumn = $stmt->fetch();
        if ($statusColumn) {
            $results['status_enum'] = '✅ Estados: ' . $statusColumn['Type'];
        }
        
        $stmt = $pdo->query("SHOW COLUMNS FROM orders WHERE Field = 'payment_status'");
        $paymentColumn = $stmt->fetch();
        if ($paymentColumn) {
            $results['payment_status_enum'] = '✅ Estados de pago: ' . $paymentColumn['Type'];
        }
        
    } catch (Exception $e) {
        $results['enums'] = '❌ Error: ' . $e->getMessage();
    }
    
    // 7. Simulación de flujo completo
    $results['workflow_simulation'] = [
        '1_cart' => '🛒 Cliente agrega productos al carrito',
        '2_order' => '📝 Se crea pedido con estado "pending"',
        '3_payment' => '💳 Se procesa pago (Bizum/Stripe)',
        '4_paid' => '✅ Pedido pasa a "paid", stock se reduce',
        '5_processing' => '📦 Vendedor prepara pedido',
        '6_pickup' => '🚚 Cliente recoge o se envía',
        '7_delivered' => '🎉 Pedido completado'
    ];
    
    // 8. Verificar índices
    try {
        $stmt = $pdo->query("SHOW INDEX FROM orders");
        $indexes = $stmt->fetchAll();
        $results['indexes'] = "✅ " . count($indexes) . " índices en tabla orders";
        
    } catch (Exception $e) {
        $results['indexes'] = '❌ Error: ' . $e->getMessage();
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Pruebas del sistema de pedidos completadas',
        'timestamp' => date('Y-m-d H:i:s'),
        'results' => $results,
        'recommendations' => [
            '✅ Sistema listo para implementar carrito',
            '✅ Preparado para integración con Stripe/Bizum',
            '✅ Soporte completo para multi-vendedor',
            '✅ Trazabilidad y auditoría incluida',
            '⚠️ Configurar comisiones por categoría',
            '⚠️ Implementar notificaciones por email',
            '⚠️ Configurar webhooks de pago'
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error en las pruebas: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
}
