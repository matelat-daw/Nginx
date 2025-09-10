# 🛍️ Sistema de Productos - Economía Circular Canarias

## 📋 Descripción

Sistema completo de gestión de productos para la plataforma de economía circular de Canarias. Incluye una base de datos genérica que soporta tanto alimentos como artículos de todo tipo, con funcionalidades de carrito de la compra y preparación para integración de pagos.

## 🗄️ Estructura de Base de Datos

### Tablas Principales

1. **`products`** - Tabla principal de productos
2. **`product_categories`** - Categorías y subcategorías
3. **`product_attributes`** - Atributos personalizados
4. **`product_variants`** - Variantes (tallas, colores, etc.)
5. **`product_reviews`** - Reseñas y valoraciones
6. **`product_favorites`** - Lista de favoritos

### Características Destacadas

✅ **Genérica**: Funciona para alimentos y artículos  
✅ **Flexible**: Atributos personalizados para casos especiales  
✅ **Completa**: Inventario, ubicación, multimedia, reseñas  
✅ **Escalable**: Preparada para crecimiento futuro  
✅ **Canaria**: Campos específicos para las islas  
✅ **E-commerce**: Lista para carrito y pagos  

## 🚀 Instalación

### 1. Ejecutar Script SQL

```bash
# Desde phpMyAdmin o línea de comandos MySQL
mysql -u tu_usuario -p tu_base_de_datos < api/create-products-tables.sql
```

### 2. Verificar Instalación

Visita: `http://localhost:8080/api/test-products.php`

Deberías ver algo como:
```json
{
    "success": true,
    "message": "Pruebas de productos completadas",
    "results": {
        "tables_check": {
            "products": "✅ Existe",
            "product_categories": "✅ Existe"
        },
        "categories": "✅ 17 categorías encontradas"
    }
}
```

## 📡 API Endpoints

### Productos

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `POST` | `/api/products/create.php` | Crear producto |
| `GET` | `/api/products/list.php` | Listar productos |
| `GET` | `/api/products/get.php` | Obtener producto por ID/slug |
| `PUT` | `/api/products/update.php` | Actualizar producto |
| `DELETE` | `/api/products/delete.php` | Eliminar producto |

### Categorías

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `GET` | `/api/products/categories.php` | Obtener categorías |

## 🛒 Ejemplos de Uso

### Crear Producto

```javascript
const response = await fetch('/api/products/create.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + token
    },
    body: JSON.stringify({
        name: 'Miel de La Palma',
        description: 'Miel artesanal de laurisilva',
        price: 15.50,
        stock_quantity: 10,
        category_id: 1,
        pickup_island: 'La Palma',
        is_organic: true,
        is_local: true
    })
});
```

### Buscar Productos

```javascript
// Buscar productos orgánicos en Gran Canaria
const response = await fetch('/api/products/list.php?island=Gran Canaria&is_organic=1&limit=20');

// Buscar por texto
const response = await fetch('/api/products/list.php?search=miel artesanal');

// Filtrar por categoría y precio
const response = await fetch('/api/products/list.php?category_id=1&min_price=10&max_price=50');
```

### Obtener Producto

```javascript
// Por ID
const response = await fetch('/api/products/get.php?id=123&include_seller=true');

// Por slug
const response = await fetch('/api/products/get.php?slug=miel-de-la-palma&include_seller=true');
```

## 🏝️ Categorías Preinstaladas

### Principales
- 🍎 Alimentos y Bebidas
- 🎨 Artesanía  
- 👕 Moda y Complementos
- 🏠 Hogar y Jardín
- 📱 Tecnología
- ⚽ Deportes y Ocio
- 📚 Libros y Media
- 🔧 Servicios
- 🚗 Vehículos
- 📦 Otros

### Subcategorías de Alimentos
- Frutas y Verduras
- Productos Lácteos
- Carnes y Pescados
- Panadería y Repostería
- Conservas y Mermeladas
- Vinos y Licores
- Condimentos y Especias

### Subcategorías de Artesanía
- Cerámica
- Textil
- Madera
- Joyería
- Decoración

## 🔧 Modelo de Datos

### Campos Principales

```php
class Product {
    // Básicos
    public $name;           // Nombre del producto
    public $description;    // Descripción completa
    public $price;          // Precio actual
    public $stockQuantity;  // Cantidad en stock
    
    // Ubicación (específico Canarias)
    public $pickupIsland;   // Isla de recogida
    public $pickupCity;     // Ciudad de recogida
    
    // Características
    public $isOrganic;      // ¿Es orgánico?
    public $isLocal;        // ¿Es producto canario?
    public $isHandmade;     // ¿Es artesanal?
    
    // Para alimentos
    public $expirationDate; // Fecha de caducidad
    public $ingredients;    // Lista de ingredientes
    public $allergens;      // Alérgenos
    
    // Estado
    public $status;         // draft, active, inactive, sold
    public $sellerId;       // ID del vendedor (FK)
}
```

## 🎯 Próximos Pasos

1. **✅ Base de datos creada**
2. **✅ Modelos PHP implementados**
3. **✅ API endpoints básicos**
4. **🔄 Siguiente: Carrito de la compra**
5. **🔄 Después: Sistema de pagos (Stripe + Bizum)**

## 🧪 Testing

Para probar el sistema:

1. Visita: `http://localhost:8080/api/test-products.php`
2. Verifica que todas las tablas existan
3. Comprueba las categorías preinstaladas
4. Prueba la validación de productos

## 🐛 Troubleshooting

### Error: "Table doesn't exist"
```bash
# Ejecutar script SQL manualmente
mysql -u root -p
use tu_base_de_datos;
source /ruta/al/archivo/create-products-tables.sql;
```

### Error: "Foreign key constraint fails"
Asegúrate de que existe al menos un usuario en la tabla `users` con ID = 1.

### Error: "JWT Secret not defined"
Verifica que el archivo `.env` tenga la variable `JWT_SECRET`.

## 📝 Notas Adicionales

- El sistema está preparado para manejar **múltiples islas canarias**
- Soporte nativo para **productos orgánicos y artesanales**
- **SEO-friendly** con slugs automáticos
- **Búsqueda full-text** en nombre, descripción y tags
- **Sistema de favoritos** y reseñas incluido
- **Gestión de stock** con alertas automáticas

---

**¡Tu base de datos de productos está lista para la economía circular de Canarias! 🏝️🛍️**
