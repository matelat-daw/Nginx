-- =====================================================
-- ECONOMÍA CIRCULAR CANARIAS - ESTRUCTURA DE PRODUCTOS
-- Base de datos genérica para productos (alimentos y artículos)
-- =====================================================

-- Eliminar tablas existentes si existen (en orden correcto por dependencias)
DROP TABLE IF EXISTS product_favorites;
DROP TABLE IF EXISTS product_reviews;
DROP TABLE IF EXISTS product_variants;
DROP TABLE IF EXISTS product_attributes;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS product_categories;

-- Tabla principal de productos
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Relación con el vendedor (usuario)
    seller_id INT NOT NULL,
    
    -- Información básica del producto
    name VARCHAR(255) NOT NULL,
    description TEXT,
    short_description VARCHAR(500),
    
    -- Precios
    price DECIMAL(10,2) NOT NULL,
    original_price DECIMAL(10,2), -- Para descuentos
    cost_price DECIMAL(10,2), -- Precio de coste (privado)
    
    -- Inventario
    stock_quantity INT NOT NULL DEFAULT 0,
    stock_alert_level INT DEFAULT 5, -- Alerta de stock bajo
    unlimited_stock BOOLEAN DEFAULT FALSE, -- Para servicios
    
    -- Clasificación
    category_id INT,
    subcategory_id INT,
    brand VARCHAR(100),
    model VARCHAR(100),
    
    -- Características físicas
    weight DECIMAL(8,3), -- En kg
    dimensions_length DECIMAL(8,2), -- En cm
    dimensions_width DECIMAL(8,2),
    dimensions_height DECIMAL(8,2),
    
    -- Características específicas de alimentos
    expiration_date DATE,
    production_date DATE,
    origin VARCHAR(100), -- Lugar de origen/producción
    ingredients TEXT, -- Para alimentos
    allergens TEXT, -- Alérgenos
    nutritional_info TEXT, -- JSON con información nutricional
    
    -- Condición y estado
    product_condition ENUM('nuevo', 'como_nuevo', 'bueno', 'aceptable', 'para_reparar') DEFAULT 'nuevo',
    is_organic BOOLEAN DEFAULT FALSE,
    is_local BOOLEAN DEFAULT TRUE, -- Producto canario
    is_handmade BOOLEAN DEFAULT FALSE,
    
    -- Ubicación y logística
    pickup_location VARCHAR(255), -- Dirección de recogida
    pickup_island ENUM('Gran Canaria', 'Tenerife', 'Lanzarote', 'Fuerteventura', 'La Palma', 'La Gomera', 'El Hierro'),
    pickup_city VARCHAR(100),
    shipping_available BOOLEAN DEFAULT FALSE,
    shipping_cost DECIMAL(8,2),
    shipping_time VARCHAR(50), -- "1-3 días", "Inmediato", etc.
    
    -- Estado del producto
    status ENUM('draft', 'active', 'inactive', 'sold', 'expired') DEFAULT 'draft',
    is_featured BOOLEAN DEFAULT FALSE,
    
    -- SEO y búsqueda
    slug VARCHAR(255) UNIQUE,
    tags TEXT, -- Tags separados por comas
    search_keywords TEXT,
    
    -- Multimedia
    main_image VARCHAR(500),
    gallery_images TEXT, -- JSON array de URLs de imágenes
    video_url VARCHAR(500),
    
    -- Métricas
    views_count INT DEFAULT 0,
    favorites_count INT DEFAULT 0,
    sales_count INT DEFAULT 0,
    rating_average DECIMAL(3,2) DEFAULT 0.00,
    rating_count INT DEFAULT 0,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    published_at TIMESTAMP NULL,
    
    -- Índices
    INDEX idx_seller (seller_id),
    INDEX idx_category (category_id),
    INDEX idx_status (status),
    INDEX idx_island (pickup_island),
    INDEX idx_city (pickup_city),
    INDEX idx_price (price),
    INDEX idx_created (created_at),
    INDEX idx_featured (is_featured),
    INDEX idx_name (name(100)),
    
    -- Clave foránea
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabla de categorías
CREATE TABLE IF NOT EXISTS product_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    parent_id INT NULL, -- Para subcategorías
    icon VARCHAR(100), -- Emoji o nombre de icono
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_parent (parent_id),
    INDEX idx_active (is_active),
    FOREIGN KEY (parent_id) REFERENCES product_categories(id) ON DELETE SET NULL
);

-- Tabla de atributos personalizados (para flexibilidad)
CREATE TABLE IF NOT EXISTS product_attributes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    attribute_name VARCHAR(100) NOT NULL,
    attribute_value TEXT NOT NULL,
    attribute_type ENUM('text', 'number', 'boolean', 'date', 'json') DEFAULT 'text',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_product (product_id),
    INDEX idx_name (attribute_name),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Tabla de variantes de producto (tallas, colores, etc.)
CREATE TABLE IF NOT EXISTS product_variants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    variant_name VARCHAR(100) NOT NULL, -- "Talla", "Color", "Sabor"
    variant_value VARCHAR(100) NOT NULL, -- "M", "Rojo", "Chocolate"
    price_modifier DECIMAL(8,2) DEFAULT 0.00, -- +/- precio base
    stock_quantity INT DEFAULT 0,
    sku VARCHAR(100), -- Código único de variante
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_product (product_id),
    INDEX idx_active (is_active),
    UNIQUE KEY unique_variant (product_id, variant_name, variant_value),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Tabla de reseñas/valoraciones
CREATE TABLE IF NOT EXISTS product_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    buyer_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    title VARCHAR(200),
    comment TEXT,
    is_verified_purchase BOOLEAN DEFAULT FALSE,
    is_approved BOOLEAN DEFAULT TRUE,
    helpful_votes INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_product (product_id),
    INDEX idx_buyer (buyer_id),
    INDEX idx_rating (rating),
    INDEX idx_approved (is_approved),
    UNIQUE KEY unique_review (product_id, buyer_id), -- Un review por comprador
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabla de favoritos
CREATE TABLE IF NOT EXISTS product_favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user (user_id),
    INDEX idx_product (product_id),
    UNIQUE KEY unique_favorite (user_id, product_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- =====================================================
-- DATOS INICIALES
-- =====================================================

-- Insertar categorías principales
INSERT IGNORE INTO product_categories (name, slug, description, icon, sort_order) VALUES
('Alimentos y Bebidas', 'alimentos-bebidas', 'Productos alimentarios locales y artesanales', '🍎', 1),
('Artesanía', 'artesania', 'Productos artesanales y hechos a mano', '🎨', 2),
('Moda y Complementos', 'moda-complementos', 'Ropa, calzado y accesorios', '👕', 3),
('Hogar y Jardín', 'hogar-jardin', 'Decoración, muebles y jardinería', '🏠', 4),
('Tecnología', 'tecnologia', 'Electrónicos y gadgets', '📱', 5),
('Deportes y Ocio', 'deportes-ocio', 'Equipamiento deportivo y entretenimiento', '⚽', 6),
('Libros y Media', 'libros-media', 'Libros, música y contenido multimedia', '📚', 7),
('Servicios', 'servicios', 'Servicios profesionales y personales', '🔧', 8),
('Vehículos', 'vehiculos', 'Automóviles, motos y transporte', '🚗', 9),
('Otros', 'otros', 'Productos diversos', '📦', 10);

-- Insertar subcategorías de alimentos
INSERT IGNORE INTO product_categories (name, slug, description, parent_id, sort_order) VALUES
('Frutas y Verduras', 'frutas-verduras', 'Productos frescos del campo canario', 1, 1),
('Productos Lácteos', 'lacteos', 'Quesos, leche y derivados', 1, 2),
('Carnes y Pescados', 'carnes-pescados', 'Productos cárnicos y del mar', 1, 3),
('Panadería y Repostería', 'panaderia-reposteria', 'Pan, dulces y bollería', 1, 4),
('Conservas y Mermeladas', 'conservas-mermeladas', 'Productos en conserva artesanales', 1, 5),
('Vinos y Licores', 'vinos-licores', 'Bebidas alcohólicas locales', 1, 6),
('Condimentos y Especias', 'condimentos-especias', 'Sal, especias y condimentos', 1, 7);

-- Insertar subcategorías de artesanía
INSERT IGNORE INTO product_categories (name, slug, description, parent_id, sort_order) VALUES
('Cerámica', 'ceramica', 'Productos de cerámica y alfarería', 2, 1),
('Textil', 'textil', 'Tejidos y productos textiles artesanales', 2, 2),
('Madera', 'madera', 'Productos tallados y trabajos en madera', 2, 3),
('Joyería', 'joyeria', 'Joyas y bisutería artesanal', 2, 4),
('Decoración', 'decoracion', 'Objetos decorativos únicos', 2, 5);

-- =====================================================
-- TRIGGERS Y FUNCIONES
-- =====================================================

-- Eliminar triggers existentes si existen
DROP TRIGGER IF EXISTS products_generate_slug;
DROP TRIGGER IF EXISTS update_product_rating;
DROP TRIGGER IF EXISTS update_favorites_count;
DROP TRIGGER IF EXISTS update_favorites_count_delete;

-- Trigger para actualizar el slug automáticamente
DELIMITER $$
CREATE TRIGGER products_generate_slug 
BEFORE INSERT ON products
FOR EACH ROW
BEGIN
    IF NEW.slug IS NULL OR NEW.slug = '' THEN
        SET NEW.slug = LOWER(REPLACE(REPLACE(REPLACE(REPLACE(NEW.name, ' ', '-'), 'á', 'a'), 'é', 'e'), 'í', 'i'));
        SET NEW.slug = REPLACE(REPLACE(REPLACE(NEW.slug, 'ó', 'o'), 'ú', 'u'), 'ñ', 'n');
    END IF;
END$$
DELIMITER ;

-- Trigger para actualizar rating promedio
DELIMITER $$
CREATE TRIGGER update_product_rating 
AFTER INSERT ON product_reviews
FOR EACH ROW
BEGIN
    UPDATE products 
    SET rating_average = (
        SELECT AVG(rating) 
        FROM product_reviews 
        WHERE product_id = NEW.product_id AND is_approved = TRUE
    ),
    rating_count = (
        SELECT COUNT(*) 
        FROM product_reviews 
        WHERE product_id = NEW.product_id AND is_approved = TRUE
    )
    WHERE id = NEW.product_id;
END$$
DELIMITER ;

-- Trigger para actualizar contador de favoritos
DELIMITER $$
CREATE TRIGGER update_favorites_count 
AFTER INSERT ON product_favorites
FOR EACH ROW
BEGIN
    UPDATE products 
    SET favorites_count = (
        SELECT COUNT(*) 
        FROM product_favorites 
        WHERE product_id = NEW.product_id
    )
    WHERE id = NEW.product_id;
END$$

CREATE TRIGGER update_favorites_count_delete 
AFTER DELETE ON product_favorites
FOR EACH ROW
BEGIN
    UPDATE products 
    SET favorites_count = (
        SELECT COUNT(*) 
        FROM product_favorites 
        WHERE product_id = OLD.product_id
    )
    WHERE id = OLD.product_id;
END$$
DELIMITER ;

-- =====================================================
-- COMENTARIOS FINALES
-- =====================================================

/*
CARACTERÍSTICAS DE ESTA ESTRUCTURA:

✅ GENÉRICA: Funciona para alimentos, artículos, servicios
✅ FLEXIBLE: Atributos personalizados para casos especiales
✅ COMPLETA: Incluye inventario, ubicación, multimedia, reseñas
✅ ESCALABLE: Preparada para crecimiento futuro
✅ CANARIA: Campos específicos para las islas
✅ E-COMMERCE: Lista para carrito y pagos

PRÓXIMOS PASOS:
1. Crear modelo Product.php
2. Crear ProductRepository.php
3. Crear API endpoints para productos
4. Integrar con el carrito de la compra
*/
