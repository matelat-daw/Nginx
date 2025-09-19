<?php
/**
 * Modelo Product - Economía Circular Canarias
 * Representa un producto del sistema con validación y mapeo automático
 */

class Product {
    // Propiedades principales
    public $id;
    public $sellerId;
    public $name;
    public $description;
    public $shortDescription;
    
    // Precios
    public $price;
    public $originalPrice;
    public $costPrice;
    
    // Inventario
    public $stockQuantity;
    public $stockAlertLevel;
    public $unlimitedStock;
    
    // Clasificación
    public $categoryId;
    public $subcategoryId;
    public $brand;
    public $model;
    
    // Características físicas
    public $weight;
    public $dimensionsLength;
    public $dimensionsWidth;
    public $dimensionsHeight;
    
    // Características específicas de alimentos
    public $expirationDate;
    public $productionDate;
    public $origin;
    public $ingredients;
    public $allergens;
    public $nutritionalInfo;
    
    // Condición y estado
    public $productCondition;
    public $isOrganic;
    public $isLocal;
    public $isHandmade;
    
    // Ubicación y logística
    public $pickupLocation;
    public $pickupIsland;
    public $pickupCity;
    public $shippingAvailable;
    public $shippingCost;
    public $shippingTime;
    
    // Estado del producto
    public $status;
    public $isFeatured;
    
    // SEO y búsqueda
    public $slug;
    public $tags;
    public $searchKeywords;
    
    // Multimedia
    public $mainImage;
    public $galleryImages;
    public $videoUrl;
    
    // Métricas
    public $viewsCount;
    public $favoritesCount;
    public $salesCount;
    public $ratingAverage;
    public $ratingCount;
    
    // Timestamps
    public $createdAt;
    public $updatedAt;
    public $publishedAt;
    
    // Propiedades relacionadas (cargadas bajo demanda)
    public $seller;
    public $category;
    public $subcategory;
    public $reviews;
    public $variants;
    public $attributes;
    
    private $validationErrors = [];
    
    /**
     * Constructor - Inicializar desde array de datos
     */
    public function __construct($data = []) {
        if (!empty($data)) {
            $this->fillFromArray($data);
        }
        
        // Valores por defecto
        $this->stockQuantity = $this->stockQuantity ?? 0;
        $this->stockAlertLevel = $this->stockAlertLevel ?? 5;
        $this->unlimitedStock = $this->unlimitedStock ?? false;
        $this->productCondition = $this->productCondition ?? 'nuevo';
        $this->isOrganic = $this->isOrganic ?? false;
        $this->isLocal = $this->isLocal ?? true;
        $this->isHandmade = $this->isHandmade ?? false;
        $this->shippingAvailable = $this->shippingAvailable ?? false;
        $this->status = $this->status ?? 'draft';
        $this->isFeatured = $this->isFeatured ?? false;
        $this->viewsCount = $this->viewsCount ?? 0;
        $this->favoritesCount = $this->favoritesCount ?? 0;
        $this->salesCount = $this->salesCount ?? 0;
        $this->ratingAverage = $this->ratingAverage ?? 0.00;
        $this->ratingCount = $this->ratingCount ?? 0;
    }
    
    /**
     * Llenar propiedades desde array de datos
     */
    public function fillFromArray($data) {
        // Campos principales
        $this->id = $data['id'] ?? $data['Id'] ?? null;
        $this->sellerId = $data['seller_id'] ?? $data['sellerId'] ?? $data['SellerId'] ?? null;
        $this->name = $data['name'] ?? $data['Name'] ?? null;
        $this->description = $data['description'] ?? $data['Description'] ?? null;
        $this->shortDescription = $data['short_description'] ?? $data['shortDescription'] ?? $data['ShortDescription'] ?? null;
        
        // Precios
        $this->price = $this->parseDecimal($data['price'] ?? $data['Price'] ?? null);
        $this->originalPrice = $this->parseDecimal($data['original_price'] ?? $data['originalPrice'] ?? $data['OriginalPrice'] ?? null);
        $this->costPrice = $this->parseDecimal($data['cost_price'] ?? $data['costPrice'] ?? $data['CostPrice'] ?? null);
        
        // Inventario
        $this->stockQuantity = $this->parseInt($data['stock_quantity'] ?? $data['stockQuantity'] ?? $data['StockQuantity'] ?? 0);
        $this->stockAlertLevel = $this->parseInt($data['stock_alert_level'] ?? $data['stockAlertLevel'] ?? $data['StockAlertLevel'] ?? 5);
        $this->unlimitedStock = $this->parseBool($data['unlimited_stock'] ?? $data['unlimitedStock'] ?? $data['UnlimitedStock'] ?? false);
        
        // Clasificación
        $this->categoryId = $this->parseInt($data['category_id'] ?? $data['categoryId'] ?? $data['CategoryId'] ?? null);
        $this->subcategoryId = $this->parseInt($data['subcategory_id'] ?? $data['subcategoryId'] ?? $data['SubcategoryId'] ?? null);
        $this->brand = $data['brand'] ?? $data['Brand'] ?? null;
        $this->model = $data['model'] ?? $data['Model'] ?? null;
        
        // Características físicas
        $this->weight = $this->parseDecimal($data['weight'] ?? $data['Weight'] ?? null);
        $this->dimensionsLength = $this->parseDecimal($data['dimensions_length'] ?? $data['dimensionsLength'] ?? null);
        $this->dimensionsWidth = $this->parseDecimal($data['dimensions_width'] ?? $data['dimensionsWidth'] ?? null);
        $this->dimensionsHeight = $this->parseDecimal($data['dimensions_height'] ?? $data['dimensionsHeight'] ?? null);
        
        // Características específicas de alimentos
        $this->expirationDate = $data['expiration_date'] ?? $data['expirationDate'] ?? null;
        $this->productionDate = $data['production_date'] ?? $data['productionDate'] ?? null;
        $this->origin = $data['origin'] ?? $data['Origin'] ?? null;
        $this->ingredients = $data['ingredients'] ?? $data['Ingredients'] ?? null;
        $this->allergens = $data['allergens'] ?? $data['Allergens'] ?? null;
        $this->nutritionalInfo = $data['nutritional_info'] ?? $data['nutritionalInfo'] ?? null;
        
        // Condición y estado
        $this->productCondition = $data['product_condition'] ?? $data['productCondition'] ?? 'nuevo';
        $this->isOrganic = $this->parseBool($data['is_organic'] ?? $data['isOrganic'] ?? false);
        $this->isLocal = $this->parseBool($data['is_local'] ?? $data['isLocal'] ?? true);
        $this->isHandmade = $this->parseBool($data['is_handmade'] ?? $data['isHandmade'] ?? false);
        
        // Ubicación y logística
        $this->pickupLocation = $data['pickup_location'] ?? $data['pickupLocation'] ?? null;
        $this->pickupIsland = $data['pickup_island'] ?? $data['pickupIsland'] ?? null;
        $this->pickupCity = $data['pickup_city'] ?? $data['pickupCity'] ?? null;
        $this->shippingAvailable = $this->parseBool($data['shipping_available'] ?? $data['shippingAvailable'] ?? false);
        $this->shippingCost = $this->parseDecimal($data['shipping_cost'] ?? $data['shippingCost'] ?? null);
        $this->shippingTime = $data['shipping_time'] ?? $data['shippingTime'] ?? null;
        
        // Estado del producto
        $this->status = $data['status'] ?? $data['Status'] ?? 'draft';
        $this->isFeatured = $this->parseBool($data['is_featured'] ?? $data['isFeatured'] ?? false);
        
        // SEO y búsqueda
        $this->slug = $data['slug'] ?? $data['Slug'] ?? null;
        $this->tags = $data['tags'] ?? $data['Tags'] ?? null;
        $this->searchKeywords = $data['search_keywords'] ?? $data['searchKeywords'] ?? null;
        
        // Multimedia
        $this->mainImage = $data['main_image'] ?? $data['mainImage'] ?? null;
        $this->galleryImages = $data['gallery_images'] ?? $data['galleryImages'] ?? null;
        $this->videoUrl = $data['video_url'] ?? $data['videoUrl'] ?? null;
        
        // Métricas
        $this->viewsCount = $this->parseInt($data['views_count'] ?? $data['viewsCount'] ?? 0);
        $this->favoritesCount = $this->parseInt($data['favorites_count'] ?? $data['favoritesCount'] ?? 0);
        $this->salesCount = $this->parseInt($data['sales_count'] ?? $data['salesCount'] ?? 0);
        $this->ratingAverage = $this->parseDecimal($data['rating_average'] ?? $data['ratingAverage'] ?? 0.00);
        $this->ratingCount = $this->parseInt($data['rating_count'] ?? $data['ratingCount'] ?? 0);
        
        // Timestamps
        $this->createdAt = $data['created_at'] ?? $data['createdAt'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? $data['updatedAt'] ?? null;
        $this->publishedAt = $data['published_at'] ?? $data['publishedAt'] ?? null;
    }
    
    /**
     * Validar todos los datos del producto
     */
    public function isValid() {
        $this->validationErrors = [];
        
        // Validar seller_id
        if (empty($this->sellerId)) {
            $this->validationErrors[] = 'ID del vendedor es requerido';
        } elseif (!is_numeric($this->sellerId) || $this->sellerId <= 0) {
            $this->validationErrors[] = 'ID del vendedor debe ser un número válido';
        }
        
        // Validar nombre
        if (empty($this->name)) {
            $this->validationErrors[] = 'Nombre del producto es requerido';
        } elseif (strlen($this->name) < 3) {
            $this->validationErrors[] = 'Nombre debe tener al menos 3 caracteres';
        } elseif (strlen($this->name) > 255) {
            $this->validationErrors[] = 'Nombre no puede tener más de 255 caracteres';
        }
        
        // Validar precio
        if (empty($this->price)) {
            $this->validationErrors[] = 'Precio es requerido';
        } elseif (!is_numeric($this->price) || $this->price <= 0) {
            $this->validationErrors[] = 'Precio debe ser un número mayor a 0';
        } elseif ($this->price > 999999.99) {
            $this->validationErrors[] = 'Precio no puede ser mayor a 999,999.99';
        }
        
        // Validar stock (solo si no es unlimited)
        if (!$this->unlimitedStock) {
            if (!is_numeric($this->stockQuantity) || $this->stockQuantity < 0) {
                $this->validationErrors[] = 'Cantidad en stock debe ser un número mayor o igual a 0';
            }
        }
        
        // Validar isla de recogida
        $validIslands = ['Gran Canaria', 'Tenerife', 'Lanzarote', 'Fuerteventura', 'La Palma', 'La Gomera', 'El Hierro'];
        if (!empty($this->pickupIsland) && !in_array($this->pickupIsland, $validIslands)) {
            $this->validationErrors[] = 'Isla de recogida no es válida';
        }
        
        // Validar condición del producto
        $validConditions = ['nuevo', 'como_nuevo', 'bueno', 'aceptable', 'para_reparar'];
        if (!in_array($this->productCondition, $validConditions)) {
            $this->validationErrors[] = 'Condición del producto no es válida';
        }
        
        // Validar estado
        $validStatuses = ['draft', 'active', 'inactive', 'sold', 'expired'];
        if (!in_array($this->status, $validStatuses)) {
            $this->validationErrors[] = 'Estado del producto no es válido';
        }
        
        // Validar fechas para alimentos
        if (!empty($this->expirationDate) && !empty($this->productionDate)) {
            $expDate = new DateTime($this->expirationDate);
            $prodDate = new DateTime($this->productionDate);
            if ($expDate <= $prodDate) {
                $this->validationErrors[] = 'Fecha de caducidad debe ser posterior a la fecha de producción';
            }
        }
        
        // Validar precio original (debe ser mayor que el precio actual si existe)
        if (!empty($this->originalPrice) && $this->originalPrice <= $this->price) {
            $this->validationErrors[] = 'Precio original debe ser mayor que el precio actual';
        }
        
        return empty($this->validationErrors);
    }
    
    /**
     * Obtener errores de validación
     */
    public function getValidationErrors() {
        return $this->validationErrors;
    }
    
    /**
     * Convertir a array para respuesta JSON
     */
    public function toArray($includePrivate = false) {
        $data = [
            'id' => $this->id,
            'sellerId' => $this->sellerId,
            'name' => $this->name,
            'description' => $this->description,
            'shortDescription' => $this->shortDescription,
            'price' => $this->price,
            'originalPrice' => $this->originalPrice,
            'stockQuantity' => $this->stockQuantity,
            'unlimitedStock' => $this->unlimitedStock,
            'categoryId' => $this->categoryId,
            'subcategoryId' => $this->subcategoryId,
            'brand' => $this->brand,
            'model' => $this->model,
            'weight' => $this->weight,
            'dimensionsLength' => $this->dimensionsLength,
            'dimensionsWidth' => $this->dimensionsWidth,
            'dimensionsHeight' => $this->dimensionsHeight,
            'expirationDate' => $this->expirationDate,
            'productionDate' => $this->productionDate,
            'origin' => $this->origin,
            'ingredients' => $this->ingredients,
            'allergens' => $this->allergens,
            'nutritionalInfo' => $this->parseJson($this->nutritionalInfo),
            'productCondition' => $this->productCondition,
            'isOrganic' => $this->isOrganic,
            'isLocal' => $this->isLocal,
            'isHandmade' => $this->isHandmade,
            'pickupLocation' => $this->pickupLocation,
            'pickupIsland' => $this->pickupIsland,
            'pickupCity' => $this->pickupCity,
            'shippingAvailable' => $this->shippingAvailable,
            'shippingCost' => $this->shippingCost,
            'shippingTime' => $this->shippingTime,
            'status' => $this->status,
            'isFeatured' => $this->isFeatured,
            'slug' => $this->slug,
            'tags' => $this->parseCommaSeparated($this->tags),
            'searchKeywords' => $this->searchKeywords,
            'mainImage' => $this->mainImage,
            'galleryImages' => $this->parseJson($this->galleryImages),
            'videoUrl' => $this->videoUrl,
            'viewsCount' => $this->viewsCount,
            'favoritesCount' => $this->favoritesCount,
            'salesCount' => $this->salesCount,
            'ratingAverage' => $this->ratingAverage,
            'ratingCount' => $this->ratingCount,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
            'publishedAt' => $this->publishedAt
        ];
        
        // Incluir datos privados solo para el propietario
        if ($includePrivate) {
            $data['costPrice'] = $this->costPrice;
            $data['stockAlertLevel'] = $this->stockAlertLevel;
        }
        
        // Incluir datos relacionados si están cargados
        if (isset($this->seller)) {
            $data['seller'] = $this->seller;
        }
        if (isset($this->category)) {
            $data['category'] = $this->category;
        }
        if (isset($this->subcategory)) {
            $data['subcategory'] = $this->subcategory;
        }
        if (isset($this->reviews)) {
            $data['reviews'] = $this->reviews;
        }
        if (isset($this->variants)) {
            $data['variants'] = $this->variants;
        }
        if (isset($this->attributes)) {
            $data['attributes'] = $this->attributes;
        }
        
        return $data;
    }
    
    /**
     * Generar slug automáticamente desde el nombre
     */
    public function generateSlug() {
        if (empty($this->name)) return null;
        
        $slug = strtolower($this->name);
        $slug = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'n'], $slug);
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        $slug = trim($slug, '-');
        
        return $slug;
    }
    
    /**
     * Verificar si el producto está disponible para compra
     */
    public function isAvailable() {
        return $this->status === 'active' && 
               ($this->unlimitedStock || $this->stockQuantity > 0) &&
               (empty($this->expirationDate) || strtotime($this->expirationDate) > time());
    }
    
    /**
     * Verificar si el producto tiene stock bajo
     */
    public function hasLowStock() {
        return !$this->unlimitedStock && $this->stockQuantity <= $this->stockAlertLevel;
    }
    
    /**
     * Calcular descuento porcentual
     */
    public function getDiscountPercentage() {
        if (empty($this->originalPrice) || $this->originalPrice <= $this->price) {
            return 0;
        }
        return round((($this->originalPrice - $this->price) / $this->originalPrice) * 100);
    }
    
    // Métodos auxiliares de parsing
    private function parseInt($value) {
        return $value !== null ? (int)$value : null;
    }
    
    private function parseDecimal($value) {
        return $value !== null ? (float)$value : null;
    }
    
    private function parseBool($value) {
        if (is_bool($value)) return $value;
        if (is_string($value)) return in_array(strtolower($value), ['1', 'true', 'yes', 'on']);
        return (bool)$value;
    }
    
    private function parseJson($value) {
        if (empty($value)) return null;
        if (is_array($value)) return $value;
        $decoded = json_decode($value, true);
        return $decoded !== null ? $decoded : $value;
    }
    
    private function parseCommaSeparated($value) {
        if (empty($value)) return [];
        if (is_array($value)) return $value;
        return array_map('trim', explode(',', $value));
    }
}
