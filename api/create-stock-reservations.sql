-- =====================================================
-- TABLA DE RESERVAS DE STOCK TEMPORAL
-- Gestiona productos reservados en carritos temporalmente
-- =====================================================

CREATE TABLE IF NOT EXISTS stock_reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Información de la reserva
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    session_token VARCHAR(255),
    
    -- Cantidad reservada
    quantity_reserved INT NOT NULL DEFAULT 1,
    
    -- Estado de la reserva
    status ENUM('active', 'confirmed', 'expired', 'cancelled') DEFAULT 'active',
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL, -- Reserva válida por 30 minutos
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Índices para rendimiento
    INDEX idx_user_product (user_id, product_id),
    INDEX idx_product (product_id),
    INDEX idx_expires (expires_at),
    INDEX idx_status (status),
    INDEX idx_session (session_token),
    
    -- Claves foráneas
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    
    -- Evitar duplicados para el mismo usuario/producto
    UNIQUE KEY unique_user_product_active (user_id, product_id, status)
);

-- =====================================================
-- TRIGGERS PARA GESTIÓN AUTOMÁTICA DE STOCK
-- =====================================================

-- Trigger para limpiar reservas expiradas automáticamente
DELIMITER $$
CREATE EVENT IF NOT EXISTS cleanup_expired_reservations
ON SCHEDULE EVERY 5 MINUTE
DO
BEGIN
    -- Marcar como expiradas las reservas vencidas
    UPDATE stock_reservations 
    SET status = 'expired' 
    WHERE status = 'active' 
    AND expires_at < NOW();
    
    -- Opcional: Eliminar reservas muy antiguas (más de 24 horas)
    DELETE FROM stock_reservations 
    WHERE status IN ('expired', 'cancelled') 
    AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR);
END$$
DELIMITER ;

-- Trigger para actualizar stock disponible automáticamente
DELIMITER $$
CREATE TRIGGER update_available_stock_on_reservation
AFTER INSERT ON stock_reservations
FOR EACH ROW
BEGIN
    -- Solo para reservas activas
    IF NEW.status = 'active' THEN
        -- Actualizar el stock disponible (no el real, solo el disponible)
        UPDATE products 
        SET stock_available = stock_quantity - (
            SELECT COALESCE(SUM(quantity_reserved), 0)
            FROM stock_reservations 
            WHERE product_id = NEW.product_id 
            AND status = 'active'
        )
        WHERE id = NEW.product_id;
    END IF;
END$$

CREATE TRIGGER update_available_stock_on_reservation_update
AFTER UPDATE ON stock_reservations
FOR EACH ROW
BEGIN
    -- Recalcular stock disponible cuando cambie el estado
    UPDATE products 
    SET stock_available = stock_quantity - (
        SELECT COALESCE(SUM(quantity_reserved), 0)
        FROM stock_reservations 
        WHERE product_id = NEW.product_id 
        AND status = 'active'
    )
    WHERE id = NEW.product_id;
END$$

CREATE TRIGGER update_available_stock_on_reservation_delete
AFTER DELETE ON stock_reservations
FOR EACH ROW
BEGIN
    -- Recalcular stock disponible cuando se elimine una reserva
    UPDATE products 
    SET stock_available = stock_quantity - (
        SELECT COALESCE(SUM(quantity_reserved), 0)
        FROM stock_reservations 
        WHERE product_id = OLD.product_id 
        AND status = 'active'
    )
    WHERE id = OLD.product_id;
END$$
DELIMITER ;

-- =====================================================
-- AGREGAR COLUMNA DE STOCK DISPONIBLE A PRODUCTOS
-- =====================================================

-- Agregar columna si no existe
ALTER TABLE products ADD COLUMN IF NOT EXISTS stock_available INT DEFAULT 0;

-- Inicializar stock disponible igual al stock total
UPDATE products SET stock_available = stock_quantity WHERE stock_available IS NULL;

-- =====================================================
-- PROCEDIMIENTOS ALMACENADOS PARA GESTIÓN DE RESERVAS
-- =====================================================

-- Procedimiento para reservar stock
DELIMITER $$
CREATE PROCEDURE ReserveStock(
    IN p_user_id INT,
    IN p_product_id INT,
    IN p_quantity INT,
    IN p_session_token VARCHAR(255),
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(255)
)
BEGIN
    DECLARE v_available_stock INT DEFAULT 0;
    DECLARE v_existing_reservation INT DEFAULT 0;
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_success = FALSE;
        SET p_message = 'Error interno al reservar stock';
    END;
    
    START TRANSACTION;
    
    -- Limpiar reservas expiradas primero
    UPDATE stock_reservations 
    SET status = 'expired' 
    WHERE status = 'active' 
    AND expires_at < NOW();
    
    -- Verificar stock disponible
    SELECT stock_available INTO v_available_stock
    FROM products 
    WHERE id = p_product_id 
    AND status = 'active';
    
    IF v_available_stock IS NULL THEN
        SET p_success = FALSE;
        SET p_message = 'Producto no encontrado o inactivo';
        ROLLBACK;
    ELSEIF v_available_stock < p_quantity THEN
        SET p_success = FALSE;
        SET p_message = CONCAT('Stock insuficiente. Disponible: ', v_available_stock);
        ROLLBACK;
    ELSE
        -- Verificar si ya existe una reserva activa para este usuario/producto
        SELECT id INTO v_existing_reservation
        FROM stock_reservations
        WHERE user_id = p_user_id 
        AND product_id = p_product_id 
        AND status = 'active'
        LIMIT 1;
        
        IF v_existing_reservation IS NOT NULL THEN
            -- Actualizar reserva existente
            UPDATE stock_reservations
            SET quantity_reserved = p_quantity,
                expires_at = DATE_ADD(NOW(), INTERVAL 30 MINUTE),
                session_token = p_session_token
            WHERE id = v_existing_reservation;
        ELSE
            -- Crear nueva reserva
            INSERT INTO stock_reservations (
                user_id, product_id, quantity_reserved, session_token, expires_at
            ) VALUES (
                p_user_id, p_product_id, p_quantity, p_session_token,
                DATE_ADD(NOW(), INTERVAL 30 MINUTE)
            );
        END IF;
        
        SET p_success = TRUE;
        SET p_message = 'Stock reservado correctamente';
        COMMIT;
    END IF;
END$$
DELIMITER ;

-- Procedimiento para confirmar compra (convertir reserva a venta)
DELIMITER $$
CREATE PROCEDURE ConfirmPurchase(
    IN p_user_id INT,
    IN p_product_id INT,
    IN p_quantity INT,
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(255)
)
BEGIN
    DECLARE v_reserved_quantity INT DEFAULT 0;
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_success = FALSE;
        SET p_message = 'Error interno al confirmar compra';
    END;
    
    START TRANSACTION;
    
    -- Verificar reserva activa
    SELECT quantity_reserved INTO v_reserved_quantity
    FROM stock_reservations
    WHERE user_id = p_user_id 
    AND product_id = p_product_id 
    AND status = 'active'
    LIMIT 1;
    
    IF v_reserved_quantity IS NULL THEN
        SET p_success = FALSE;
        SET p_message = 'No hay reserva activa para este producto';
        ROLLBACK;
    ELSEIF v_reserved_quantity < p_quantity THEN
        SET p_success = FALSE;
        SET p_message = 'Cantidad solicitada mayor a la reservada';
        ROLLBACK;
    ELSE
        -- Confirmar la reserva
        UPDATE stock_reservations
        SET status = 'confirmed'
        WHERE user_id = p_user_id 
        AND product_id = p_product_id 
        AND status = 'active';
        
        -- Reducir stock real del producto
        UPDATE products
        SET stock_quantity = stock_quantity - p_quantity,
            sales_count = sales_count + p_quantity
        WHERE id = p_product_id;
        
        SET p_success = TRUE;
        SET p_message = 'Compra confirmada correctamente';
        COMMIT;
    END IF;
END$$
DELIMITER ;

-- Procedimiento para liberar reservas de un usuario
DELIMITER $$
CREATE PROCEDURE ReleaseUserReservations(
    IN p_user_id INT,
    IN p_session_token VARCHAR(255)
)
BEGIN
    UPDATE stock_reservations
    SET status = 'cancelled'
    WHERE (user_id = p_user_id OR session_token = p_session_token)
    AND status = 'active';
END$$
DELIMITER ;

-- =====================================================
-- COMENTARIOS FINALES
-- =====================================================

/*
FUNCIONALIDADES IMPLEMENTADAS:

✅ RESERVA TEMPORAL: Al agregar al carrito se reserva por 30 minutos
✅ STOCK DISPONIBLE: Campo separado que refleja stock real - reservas activas  
✅ LIMPIEZA AUTOMÁTICA: Event scheduler limpia reservas expiradas cada 5 minutos
✅ TRIGGERS AUTOMÁTICOS: Mantienen sincronizado el stock disponible
✅ PROCEDIMIENTOS: Funciones seguras para reservar, confirmar y liberar
✅ GESTIÓN DE SESIONES: Asocia reservas a tokens de sesión
✅ PREVENCIÓN DE DUPLICADOS: Evita múltiples reservas del mismo producto por usuario

FLUJO DE TRABAJO:
1. Usuario agrega producto al carrito → Reserva temporal (30 min)
2. Stock disponible se reduce automáticamente
3. Si usuario completa compra → Reserva se confirma, stock real se reduce
4. Si token expira o sesión termina → Reserva se libera, stock disponible se restaura
5. Limpieza automática cada 5 minutos de reservas vencidas

USO EN EL CÓDIGO:
- Al agregar al carrito: CALL ReserveStock(user_id, product_id, quantity, token, @success, @message)
- Al completar compra: CALL ConfirmPurchase(user_id, product_id, quantity, @success, @message)  
- Al cerrar sesión: CALL ReleaseUserReservations(user_id, token)
*/
