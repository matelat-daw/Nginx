<?php
/**
 * Modelo OrderItem - Economía Circular Canarias
 * Representa un artículo dentro de un pedido
 */

class OrderItem {
    // Propiedades principales
    public $id;
    public $orderId;
    public $productId;
    public $sellerId;
    
    // Información del producto (snapshot)
    public $productName;
    public $productDescription;
    public $productSku;
    
    // Precios
    public $unitPrice;
    public $originalPrice;
    public $quantity;
    public $lineTotal;
    
    // Variante
    public $variantInfo;
    
    // Estado
    public $itemStatus;
    public $trackingNumber;
    public $deliveredAt;
    
    // Comisiones
    public $platformCommissionRate;
    public $platformCommissionAmount;
    public $sellerPayout;
    
    // Timestamps
    public $createdAt;
    public $updatedAt;
    
    // Propiedades relacionadas
    public $product;
    public $seller;
    
    private $validationErrors = [];
    
    public function __construct($data = []) {
        if (!empty($data)) {
            $this->fillFromArray($data);
        }
        
        // Valores por defecto
        $this->quantity = $this->quantity ?? 1;
        $this->itemStatus = $this->itemStatus ?? 'pending';
        $this->platformCommissionRate = $this->platformCommissionRate ?? 0.00;
        $this->platformCommissionAmount = $this->platformCommissionAmount ?? 0.00;
    }
    
    public function fillFromArray($data) {
        $this->id = $data['id'] ?? null;
        $this->orderId = $data['order_id'] ?? $data['orderId'] ?? null;
        $this->productId = $data['product_id'] ?? $data['productId'] ?? null;
        $this->sellerId = $data['seller_id'] ?? $data['sellerId'] ?? null;
        
        $this->productName = $data['product_name'] ?? $data['productName'] ?? null;
        $this->productDescription = $data['product_description'] ?? $data['productDescription'] ?? null;
        $this->productSku = $data['product_sku'] ?? $data['productSku'] ?? null;
        
        $this->unitPrice = $this->parseDecimal($data['unit_price'] ?? $data['unitPrice'] ?? null);
        $this->originalPrice = $this->parseDecimal($data['original_price'] ?? $data['originalPrice'] ?? null);
        $this->quantity = $this->parseInt($data['quantity'] ?? 1);
        $this->lineTotal = $this->parseDecimal($data['line_total'] ?? $data['lineTotal'] ?? null);
        
        $this->variantInfo = $this->parseJson($data['variant_info'] ?? $data['variantInfo'] ?? null);
        
        $this->itemStatus = $data['item_status'] ?? $data['itemStatus'] ?? 'pending';
        $this->trackingNumber = $data['tracking_number'] ?? $data['trackingNumber'] ?? null;
        $this->deliveredAt = $data['delivered_at'] ?? $data['deliveredAt'] ?? null;
        
        $this->platformCommissionRate = $this->parseDecimal($data['platform_commission_rate'] ?? $data['platformCommissionRate'] ?? 0.00);
        $this->platformCommissionAmount = $this->parseDecimal($data['platform_commission_amount'] ?? $data['platformCommissionAmount'] ?? 0.00);
        $this->sellerPayout = $this->parseDecimal($data['seller_payout'] ?? $data['sellerPayout'] ?? null);
        
        $this->createdAt = $data['created_at'] ?? $data['createdAt'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? $data['updatedAt'] ?? null;
        
        // Calcular lineTotal si no está establecido
        if ($this->lineTotal === null && $this->unitPrice !== null && $this->quantity !== null) {
            $this->calculateLineTotal();
        }
    }
    
    public function isValid() {
        $this->validationErrors = [];
        
        // Validar campos requeridos
        if (empty($this->orderId)) {
            $this->validationErrors[] = 'ID del pedido es requerido';
        }
        
        if (empty($this->productId)) {
            $this->validationErrors[] = 'ID del producto es requerido';
        }
        
        if (empty($this->sellerId)) {
            $this->validationErrors[] = 'ID del vendedor es requerido';
        }
        
        if (empty($this->productName)) {
            $this->validationErrors[] = 'Nombre del producto es requerido';
        }
        
        // Validar precios
        if ($this->unitPrice === null || $this->unitPrice <= 0) {
            $this->validationErrors[] = 'Precio unitario debe ser mayor a 0';
        }
        
        // Validar cantidad
        if ($this->quantity === null || $this->quantity <= 0) {
            $this->validationErrors[] = 'Cantidad debe ser mayor a 0';
        }
        
        // Validar estado
        $validStatuses = ['pending', 'confirmed', 'preparing', 'ready', 'delivered', 'cancelled', 'refunded'];
        if (!in_array($this->itemStatus, $validStatuses)) {
            $this->validationErrors[] = 'Estado del artículo no válido';
        }
        
        // Validar comisión
        if ($this->platformCommissionRate < 0 || $this->platformCommissionRate > 100) {
            $this->validationErrors[] = 'Tasa de comisión debe estar entre 0 y 100';
        }
        
        return empty($this->validationErrors);
    }
    
    public function getValidationErrors() {
        return $this->validationErrors;
    }
    
    public function toArray($includeRelated = false) {
        $data = [
            'id' => $this->id,
            'orderId' => $this->orderId,
            'productId' => $this->productId,
            'sellerId' => $this->sellerId,
            'productName' => $this->productName,
            'productDescription' => $this->productDescription,
            'productSku' => $this->productSku,
            'unitPrice' => $this->unitPrice,
            'originalPrice' => $this->originalPrice,
            'quantity' => $this->quantity,
            'lineTotal' => $this->lineTotal,
            'variantInfo' => $this->variantInfo,
            'itemStatus' => $this->itemStatus,
            'trackingNumber' => $this->trackingNumber,
            'deliveredAt' => $this->deliveredAt,
            'platformCommissionRate' => $this->platformCommissionRate,
            'platformCommissionAmount' => $this->platformCommissionAmount,
            'sellerPayout' => $this->sellerPayout,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt
        ];
        
        if ($includeRelated) {
            if (isset($this->product)) {
                $data['product'] = $this->product;
            }
            if (isset($this->seller)) {
                $data['seller'] = $this->seller;
            }
        }
        
        return $data;
    }
    
    public function calculateLineTotal() {
        $this->lineTotal = $this->unitPrice * $this->quantity;
        return $this->lineTotal;
    }
    
    public function calculateCommission() {
        if ($this->platformCommissionRate > 0) {
            $this->platformCommissionAmount = ($this->lineTotal * $this->platformCommissionRate) / 100;
            $this->sellerPayout = $this->lineTotal - $this->platformCommissionAmount;
        } else {
            $this->platformCommissionAmount = 0.00;
            $this->sellerPayout = $this->lineTotal;
        }
        
        return $this->platformCommissionAmount;
    }
    
    public function hasDiscount() {
        return $this->originalPrice !== null && $this->originalPrice > $this->unitPrice;
    }
    
    public function getDiscountAmount() {
        if ($this->hasDiscount()) {
            return ($this->originalPrice - $this->unitPrice) * $this->quantity;
        }
        return 0.00;
    }
    
    public function getDiscountPercentage() {
        if ($this->hasDiscount()) {
            return round((($this->originalPrice - $this->unitPrice) / $this->originalPrice) * 100, 2);
        }
        return 0.00;
    }
    
    public function getStatusLabel() {
        $labels = [
            'pending' => 'Pendiente',
            'confirmed' => 'Confirmado',
            'preparing' => 'Preparando',
            'ready' => 'Listo',
            'delivered' => 'Entregado',
            'cancelled' => 'Cancelado',
            'refunded' => 'Reembolsado'
        ];
        
        return $labels[$this->itemStatus] ?? $this->itemStatus;
    }
    
    public function getVariantDisplayText() {
        if (empty($this->variantInfo)) {
            return '';
        }
        
        if (is_array($this->variantInfo)) {
            $parts = [];
            foreach ($this->variantInfo as $key => $value) {
                $parts[] = ucfirst($key) . ': ' . $value;
            }
            return implode(', ', $parts);
        }
        
        return (string)$this->variantInfo;
    }
    
    // Métodos auxiliares
    private function parseInt($value) {
        return $value !== null ? (int)$value : null;
    }
    
    private function parseDecimal($value) {
        return $value !== null ? (float)$value : null;
    }
    
    private function parseJson($value) {
        if (empty($value)) return null;
        if (is_array($value)) return $value;
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return $decoded !== null ? $decoded : $value;
        }
        return $value;
    }
}
