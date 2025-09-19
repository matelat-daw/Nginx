<?php
/**
 * OrderRepository - Economía Circular Canarias
 * Maneja todas las operaciones de base de datos para pedidos
 */

require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/OrderItem.php';

class OrderRepository {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Crear un nuevo pedido
     */
    public function create(Order $order) {
        try {
            $this->pdo->beginTransaction();
            
            $sql = "INSERT INTO orders (
                buyer_id, seller_id, status, payment_status, payment_method,
                subtotal, shipping_cost, tax_amount, discount_amount, coupon_discount, total_amount,
                delivery_method, shipping_address, shipping_island, shipping_city, shipping_postal_code, shipping_phone,
                billing_address, billing_island, billing_city, billing_postal_code, billing_name, billing_tax_id,
                payment_reference, coupon_code, estimated_delivery_date,
                buyer_notes, seller_notes, admin_notes
            ) VALUES (
                :buyer_id, :seller_id, :status, :payment_status, :payment_method,
                :subtotal, :shipping_cost, :tax_amount, :discount_amount, :coupon_discount, :total_amount,
                :delivery_method, :shipping_address, :shipping_island, :shipping_city, :shipping_postal_code, :shipping_phone,
                :billing_address, :billing_island, :billing_city, :billing_postal_code, :billing_name, :billing_tax_id,
                :payment_reference, :coupon_code, :estimated_delivery_date,
                :buyer_notes, :seller_notes, :admin_notes
            )";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                ':buyer_id' => $order->buyerId,
                ':seller_id' => $order->sellerId,
                ':status' => $order->status,
                ':payment_status' => $order->paymentStatus,
                ':payment_method' => $order->paymentMethod,
                ':subtotal' => $order->subtotal,
                ':shipping_cost' => $order->shippingCost,
                ':tax_amount' => $order->taxAmount,
                ':discount_amount' => $order->discountAmount,
                ':coupon_discount' => $order->couponDiscount,
                ':total_amount' => $order->totalAmount,
                ':delivery_method' => $order->deliveryMethod,
                ':shipping_address' => $order->shippingAddress,
                ':shipping_island' => $order->shippingIsland,
                ':shipping_city' => $order->shippingCity,
                ':shipping_postal_code' => $order->shippingPostalCode,
                ':shipping_phone' => $order->shippingPhone,
                ':billing_address' => $order->billingAddress,
                ':billing_island' => $order->billingIsland,
                ':billing_city' => $order->billingCity,
                ':billing_postal_code' => $order->billingPostalCode,
                ':billing_name' => $order->billingName,
                ':billing_tax_id' => $order->billingTaxId,
                ':payment_reference' => $order->paymentReference,
                ':coupon_code' => $order->couponCode,
                ':estimated_delivery_date' => $order->estimatedDeliveryDate,
                ':buyer_notes' => $order->buyerNotes,
                ':seller_notes' => $order->sellerNotes,
                ':admin_notes' => $order->adminNotes
            ]);
            
            if ($result) {
                $order->id = $this->pdo->lastInsertId();
                
                // Obtener el número de pedido generado automáticamente
                $stmt = $this->pdo->prepare("SELECT order_number FROM orders WHERE id = ?");
                $stmt->execute([$order->id]);
                $order->orderNumber = $stmt->fetchColumn();
                
                // Agregar items del pedido
                foreach ($order->items as $item) {
                    $item->orderId = $order->id;
                    $this->addOrderItem($item);
                }
                
                $this->pdo->commit();
                return $order;
            }
            
            $this->pdo->rollback();
            return false;
            
        } catch (PDOException $e) {
            $this->pdo->rollback();
            error_log("Error creating order: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Agregar item al pedido
     */
    public function addOrderItem(OrderItem $item) {
        try {
            $sql = "INSERT INTO order_items (
                order_id, product_id, seller_id, product_name, product_description, product_sku,
                unit_price, original_price, quantity, line_total, variant_info, item_status,
                platform_commission_rate, platform_commission_amount, seller_payout
            ) VALUES (
                :order_id, :product_id, :seller_id, :product_name, :product_description, :product_sku,
                :unit_price, :original_price, :quantity, :line_total, :variant_info, :item_status,
                :platform_commission_rate, :platform_commission_amount, :seller_payout
            )";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                ':order_id' => $item->orderId,
                ':product_id' => $item->productId,
                ':seller_id' => $item->sellerId,
                ':product_name' => $item->productName,
                ':product_description' => $item->productDescription,
                ':product_sku' => $item->productSku,
                ':unit_price' => $item->unitPrice,
                ':original_price' => $item->originalPrice,
                ':quantity' => $item->quantity,
                ':line_total' => $item->lineTotal,
                ':variant_info' => is_array($item->variantInfo) ? json_encode($item->variantInfo) : $item->variantInfo,
                ':item_status' => $item->itemStatus,
                ':platform_commission_rate' => $item->platformCommissionRate,
                ':platform_commission_amount' => $item->platformCommissionAmount,
                ':seller_payout' => $item->sellerPayout
            ]);
            
            if ($result) {
                $item->id = $this->pdo->lastInsertId();
                return $item;
            }
            
            return false;
            
        } catch (PDOException $e) {
            error_log("Error adding order item: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Buscar pedido por ID
     */
    public function findById($id, $includeItems = false, $includeRelated = false) {
        try {
            $sql = "SELECT * FROM orders WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$data) return null;
            
            $order = new Order($data);
            
            // Cargar items si se solicita
            if ($includeItems) {
                $order->items = $this->getOrderItems($order->id);
            }
            
            // Cargar datos relacionados
            if ($includeRelated) {
                $this->loadRelatedData($order);
            }
            
            return $order;
            
        } catch (PDOException $e) {
            error_log("Error finding order by ID: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Buscar pedido por número
     */
    public function findByOrderNumber($orderNumber, $includeItems = false, $includeRelated = false) {
        try {
            $sql = "SELECT * FROM orders WHERE order_number = :order_number";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':order_number' => $orderNumber]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$data) return null;
            
            $order = new Order($data);
            
            if ($includeItems) {
                $order->items = $this->getOrderItems($order->id);
            }
            
            if ($includeRelated) {
                $this->loadRelatedData($order);
            }
            
            return $order;
            
        } catch (PDOException $e) {
            error_log("Error finding order by number: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obtener pedidos del usuario
     */
    public function findByUser($userId, $userType = 'buyer', $limit = 20, $offset = 0, $status = null) {
        try {
            $sql = "SELECT * FROM orders WHERE ";
            $params = [];
            
            if ($userType === 'buyer') {
                $sql .= "buyer_id = :user_id";
            } else {
                $sql .= "seller_id = :user_id";
            }
            $params[':user_id'] = $userId;
            
            if ($status) {
                $sql .= " AND status = :status";
                $params[':status'] = $status;
            }
            
            $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
            
            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $orders = [];
            foreach ($results as $data) {
                $orders[] = new Order($data);
            }
            
            return $orders;
            
        } catch (PDOException $e) {
            error_log("Error finding orders by user: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener items de un pedido
     */
    public function getOrderItems($orderId) {
        try {
            $sql = "SELECT * FROM order_items WHERE order_id = :order_id ORDER BY created_at";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':order_id' => $orderId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $items = [];
            foreach ($results as $data) {
                $items[] = new OrderItem($data);
            }
            
            return $items;
            
        } catch (PDOException $e) {
            error_log("Error getting order items: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Actualizar estado del pedido
     */
    public function updateStatus($orderId, $newStatus, $userId = null, $reason = null) {
        try {
            $sql = "UPDATE orders SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([':id' => $orderId, ':status' => $newStatus]);
            
            // El trigger se encarga del historial automáticamente
            
            return $result;
            
        } catch (PDOException $e) {
            error_log("Error updating order status: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Actualizar estado de pago
     */
    public function updatePaymentStatus($orderId, $paymentStatus, $paymentReference = null) {
        try {
            $sql = "UPDATE orders SET 
                    payment_status = :payment_status, 
                    payment_reference = COALESCE(:payment_reference, payment_reference),
                    updated_at = CURRENT_TIMESTAMP 
                    WHERE id = :id";
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':id' => $orderId,
                ':payment_status' => $paymentStatus,
                ':payment_reference' => $paymentReference
            ]);
            
        } catch (PDOException $e) {
            error_log("Error updating payment status: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener estadísticas de ventas
     */
    public function getSalesStats($sellerId, $dateFrom = null, $dateTo = null) {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_orders,
                        SUM(total_amount) as total_revenue,
                        AVG(total_amount) as average_order_value,
                        COUNT(CASE WHEN status = 'delivered' THEN 1 END) as delivered_orders,
                        COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_orders
                    FROM orders 
                    WHERE seller_id = :seller_id";
            
            $params = [':seller_id' => $sellerId];
            
            if ($dateFrom) {
                $sql .= " AND created_at >= :date_from";
                $params[':date_from'] = $dateFrom;
            }
            
            if ($dateTo) {
                $sql .= " AND created_at <= :date_to";
                $params[':date_to'] = $dateTo;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error getting sales stats: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Validar cupón
     */
    public function validateCoupon($couponCode, $orderAmount = 0, $userId = null) {
        try {
            $sql = "SELECT * FROM coupons WHERE code = :code AND is_active = 1 
                    AND valid_from <= NOW() AND valid_until >= NOW()";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':code' => $couponCode]);
            $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$coupon) {
                return ['valid' => false, 'error' => 'Cupón no válido o expirado'];
            }
            
            // Verificar límite de usos
            if ($coupon['max_uses'] && $coupon['used_count'] >= $coupon['max_uses']) {
                return ['valid' => false, 'error' => 'Cupón agotado'];
            }
            
            // Verificar importe mínimo
            if ($coupon['minimum_order_amount'] && $orderAmount < $coupon['minimum_order_amount']) {
                return ['valid' => false, 'error' => 'Importe mínimo no alcanzado'];
            }
            
            // Verificar usos por usuario
            if ($userId && $coupon['max_uses_per_user']) {
                $sql = "SELECT COUNT(*) FROM orders WHERE buyer_id = :user_id AND coupon_code = :code";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([':user_id' => $userId, ':code' => $couponCode]);
                $userUses = $stmt->fetchColumn();
                
                if ($userUses >= $coupon['max_uses_per_user']) {
                    return ['valid' => false, 'error' => 'Límite de usos por usuario alcanzado'];
                }
            }
            
            return ['valid' => true, 'coupon' => $coupon];
            
        } catch (PDOException $e) {
            error_log("Error validating coupon: " . $e->getMessage());
            return ['valid' => false, 'error' => 'Error al validar cupón'];
        }
    }
    
    /**
     * Cargar datos relacionados
     */
    private function loadRelatedData(Order $order) {
        // Cargar comprador
        if ($order->buyerId) {
            $stmt = $this->pdo->prepare("SELECT id, first_name, last_name, email FROM users WHERE id = ?");
            $stmt->execute([$order->buyerId]);
            $order->buyer = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // Cargar vendedor
        if ($order->sellerId) {
            $stmt = $this->pdo->prepare("SELECT id, first_name, last_name, email FROM users WHERE id = ?");
            $stmt->execute([$order->sellerId]);
            $order->seller = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // Cargar historial de estados
        $stmt = $this->pdo->prepare("SELECT * FROM order_status_history WHERE order_id = ? ORDER BY created_at");
        $stmt->execute([$order->id]);
        $order->statusHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
