-- Script para permitir NULL en claves foráneas de order_items
-- Ejecutar manualmente en MySQL/phpMyAdmin

USE canarias_ec;

-- Primero, eliminar las claves foráneas existentes
ALTER TABLE order_items 
    DROP FOREIGN KEY order_items_ibfk_2,
    DROP FOREIGN KEY order_items_ibfk_3;

-- Modificar las columnas para permitir NULL
ALTER TABLE order_items 
    MODIFY COLUMN product_id INT NULL,
    MODIFY COLUMN seller_id INT NULL;

-- Recrear las claves foráneas con ON DELETE SET NULL
ALTER TABLE order_items
    ADD CONSTRAINT fk_order_items_product 
        FOREIGN KEY (product_id) REFERENCES products(id) 
        ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT fk_order_items_seller 
        FOREIGN KEY (seller_id) REFERENCES users(id) 
        ON DELETE SET NULL ON UPDATE CASCADE;

-- Verificar cambios
DESCRIBE order_items;
