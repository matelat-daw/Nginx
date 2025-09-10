<?php
/**
 * ProductRepository - Economía Circular Canarias
 * Maneja todas las operaciones de base de datos para productos
 */

require_once __DIR__ . '/../models/Product.php';

class ProductRepository {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Crear un nuevo producto
     */
    public function create(Product $product) {
        try {
            // Generar slug si no existe
            if (empty($product->slug)) {
                $product->slug = $product->generateSlug();
                $product->slug = $this->ensureUniqueSlug($product->slug);
            }
            
            $sql = "INSERT INTO products (
                seller_id, name, description, short_description, price, original_price, cost_price,
                stock_quantity, stock_alert_level, unlimited_stock, category_id, subcategory_id,
                brand, model, weight, dimensions_length, dimensions_width, dimensions_height,
                expiration_date, production_date, origin, ingredients, allergens, nutritional_info,
                product_condition, is_organic, is_local, is_handmade, pickup_location, pickup_island,
                pickup_city, shipping_available, shipping_cost, shipping_time, status, is_featured,
                slug, tags, search_keywords, main_image, gallery_images, video_url
            ) VALUES (
                :seller_id, :name, :description, :short_description, :price, :original_price, :cost_price,
                :stock_quantity, :stock_alert_level, :unlimited_stock, :category_id, :subcategory_id,
                :brand, :model, :weight, :dimensions_length, :dimensions_width, :dimensions_height,
                :expiration_date, :production_date, :origin, :ingredients, :allergens, :nutritional_info,
                :product_condition, :is_organic, :is_local, :is_handmade, :pickup_location, :pickup_island,
                :pickup_city, :shipping_available, :shipping_cost, :shipping_time, :status, :is_featured,
                :slug, :tags, :search_keywords, :main_image, :gallery_images, :video_url
            )";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                ':seller_id' => $product->sellerId,
                ':name' => $product->name,
                ':description' => $product->description,
                ':short_description' => $product->shortDescription,
                ':price' => $product->price,
                ':original_price' => $product->originalPrice,
                ':cost_price' => $product->costPrice,
                ':stock_quantity' => $product->stockQuantity,
                ':stock_alert_level' => $product->stockAlertLevel,
                ':unlimited_stock' => $product->unlimitedStock ? 1 : 0,
                ':category_id' => $product->categoryId,
                ':subcategory_id' => $product->subcategoryId,
                ':brand' => $product->brand,
                ':model' => $product->model,
                ':weight' => $product->weight,
                ':dimensions_length' => $product->dimensionsLength,
                ':dimensions_width' => $product->dimensionsWidth,
                ':dimensions_height' => $product->dimensionsHeight,
                ':expiration_date' => $product->expirationDate,
                ':production_date' => $product->productionDate,
                ':origin' => $product->origin,
                ':ingredients' => $product->ingredients,
                ':allergens' => $product->allergens,
                ':nutritional_info' => is_array($product->nutritionalInfo) ? json_encode($product->nutritionalInfo) : $product->nutritionalInfo,
                ':product_condition' => $product->productCondition,
                ':is_organic' => $product->isOrganic ? 1 : 0,
                ':is_local' => $product->isLocal ? 1 : 0,
                ':is_handmade' => $product->isHandmade ? 1 : 0,
                ':pickup_location' => $product->pickupLocation,
                ':pickup_island' => $product->pickupIsland,
                ':pickup_city' => $product->pickupCity,
                ':shipping_available' => $product->shippingAvailable ? 1 : 0,
                ':shipping_cost' => $product->shippingCost,
                ':shipping_time' => $product->shippingTime,
                ':status' => $product->status,
                ':is_featured' => $product->isFeatured ? 1 : 0,
                ':slug' => $product->slug,
                ':tags' => is_array($product->tags) ? implode(',', $product->tags) : $product->tags,
                ':search_keywords' => $product->searchKeywords,
                ':main_image' => $product->mainImage,
                ':gallery_images' => is_array($product->galleryImages) ? json_encode($product->galleryImages) : $product->galleryImages,
                ':video_url' => $product->videoUrl
            ]);
            
            if ($result) {
                $product->id = $this->pdo->lastInsertId();
                return $product;
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Error creating product: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Buscar producto por ID
     */
    public function findById($id, $includeSeller = false) {
        try {
            $sql = "SELECT p.* FROM products p WHERE p.id = :id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$data) return null;
            
            $product = new Product($data);
            
            // Cargar información del vendedor si se solicita
            if ($includeSeller) {
                $product->seller = $this->loadSeller($product->sellerId);
            }
            
            return $product;
        } catch (PDOException $e) {
            error_log("Error finding product by ID: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Buscar producto por slug
     */
    public function findBySlug($slug, $includeSeller = false) {
        try {
            $sql = "SELECT p.* FROM products p WHERE p.slug = :slug AND p.status = 'active'";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':slug' => $slug]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$data) return null;
            
            $product = new Product($data);
            
            // Incrementar contador de vistas
            $this->incrementViews($product->id);
            
            // Cargar información del vendedor si se solicita
            if ($includeSeller) {
                $product->seller = $this->loadSeller($product->sellerId);
            }
            
            return $product;
        } catch (PDOException $e) {
            error_log("Error finding product by slug: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obtener productos del vendedor
     */
    public function findBySeller($sellerId, $limit = 20, $offset = 0, $status = null) {
        try {
            $sql = "SELECT p.* FROM products p WHERE p.seller_id = :seller_id";
            $params = [':seller_id' => $sellerId];
            
            if ($status) {
                $sql .= " AND p.status = :status";
                $params[':status'] = $status;
            }
            
            $sql .= " ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";
            
            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $products = [];
            foreach ($results as $data) {
                $products[] = new Product($data);
            }
            
            return $products;
        } catch (PDOException $e) {
            error_log("Error finding products by seller: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Buscar productos con filtros
     */
    public function search($filters = [], $limit = 20, $offset = 0, $orderBy = 'created_at', $orderDir = 'DESC') {
        try {
            $sql = "SELECT p.* FROM products p WHERE p.status = 'active'";
            $params = [];
            
            // Filtro por categoría
            if (!empty($filters['category_id'])) {
                $sql .= " AND p.category_id = :category_id";
                $params[':category_id'] = $filters['category_id'];
            }
            
            // Filtro por subcategoría
            if (!empty($filters['subcategory_id'])) {
                $sql .= " AND p.subcategory_id = :subcategory_id";
                $params[':subcategory_id'] = $filters['subcategory_id'];
            }
            
            // Filtro por isla
            if (!empty($filters['island'])) {
                $sql .= " AND p.pickup_island = :island";
                $params[':island'] = $filters['island'];
            }
            
            // Filtro por ciudad
            if (!empty($filters['city'])) {
                $sql .= " AND p.pickup_city = :city";
                $params[':city'] = $filters['city'];
            }
            
            // Filtro por rango de precios
            if (!empty($filters['min_price'])) {
                $sql .= " AND p.price >= :min_price";
                $params[':min_price'] = $filters['min_price'];
            }
            
            if (!empty($filters['max_price'])) {
                $sql .= " AND p.price <= :max_price";
                $params[':max_price'] = $filters['max_price'];
            }
            
            // Filtro por condición
            if (!empty($filters['condition'])) {
                $sql .= " AND p.product_condition = :condition";
                $params[':condition'] = $filters['condition'];
            }
            
            // Filtros booleanos
            if (isset($filters['is_organic'])) {
                $sql .= " AND p.is_organic = :is_organic";
                $params[':is_organic'] = $filters['is_organic'] ? 1 : 0;
            }
            
            if (isset($filters['is_local'])) {
                $sql .= " AND p.is_local = :is_local";
                $params[':is_local'] = $filters['is_local'] ? 1 : 0;
            }
            
            if (isset($filters['is_handmade'])) {
                $sql .= " AND p.is_handmade = :is_handmade";
                $params[':is_handmade'] = $filters['is_handmade'] ? 1 : 0;
            }
            
            if (isset($filters['shipping_available'])) {
                $sql .= " AND p.shipping_available = :shipping_available";
                $params[':shipping_available'] = $filters['shipping_available'] ? 1 : 0;
            }
            
            // Búsqueda de texto
            if (!empty($filters['search'])) {
                $sql .= " AND (MATCH(p.name, p.description, p.tags, p.search_keywords) AGAINST(:search IN NATURAL LANGUAGE MODE)
                         OR p.name LIKE :search_like OR p.description LIKE :search_like)";
                $params[':search'] = $filters['search'];
                $params[':search_like'] = '%' . $filters['search'] . '%';
            }
            
            // Solo productos destacados
            if (!empty($filters['featured'])) {
                $sql .= " AND p.is_featured = 1";
            }
            
            // Ordenamiento
            $validOrderBy = ['created_at', 'price', 'name', 'rating_average', 'views_count', 'favorites_count'];
            if (!in_array($orderBy, $validOrderBy)) {
                $orderBy = 'created_at';
            }
            
            $orderDir = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';
            $sql .= " ORDER BY p.{$orderBy} {$orderDir}";
            
            // Paginación
            $sql .= " LIMIT :limit OFFSET :offset";
            
            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $products = [];
            foreach ($results as $data) {
                $products[] = new Product($data);
            }
            
            return $products;
        } catch (PDOException $e) {
            error_log("Error searching products: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Actualizar producto
     */
    public function update(Product $product) {
        try {
            $sql = "UPDATE products SET 
                name = :name, description = :description, short_description = :short_description,
                price = :price, original_price = :original_price, cost_price = :cost_price,
                stock_quantity = :stock_quantity, stock_alert_level = :stock_alert_level,
                unlimited_stock = :unlimited_stock, category_id = :category_id, subcategory_id = :subcategory_id,
                brand = :brand, model = :model, weight = :weight, dimensions_length = :dimensions_length,
                dimensions_width = :dimensions_width, dimensions_height = :dimensions_height,
                expiration_date = :expiration_date, production_date = :production_date, origin = :origin,
                ingredients = :ingredients, allergens = :allergens, nutritional_info = :nutritional_info,
                product_condition = :product_condition, is_organic = :is_organic, is_local = :is_local,
                is_handmade = :is_handmade, pickup_location = :pickup_location, pickup_island = :pickup_island,
                pickup_city = :pickup_city, shipping_available = :shipping_available, shipping_cost = :shipping_cost,
                shipping_time = :shipping_time, status = :status, is_featured = :is_featured, slug = :slug,
                tags = :tags, search_keywords = :search_keywords, main_image = :main_image,
                gallery_images = :gallery_images, video_url = :video_url, updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':id' => $product->id,
                ':name' => $product->name,
                ':description' => $product->description,
                ':short_description' => $product->shortDescription,
                ':price' => $product->price,
                ':original_price' => $product->originalPrice,
                ':cost_price' => $product->costPrice,
                ':stock_quantity' => $product->stockQuantity,
                ':stock_alert_level' => $product->stockAlertLevel,
                ':unlimited_stock' => $product->unlimitedStock ? 1 : 0,
                ':category_id' => $product->categoryId,
                ':subcategory_id' => $product->subcategoryId,
                ':brand' => $product->brand,
                ':model' => $product->model,
                ':weight' => $product->weight,
                ':dimensions_length' => $product->dimensionsLength,
                ':dimensions_width' => $product->dimensionsWidth,
                ':dimensions_height' => $product->dimensionsHeight,
                ':expiration_date' => $product->expirationDate,
                ':production_date' => $product->productionDate,
                ':origin' => $product->origin,
                ':ingredients' => $product->ingredients,
                ':allergens' => $product->allergens,
                ':nutritional_info' => is_array($product->nutritionalInfo) ? json_encode($product->nutritionalInfo) : $product->nutritionalInfo,
                ':product_condition' => $product->productCondition,
                ':is_organic' => $product->isOrganic ? 1 : 0,
                ':is_local' => $product->isLocal ? 1 : 0,
                ':is_handmade' => $product->isHandmade ? 1 : 0,
                ':pickup_location' => $product->pickupLocation,
                ':pickup_island' => $product->pickupIsland,
                ':pickup_city' => $product->pickupCity,
                ':shipping_available' => $product->shippingAvailable ? 1 : 0,
                ':shipping_cost' => $product->shippingCost,
                ':shipping_time' => $product->shippingTime,
                ':status' => $product->status,
                ':is_featured' => $product->isFeatured ? 1 : 0,
                ':slug' => $product->slug,
                ':tags' => is_array($product->tags) ? implode(',', $product->tags) : $product->tags,
                ':search_keywords' => $product->searchKeywords,
                ':main_image' => $product->mainImage,
                ':gallery_images' => is_array($product->galleryImages) ? json_encode($product->galleryImages) : $product->galleryImages,
                ':video_url' => $product->videoUrl
            ]);
        } catch (PDOException $e) {
            error_log("Error updating product: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Eliminar producto
     */
    public function delete($id) {
        try {
            $sql = "DELETE FROM products WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log("Error deleting product: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener categorías
     */
    public function getCategories($parentId = null) {
        try {
            $sql = "SELECT * FROM product_categories WHERE is_active = 1";
            $params = [];
            
            if ($parentId === null) {
                $sql .= " AND parent_id IS NULL";
            } else {
                $sql .= " AND parent_id = :parent_id";
                $params[':parent_id'] = $parentId;
            }
            
            $sql .= " ORDER BY sort_order, name";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting categories: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Actualizar stock del producto
     */
    public function updateStock($productId, $newStock) {
        try {
            $sql = "UPDATE products SET stock_quantity = :stock, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([':id' => $productId, ':stock' => $newStock]);
        } catch (PDOException $e) {
            error_log("Error updating stock: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Incrementar contador de vistas
     */
    private function incrementViews($productId) {
        try {
            $sql = "UPDATE products SET views_count = views_count + 1 WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $productId]);
        } catch (PDOException $e) {
            error_log("Error incrementing views: " . $e->getMessage());
        }
    }
    
    /**
     * Cargar información del vendedor
     */
    private function loadSeller($sellerId) {
        try {
            $sql = "SELECT id, first_name, last_name, profile_image, island, city FROM users WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $sellerId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error loading seller: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Asegurar que el slug sea único
     */
    private function ensureUniqueSlug($slug) {
        try {
            $originalSlug = $slug;
            $counter = 1;
            
            while ($this->slugExists($slug)) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }
            
            return $slug;
        } catch (Exception $e) {
            error_log("Error ensuring unique slug: " . $e->getMessage());
            return $slug . '-' . time();
        }
    }
    
    /**
     * Verificar si el slug existe
     */
    private function slugExists($slug) {
        try {
            $sql = "SELECT COUNT(*) FROM products WHERE slug = :slug";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':slug' => $slug]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Error checking slug existence: " . $e->getMessage());
            return true; // Por seguridad, asumimos que existe
        }
    }
}
