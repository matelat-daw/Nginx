-- =====================================================
-- ECONOMÍA CIRCULAR CANARIAS - SISTEMA DE PEDIDOS Y FACTURACIÓN
-- Estructura moderna para e-commerce con trazabilidad completa
-- =====================================================

-- Eliminar tablas existentes si existen (en orden correcto por dependencias)
DROP TABLE IF EXISTS coupons;
DROP TABLE IF EXISTS invoices;
DROP TABLE IF EXISTS order_status_history;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;

-- Tabla de pedidos (reemplaza tu tabla "invoice")
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Número de pedido único y legible
    order_number VARCHAR(20) UNIQUE NOT NULL, -- ECC-2025-000001
    
    -- Relaciones principales
    buyer_id INT NOT NULL,
    seller_id INT, -- Puede ser NULL para pedidos multi-vendedor
    
    -- Estado del pedido
    status ENUM(
        'pending',          -- Pendiente de pago
        'paid',            -- Pagado
        'processing',      -- En preparación
        'ready_pickup',    -- Listo para recoger
        'shipped',         -- Enviado
        'delivered',       -- Entregado
        'cancelled',       -- Cancelado
        'refunded'         -- Reembolsado
    ) DEFAULT 'pending',
    
    -- Importes (todos en EUR)
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,           -- Suma de productos
    shipping_cost DECIMAL(8,2) NOT NULL DEFAULT 0.00,      -- Coste de envío
    tax_amount DECIMAL(8,2) NOT NULL DEFAULT 0.00,         -- Impuestos (IGIC)
    discount_amount DECIMAL(8,2) NOT NULL DEFAULT 0.00,    -- Descuentos aplicados
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,      -- Total final
    
    -- Información de entrega
    delivery_method ENUM('pickup', 'shipping', 'digital') NOT NULL DEFAULT 'pickup',
    
    -- Dirección de envío (si aplica)
    shipping_address TEXT,
    shipping_island ENUM('Gran Canaria', 'Tenerife', 'Lanzarote', 'Fuerteventura', 'La Palma', 'La Gomera', 'El Hierro'),
    shipping_city VARCHAR(100),
    shipping_postal_code VARCHAR(10),
    shipping_phone VARCHAR(20),
    
    -- Dirección de facturación
    billing_address TEXT,
    billing_island ENUM('Gran Canaria', 'Tenerife', 'Lanzarote', 'Fuerteventura', 'La Palma', 'La Gomera', 'El Hierro'),
    billing_city VARCHAR(100),
    billing_postal_code VARCHAR(10),
    
    -- Datos fiscales (para facturas)
    billing_name VARCHAR(255),
    billing_tax_id VARCHAR(20), -- NIF/CIF
    
    -- Información de pago
    payment_method ENUM('bizum', 'card', 'transfer', 'cash', 'stripe') NOT NULL,
    payment_status ENUM('pending', 'paid', 'failed', 'refunded', 'partial') DEFAULT 'pending',
    payment_reference VARCHAR(100), -- Referencia del pago externo
    
    -- Cupones y descuentos
    coupon_code VARCHAR(50),
    coupon_discount DECIMAL(8,2) DEFAULT 0.00,
    
    -- Fechas importantes
    estimated_delivery_date DATE,
    actual_delivery_date DATETIME,
    
    -- Notas y comentarios
    buyer_notes TEXT,
    seller_notes TEXT,
    admin_notes TEXT,
    
    -- Cancelación/devolución
    cancellation_reason TEXT,
    refund_reason TEXT,
    refund_amount DECIMAL(10,2) DEFAULT 0.00,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Índices para rendimiento
    INDEX idx_buyer (buyer_id),
    INDEX idx_seller (seller_id),
    INDEX idx_status (status),
    INDEX idx_payment_status (payment_status),
    INDEX idx_order_number (order_number),
    INDEX idx_created (created_at),
    INDEX idx_delivery_method (delivery_method),
    INDEX idx_island (shipping_island),
    
    -- Claves foráneas
    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Tabla de artículos del pedido (reemplaza tu tabla "sold")
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Relaciones
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    seller_id INT NOT NULL, -- Para pedidos multi-vendedor
    
    -- Información del producto en el momento de la compra
    product_name VARCHAR(255) NOT NULL,
    product_description TEXT,
    product_sku VARCHAR(100),
    
    -- Precio en el momento de la compra (importante para histórico)
    unit_price DECIMAL(10,2) NOT NULL,
    original_price DECIMAL(10,2), -- Precio antes de descuentos
    
    -- Cantidad y totales
    quantity INT NOT NULL DEFAULT 1,
    line_total DECIMAL(10,2) NOT NULL, -- unit_price * quantity
    
    -- Variante específica (si aplica)
    variant_info JSON, -- {"talla": "M", "color": "azul"}
    
    -- Estado específico del artículo
    item_status ENUM(
        'pending',
        'confirmed',
        'preparing',
        'ready',
        'delivered',
        'cancelled',
        'refunded'
    ) DEFAULT 'pending',
    
    -- Información de entrega específica
    tracking_number VARCHAR(100),
    delivered_at DATETIME,
    
    -- Comisiones (para la plataforma)
    platform_commission_rate DECIMAL(5,2) DEFAULT 0.00, -- Porcentaje
    platform_commission_amount DECIMAL(8,2) DEFAULT 0.00,
    seller_payout DECIMAL(10,2), -- Lo que recibe el vendedor
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Índices
    INDEX idx_order (order_id),
    INDEX idx_product (product_id),
    INDEX idx_seller (seller_id),
    INDEX idx_status (item_status),
    
    -- Claves foráneas
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE RESTRICT
);

-- Tabla de pagos (para trazabilidad de transacciones)
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Relación con el pedido
    order_id INT NOT NULL,
    
    -- Información del pago
    payment_method ENUM('bizum', 'card', 'transfer', 'cash', 'stripe') NOT NULL,
    payment_provider VARCHAR(50), -- 'stripe', 'redsys', etc.
    
    -- Importes
    amount DECIMAL(10,2) NOT NULL,
    currency CHAR(3) DEFAULT 'EUR',
    
    -- Estado del pago
    status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded') DEFAULT 'pending',
    
    -- Referencias externas
    external_payment_id VARCHAR(100), -- ID de Stripe, Bizum, etc.
    external_reference VARCHAR(100),
    
    -- Detalles específicos del método de pago
    card_last_four CHAR(4), -- Últimos 4 dígitos de tarjeta
    bizum_phone VARCHAR(15), -- Teléfono de Bizum
    
    -- Fechas de procesamiento
    processed_at DATETIME,
    confirmed_at DATETIME,
    
    -- Información adicional
    processor_response JSON, -- Respuesta completa del procesador
    failure_reason TEXT,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Índices
    INDEX idx_order (order_id),
    INDEX idx_status (status),
    INDEX idx_external_id (external_payment_id),
    INDEX idx_method (payment_method),
    
    -- Clave foránea
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- Tabla de historial de estados (auditoría completa)
CREATE TABLE IF NOT EXISTS order_status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Relación
    order_id INT NOT NULL,
    order_item_id INT, -- NULL para cambios de todo el pedido
    
    -- Cambio de estado
    from_status VARCHAR(50),
    to_status VARCHAR(50) NOT NULL,
    
    -- Quién hizo el cambio
    changed_by_user_id INT,
    changed_by_role ENUM('buyer', 'seller', 'admin', 'system') DEFAULT 'system',
    
    -- Detalles del cambio
    reason TEXT,
    notes TEXT,
    
    -- Timestamp
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Índices
    INDEX idx_order (order_id),
    INDEX idx_item (order_item_id),
    INDEX idx_created (created_at),
    
    -- Claves foráneas
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by_user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Tabla de facturas (opcional, para empresas)
CREATE TABLE IF NOT EXISTS invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Número de factura único
    invoice_number VARCHAR(20) UNIQUE NOT NULL, -- ECC-F-2025-000001
    
    -- Relación con el pedido
    order_id INT NOT NULL,
    
    -- Tipo de factura
    invoice_type ENUM('invoice', 'credit_note', 'proforma') DEFAULT 'invoice',
    
    -- Datos del emisor (tu empresa)
    issuer_name VARCHAR(255) NOT NULL,
    issuer_tax_id VARCHAR(20) NOT NULL,
    issuer_address TEXT NOT NULL,
    
    -- Datos del cliente
    customer_name VARCHAR(255) NOT NULL,
    customer_tax_id VARCHAR(20),
    customer_address TEXT NOT NULL,
    
    -- Importes fiscales
    subtotal DECIMAL(10,2) NOT NULL,
    tax_rate DECIMAL(5,2) NOT NULL DEFAULT 7.00, -- IGIC Canarias
    tax_amount DECIMAL(8,2) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    
    -- Estado fiscal
    status ENUM('draft', 'sent', 'paid', 'overdue', 'cancelled') DEFAULT 'draft',
    
    -- Fechas fiscales
    issue_date DATE NOT NULL,
    due_date DATE,
    paid_date DATE,
    
    -- Archivo PDF
    pdf_path VARCHAR(500),
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Índices
    INDEX idx_order (order_id),
    INDEX idx_invoice_number (invoice_number),
    INDEX idx_status (status),
    INDEX idx_customer_tax_id (customer_tax_id),
    
    -- Clave foránea
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE RESTRICT
);

-- Tabla de cupones/descuentos
CREATE TABLE IF NOT EXISTS coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Código del cupón
    code VARCHAR(50) UNIQUE NOT NULL,
    
    -- Descripción
    name VARCHAR(255) NOT NULL,
    description TEXT,
    
    -- Tipo de descuento
    discount_type ENUM('percentage', 'fixed_amount', 'free_shipping') NOT NULL,
    discount_value DECIMAL(8,2) NOT NULL, -- Porcentaje o importe fijo
    
    -- Límites de uso
    max_uses INT, -- NULL = ilimitado
    used_count INT DEFAULT 0,
    max_uses_per_user INT DEFAULT 1,
    
    -- Condiciones
    minimum_order_amount DECIMAL(8,2),
    valid_for_categories TEXT, -- JSON array de IDs de categoría
    valid_for_sellers TEXT, -- JSON array de IDs de vendedor
    
    -- Fechas de validez
    valid_from DATETIME NOT NULL,
    valid_until DATETIME NOT NULL,
    
    -- Estado
    is_active BOOLEAN DEFAULT TRUE,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Índices
    INDEX idx_code (code),
    INDEX idx_active (is_active),
    INDEX idx_valid_dates (valid_from, valid_until)
);

-- =====================================================
-- TRIGGERS AUTOMÁTICOS
-- =====================================================

-- Trigger para generar número de pedido automáticamente
DELIMITER $$
CREATE TRIGGER orders_generate_number 
BEFORE INSERT ON orders
FOR EACH ROW
BEGIN
    IF NEW.order_number IS NULL OR NEW.order_number = '' THEN
        SET NEW.order_number = CONCAT('ECC-', YEAR(NOW()), '-', LPAD(
            (SELECT COALESCE(MAX(CAST(SUBSTRING(order_number, -6) AS UNSIGNED)), 0) + 1 
             FROM orders 
             WHERE order_number LIKE CONCAT('ECC-', YEAR(NOW()), '-%')), 
            6, '0'
        ));
    END IF;
END$$
DELIMITER ;

-- Trigger para calcular total del pedido
DELIMITER $$
CREATE TRIGGER order_items_update_order_total 
AFTER INSERT ON order_items
FOR EACH ROW
BEGIN
    UPDATE orders 
    SET subtotal = (
        SELECT COALESCE(SUM(line_total), 0) 
        FROM order_items 
        WHERE order_id = NEW.order_id
    ),
    total_amount = subtotal + shipping_cost + tax_amount - discount_amount - coupon_discount
    WHERE id = NEW.order_id;
END$$

CREATE TRIGGER order_items_update_order_total_update 
AFTER UPDATE ON order_items
FOR EACH ROW
BEGIN
    UPDATE orders 
    SET subtotal = (
        SELECT COALESCE(SUM(line_total), 0) 
        FROM order_items 
        WHERE order_id = NEW.order_id
    ),
    total_amount = subtotal + shipping_cost + tax_amount - discount_amount - coupon_discount
    WHERE id = NEW.order_id;
END$$

CREATE TRIGGER order_items_update_order_total_delete 
AFTER DELETE ON order_items
FOR EACH ROW
BEGIN
    UPDATE orders 
    SET subtotal = (
        SELECT COALESCE(SUM(line_total), 0) 
        FROM order_items 
        WHERE order_id = OLD.order_id
    ),
    total_amount = subtotal + shipping_cost + tax_amount - discount_amount - coupon_discount
    WHERE id = OLD.order_id;
END$$
DELIMITER ;

-- Trigger para registrar cambios de estado
DELIMITER $$
CREATE TRIGGER orders_status_history 
AFTER UPDATE ON orders
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO order_status_history (
            order_id, from_status, to_status, changed_by_role, notes
        ) VALUES (
            NEW.id, OLD.status, NEW.status, 'system', 
            CONCAT('Estado cambiado de ', OLD.status, ' a ', NEW.status)
        );
    END IF;
END$$
DELIMITER ;

-- Trigger para actualizar stock cuando se confirma un pedido
DELIMITER $$
CREATE TRIGGER orders_update_stock 
AFTER UPDATE ON orders
FOR EACH ROW
BEGIN
    -- Cuando el pedido pasa a 'paid', reducir stock
    IF OLD.status = 'pending' AND NEW.status = 'paid' THEN
        UPDATE products p
        INNER JOIN order_items oi ON p.id = oi.product_id
        SET p.stock_quantity = p.stock_quantity - oi.quantity,
            p.sales_count = p.sales_count + oi.quantity
        WHERE oi.order_id = NEW.id;
    END IF;
    
    -- Cuando se cancela, restaurar stock
    IF NEW.status = 'cancelled' AND OLD.status IN ('paid', 'processing') THEN
        UPDATE products p
        INNER JOIN order_items oi ON p.id = oi.product_id
        SET p.stock_quantity = p.stock_quantity + oi.quantity,
            p.sales_count = p.sales_count - oi.quantity
        WHERE oi.order_id = NEW.id;
    END IF;
END$$
DELIMITER ;

-- =====================================================
-- DATOS INICIALES
-- =====================================================

-- Insertar algunos cupones de ejemplo
INSERT INTO coupons (code, name, description, discount_type, discount_value, max_uses, valid_from, valid_until) VALUES
('BIENVENIDO10', 'Descuento de Bienvenida', 'Descuento del 10% para nuevos usuarios', 'percentage', 10.00, NULL, '2025-01-01 00:00:00', '2025-12-31 23:59:59'),
('ENVIOGRATIS', 'Envío Gratuito', 'Envío gratis en pedidos superiores a 30€', 'free_shipping', 0.00, NULL, '2025-01-01 00:00:00', '2025-12-31 23:59:59'),
('CANARIAS5', 'Descuento Canario', 'Descuento especial para productos locales', 'percentage', 5.00, 1000, '2025-01-01 00:00:00', '2025-12-31 23:59:59');

-- =====================================================
-- COMENTARIOS FINALES
-- =====================================================

/*
VENTAJAS DE ESTA ESTRUCTURA VS TU SISTEMA ANTERIOR:

✅ ESCALABLE: Soporta multi-vendedor y crecimiento
✅ TRAZABILIDAD: Historial completo de cambios
✅ FISCAL: Preparado para facturación empresarial
✅ FLEXIBLE: Múltiples métodos de pago y entrega
✅ AUDITORÍA: Registro completo de todas las operaciones
✅ INTEGRACIONES: Preparado para Stripe, Bizum, etc.

FLUJO TÍPICO:
1. Cliente agrega productos al carrito
2. Se crea ORDER con status 'pending'
3. Se agregan ORDER_ITEMS
4. Se procesa PAYMENT
5. ORDER cambia a 'paid'
6. Stock se reduce automáticamente
7. Vendedor prepara pedido ('processing')
8. Cliente recibe producto ('delivered')
9. Opcional: se genera INVOICE fiscal

PRÓXIMOS PASOS:
1. Crear modelos PHP (Order, OrderItem, Payment)
2. Crear API endpoints para pedidos
3. Integrar carrito de la compra
4. Conectar con sistema de pagos
*/
