<?php
/**
 * Modelo Order - Economía Circular Canarias
 * Representa un pedido del sistema con validación y mapeo automático
 */

class Order {
    // Propiedades principales
    public $id;
    public $orderNumber;
    public $buyerId;
    public $sellerId;
    
    // Estado y flujo
    public $status;
    public $paymentStatus;
    public $paymentMethod;
    
    // Importes
    public $subtotal;
    public $shippingCost;
    public $taxAmount;
    public $discountAmount;
    public $couponDiscount;
    public $totalAmount;
    
    // Entrega
    public $deliveryMethod;
    public $shippingAddress;
    public $shippingIsland;
    public $shippingCity;
    public $shippingPostalCode;
    public $shippingPhone;
    
    // Facturación
    public $billingAddress;
    public $billingIsland;
    public $billingCity;
    public $billingPostalCode;
    public $billingName;
    public $billingTaxId;
    
    // Pago
    public $paymentReference;
    
    // Cupones
    public $couponCode;
    
    // Fechas
    public $estimatedDeliveryDate;
    public $actualDeliveryDate;
    
    // Notas
    public $buyerNotes;
    public $sellerNotes;
    public $adminNotes;
    
    // Cancelación/devolución
    public $cancellationReason;
    public $refundReason;
    public $refundAmount;
    
    // Timestamps
    public $createdAt;
    public $updatedAt;
    
    // Propiedades relacionadas
    public $items = [];
    public $payments = [];
    public $buyer;
    public $seller;
    public $statusHistory = [];
    
    private $validationErrors = [];
    
    public function __construct($data = []) {
        if (!empty($data)) {
            $this->fillFromArray($data);
        }
        
        // Valores por defecto
        $this->status = $this->status ?? 'pending';
        $this->paymentStatus = $this->paymentStatus ?? 'pending';
        $this->deliveryMethod = $this->deliveryMethod ?? 'pickup';
        $this->subtotal = $this->subtotal ?? 0.00;
        $this->shippingCost = $this->shippingCost ?? 0.00;
        $this->taxAmount = $this->taxAmount ?? 0.00;
        $this->discountAmount = $this->discountAmount ?? 0.00;
        $this->couponDiscount = $this->couponDiscount ?? 0.00;
        $this->totalAmount = $this->totalAmount ?? 0.00;
        $this->refundAmount = $this->refundAmount ?? 0.00;
    }
    
    public function fillFromArray($data) {
        $this->id = $data['id'] ?? null;
        $this->orderNumber = $data['order_number'] ?? $data['orderNumber'] ?? null;
        $this->buyerId = $data['buyer_id'] ?? $data['buyerId'] ?? null;
        $this->sellerId = $data['seller_id'] ?? $data['sellerId'] ?? null;
        
        $this->status = $data['status'] ?? 'pending';
        $this->paymentStatus = $data['payment_status'] ?? $data['paymentStatus'] ?? 'pending';
        $this->paymentMethod = $data['payment_method'] ?? $data['paymentMethod'] ?? null;
        
        $this->subtotal = $this->parseDecimal($data['subtotal'] ?? 0.00);
        $this->shippingCost = $this->parseDecimal($data['shipping_cost'] ?? $data['shippingCost'] ?? 0.00);
        $this->taxAmount = $this->parseDecimal($data['tax_amount'] ?? $data['taxAmount'] ?? 0.00);
        $this->discountAmount = $this->parseDecimal($data['discount_amount'] ?? $data['discountAmount'] ?? 0.00);
        $this->couponDiscount = $this->parseDecimal($data['coupon_discount'] ?? $data['couponDiscount'] ?? 0.00);
        $this->totalAmount = $this->parseDecimal($data['total_amount'] ?? $data['totalAmount'] ?? 0.00);
        
        $this->deliveryMethod = $data['delivery_method'] ?? $data['deliveryMethod'] ?? 'pickup';
        $this->shippingAddress = $data['shipping_address'] ?? $data['shippingAddress'] ?? null;
        $this->shippingIsland = $data['shipping_island'] ?? $data['shippingIsland'] ?? null;
        $this->shippingCity = $data['shipping_city'] ?? $data['shippingCity'] ?? null;
        $this->shippingPostalCode = $data['shipping_postal_code'] ?? $data['shippingPostalCode'] ?? null;
        $this->shippingPhone = $data['shipping_phone'] ?? $data['shippingPhone'] ?? null;
        
        $this->billingAddress = $data['billing_address'] ?? $data['billingAddress'] ?? null;
        $this->billingIsland = $data['billing_island'] ?? $data['billingIsland'] ?? null;
        $this->billingCity = $data['billing_city'] ?? $data['billingCity'] ?? null;
        $this->billingPostalCode = $data['billing_postal_code'] ?? $data['billingPostalCode'] ?? null;
        $this->billingName = $data['billing_name'] ?? $data['billingName'] ?? null;
        $this->billingTaxId = $data['billing_tax_id'] ?? $data['billingTaxId'] ?? null;
        
        $this->paymentReference = $data['payment_reference'] ?? $data['paymentReference'] ?? null;
        $this->couponCode = $data['coupon_code'] ?? $data['couponCode'] ?? null;
        
        $this->estimatedDeliveryDate = $data['estimated_delivery_date'] ?? $data['estimatedDeliveryDate'] ?? null;
        $this->actualDeliveryDate = $data['actual_delivery_date'] ?? $data['actualDeliveryDate'] ?? null;
        
        $this->buyerNotes = $data['buyer_notes'] ?? $data['buyerNotes'] ?? null;
        $this->sellerNotes = $data['seller_notes'] ?? $data['sellerNotes'] ?? null;
        $this->adminNotes = $data['admin_notes'] ?? $data['adminNotes'] ?? null;
        
        $this->cancellationReason = $data['cancellation_reason'] ?? $data['cancellationReason'] ?? null;
        $this->refundReason = $data['refund_reason'] ?? $data['refundReason'] ?? null;
        $this->refundAmount = $this->parseDecimal($data['refund_amount'] ?? $data['refundAmount'] ?? 0.00);
        
        $this->createdAt = $data['created_at'] ?? $data['createdAt'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? $data['updatedAt'] ?? null;
    }
    
    public function isValid() {
        $this->validationErrors = [];
        
        // Validar comprador
        if (empty($this->buyerId)) {
            $this->validationErrors[] = 'ID del comprador es requerido';
        }
        
        // Validar método de pago
        $validPaymentMethods = ['bizum', 'card', 'transfer', 'cash', 'stripe'];
        if (!empty($this->paymentMethod) && !in_array($this->paymentMethod, $validPaymentMethods)) {
            $this->validationErrors[] = 'Método de pago no válido';
        }
        
        // Validar método de entrega
        $validDeliveryMethods = ['pickup', 'shipping', 'digital'];
        if (!in_array($this->deliveryMethod, $validDeliveryMethods)) {
            $this->validationErrors[] = 'Método de entrega no válido';
        }
        
        // Validar dirección de envío si es necesaria
        if ($this->deliveryMethod === 'shipping') {
            if (empty($this->shippingAddress)) {
                $this->validationErrors[] = 'Dirección de envío requerida para envío';
            }
            if (empty($this->shippingIsland)) {
                $this->validationErrors[] = 'Isla de envío requerida';
            }
        }
        
        // Validar importes
        if ($this->totalAmount < 0) {
            $this->validationErrors[] = 'El total no puede ser negativo';
        }
        
        if ($this->refundAmount > $this->totalAmount) {
            $this->validationErrors[] = 'El reembolso no puede ser mayor que el total';
        }
        
        // Validar isla
        $validIslands = ['Gran Canaria', 'Tenerife', 'Lanzarote', 'Fuerteventura', 'La Palma', 'La Gomera', 'El Hierro'];
        if (!empty($this->shippingIsland) && !in_array($this->shippingIsland, $validIslands)) {
            $this->validationErrors[] = 'Isla de envío no válida';
        }
        if (!empty($this->billingIsland) && !in_array($this->billingIsland, $validIslands)) {
            $this->validationErrors[] = 'Isla de facturación no válida';
        }
        
        return empty($this->validationErrors);
    }
    
    public function getValidationErrors() {
        return $this->validationErrors;
    }
    
    public function toArray($includeRelated = false) {
        $data = [
            'id' => $this->id,
            'orderNumber' => $this->orderNumber,
            'buyerId' => $this->buyerId,
            'sellerId' => $this->sellerId,
            'status' => $this->status,
            'paymentStatus' => $this->paymentStatus,
            'paymentMethod' => $this->paymentMethod,
            'subtotal' => $this->subtotal,
            'shippingCost' => $this->shippingCost,
            'taxAmount' => $this->taxAmount,
            'discountAmount' => $this->discountAmount,
            'couponDiscount' => $this->couponDiscount,
            'totalAmount' => $this->totalAmount,
            'deliveryMethod' => $this->deliveryMethod,
            'shippingAddress' => $this->shippingAddress,
            'shippingIsland' => $this->shippingIsland,
            'shippingCity' => $this->shippingCity,
            'shippingPostalCode' => $this->shippingPostalCode,
            'shippingPhone' => $this->shippingPhone,
            'billingAddress' => $this->billingAddress,
            'billingIsland' => $this->billingIsland,
            'billingCity' => $this->billingCity,
            'billingPostalCode' => $this->billingPostalCode,
            'billingName' => $this->billingName,
            'billingTaxId' => $this->billingTaxId,
            'paymentReference' => $this->paymentReference,
            'couponCode' => $this->couponCode,
            'estimatedDeliveryDate' => $this->estimatedDeliveryDate,
            'actualDeliveryDate' => $this->actualDeliveryDate,
            'buyerNotes' => $this->buyerNotes,
            'sellerNotes' => $this->sellerNotes,
            'adminNotes' => $this->adminNotes,
            'cancellationReason' => $this->cancellationReason,
            'refundReason' => $this->refundReason,
            'refundAmount' => $this->refundAmount,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt
        ];
        
        if ($includeRelated) {
            if (!empty($this->items)) {
                $data['items'] = $this->items;
            }
            if (!empty($this->payments)) {
                $data['payments'] = $this->payments;
            }
            if (isset($this->buyer)) {
                $data['buyer'] = $this->buyer;
            }
            if (isset($this->seller)) {
                $data['seller'] = $this->seller;
            }
            if (!empty($this->statusHistory)) {
                $data['statusHistory'] = $this->statusHistory;
            }
        }
        
        return $data;
    }
    
    public function calculateTotal() {
        $this->totalAmount = $this->subtotal + $this->shippingCost + $this->taxAmount - $this->discountAmount - $this->couponDiscount;
        return $this->totalAmount;
    }
    
    public function addItem(OrderItem $item) {
        $this->items[] = $item;
        $this->recalculateSubtotal();
    }
    
    public function recalculateSubtotal() {
        $this->subtotal = 0;
        foreach ($this->items as $item) {
            $this->subtotal += $item->lineTotal;
        }
        $this->calculateTotal();
    }
    
    public function canBeCancelled() {
        return in_array($this->status, ['pending', 'paid', 'processing']);
    }
    
    public function canBeRefunded() {
        return in_array($this->status, ['paid', 'delivered']) && $this->paymentStatus === 'paid';
    }
    
    public function getStatusLabel() {
        $labels = [
            'pending' => 'Pendiente de pago',
            'paid' => 'Pagado',
            'processing' => 'En preparación',
            'ready_pickup' => 'Listo para recoger',
            'shipped' => 'Enviado',
            'delivered' => 'Entregado',
            'cancelled' => 'Cancelado',
            'refunded' => 'Reembolsado'
        ];
        
        return $labels[$this->status] ?? $this->status;
    }
    
    public function getPaymentStatusLabel() {
        $labels = [
            'pending' => 'Pendiente',
            'paid' => 'Pagado',
            'failed' => 'Fallido',
            'refunded' => 'Reembolsado',
            'partial' => 'Parcial'
        ];
        
        return $labels[$this->paymentStatus] ?? $this->paymentStatus;
    }
    
    private function parseDecimal($value) {
        return $value !== null ? (float)$value : 0.00;
    }
}
