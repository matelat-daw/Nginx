# 📚 ARQUITECTURA TÉCNICA - CANARIAS CIRCULAR
## Guía de Referencia Obligatoria - Leer ANTES de Implementar Nuevas Características

> **ÚLTIMA ACTUALIZACIÓN:** 9 de octubre de 2025
> **VERSIÓN:** 2.0.0
> **ESTADO:** Producción en desarrollo local

---

## 🎯 PROPÓSITO DE ESTE DOCUMENTO

Este documento es la **FUENTE ÚNICA DE VERDAD** sobre la arquitectura técnica del proyecto.
**DEBE LEERSE COMPLETAMENTE** antes de realizar cualquier cambio o nueva implementación.

---

## 📋 ÍNDICE RÁPIDO

1. [Stack Tecnológico](#stack-tecnológico)
2. [Configuración de Base de Datos](#configuración-de-base-de-datos)
3. [Sistema de Autenticación](#sistema-de-autenticación)
4. [Sistema de Email](#sistema-de-email)
5. [Estructura de API](#estructura-de-api)
6. [Frontend - Componentes](#frontend---componentes)
7. [Seguridad](#seguridad)
8. [Base de Datos - Tablas](#base-de-datos---tablas)
9. [Variables de Entorno](#variables-de-entorno)
10. [Convenciones y Estándares](#convenciones-y-estándares)

---

## 🔧 STACK TECNOLÓGICO

### Backend
- **PHP 8.x** (ubicado en `C:\Server\PHP`)
- **MySQL/MariaDB** (base de datos: `canarias_ec`)
- **Servidor Web:** Nginx (localhost)
- **Email:** Sendmail + SMTP Gmail configurado

### Frontend
- **JavaScript Vanilla** (ES6+)
- **Arquitectura:** Componentes modulares
- **Sin frameworks** (decisión arquitectónica)
- **CSS Modular** por componente

### Herramientas
- **Git** (repositorio: matelat-daw/Nginx)
- **VS Code** como IDE principal

---

## 💾 CONFIGURACIÓN DE BASE DE DATOS

### ⚠️ CRÍTICO - TIPO DE CONEXIÓN

**USAMOS PDO, NO MySQLi**

```php
// ✅ CORRECTO - PDO
$db = getDBConnection(); // Retorna objeto PDO
$db->beginTransaction();
$db->commit();
$db->rollBack(); // Con B mayúscula

// ❌ INCORRECTO - MySQLi (NO USAR)
$db->begin_transaction(); // NO EXISTE EN PDO
$db->rollback(); // NO ES EL MÉTODO CORRECTO
$stmt->bind_param(); // NO EXISTE EN PDO
```

### Configuración PDO

**Ubicación:** `api/config.php` - función `getDBConnection()`

```php
function getDBConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => false,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
            PDO::ATTR_TIMEOUT => 30
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }
    
    return $pdo;
}
```

### Sintaxis PDO vs MySQLi

| Operación | PDO (✅ USAR) | MySQLi (❌ NO USAR) |
|-----------|---------------|---------------------|
| Preparar query | `$stmt = $db->prepare($sql)` | `$stmt = $db->prepare($sql)` |
| Ejecutar | `$stmt->execute([':param' => $value])` | `$stmt->bind_param('s', $value); $stmt->execute()` |
| Parámetros | Named: `:param` | Tipos: `'s', 'i', 'd'` |
| Last ID | `$db->lastInsertId()` | `$db->insert_id` |
| Transacciones | `beginTransaction()`, `commit()`, `rollBack()` | `begin_transaction()`, `commit()`, `rollback()` |
| Cerrar statement | No necesario (auto) | `$stmt->close()` |
| Errores | Excepciones automáticas | `$db->error`, `$stmt->error` |

### Credenciales de BD

**Archivo:** `.env` (en la raíz del proyecto)

```env
DB_HOST=localhost
DB_NAME=canarias_ec
DB_USER=root
DB_PASS=tu_contraseña_segura
```

**⚠️ IMPORTANTE:** El archivo `.env` NO está en Git (incluido en `.gitignore`)

---

## 🔐 SISTEMA DE AUTENTICACIÓN

### JWT (JSON Web Tokens)

**Ubicación:** `api/config.php` - Clase `JWT`

#### Características
- **Algoritmo:** HS256 (HMAC-SHA256)
- **Secret Key:** Almacenada en `.env` como `JWT_SECRET`
- **Expiración:** 24 horas (configurable vía `JWT_EXPIRATION`)
- **Almacenamiento:** Cookie HTTP

#### Estructura del Token

```php
// Payload del JWT
[
    'userId' => int,      // ID del usuario
    'email' => string,    // Email del usuario
    'iat' => timestamp,   // Issued at
    'exp' => timestamp    // Expiration time
]
```

#### Métodos Disponibles

```php
// Generar token
$jwt = JWT::generateToken($userId, $email);

// Decodificar token (devuelve objeto con payload)
$decoded = JWT::decode($jwt, JWT_SECRET);
// Acceso: $decoded->userId, $decoded->email

// Validar token (devuelve true/false)
$isValid = JWT::validate($jwt, JWT_SECRET);
```

### Cookies de Autenticación

**Nombre de la cookie:** `ecc_auth_token` (constante `COOKIE_NAME`)

```php
// Configuración en config.php
define('COOKIE_NAME', 'ecc_auth_token');
define('COOKIE_EXPIRATION', 24 * 60 * 60); // 24 horas
define('COOKIE_SECURE', false);           // true en producción
define('COOKIE_HTTP_ONLY', false);        // Permite acceso desde JS
define('COOKIE_SAME_SITE', 'Lax');
```

**⚠️ NOTA:** `COOKIE_HTTP_ONLY` está en `false` porque el frontend necesita leer el token para verificar sesión.

### Validación de Usuario en Endpoints

**Patrón estándar para endpoints API:**

```php
// 1. Verificar sesión PHP primero
$userId = $_SESSION['user_id'] ?? null;

// 2. Si no hay sesión, intentar con JWT desde cookie
if (!$userId && isset($_COOKIE[COOKIE_NAME])) {
    try {
        $jwt = $_COOKIE[COOKIE_NAME];
        $decoded = JWT::decode($jwt, JWT_SECRET);
        
        if ($decoded && isset($decoded->userId)) {
            $userId = $decoded->userId;
            $_SESSION['user_id'] = $userId;
        }
    } catch (Exception $e) {
        logMessage('WARNING', "Error decodificando JWT: " . $e->getMessage());
    }
}

// 3. Validar que tenemos usuario
if (!$userId) {
    throw new Exception('Usuario no autenticado');
}
```

### Encriptación de Contraseñas

**Algoritmo:** Bcrypt (vía `password_hash()` de PHP)

```php
// ✅ Al registrar usuario
$hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

// ✅ Al validar login
$isValid = password_verify($plainPassword, $hashedPassword);
```

**⚠️ NUNCA:**
- Almacenar contraseñas en texto plano
- Usar MD5 o SHA1 para passwords
- Comparar contraseñas con `===`

---

## 📧 SISTEMA DE EMAIL

### Configuración

**Motor:** Sendmail configurado con SMTP de Gmail
**Función PHP:** `mail()` nativa

**⚠️ IMPORTANTE:** NO usamos librerías externas (PHPMailer, etc.). Usamos la función `mail()` nativa de PHP porque Sendmail ya está configurado.

### Función de Envío

**Ubicación:** `api/config.php`

```php
// Configuración de remitente
define('EMAIL_FROM', 'tu_email@gmail.com');
define('EMAIL_FROM_NAME', 'Canarias Circular');
```

### Emails Implementados

#### 1. Email de Bienvenida
**Función:** `sendWelcomeEmail($userEmail, $userName, $userId, $confirmationToken)`
**Trigger:** Al registrar nuevo usuario
**Contenido:** Enlace de confirmación de email

#### 2. Email de Confirmación de Pedido
**Función:** `sendOrderConfirmationEmail($orderData)`
**Trigger:** Al completar un pedido
**Contenido:** 
- Detalles del pedido
- Lista de productos con precios
- Total del pedido
- Método de pago
- Enlace para ver el pedido

### Estructura de Email HTML

Todos los emails usan:
- HTML con estilos inline (para compatibilidad)
- Gradientes en headers
- Responsive design
- Botones con call-to-action
- Información de contacto en footer

### Manejo de Errores

```php
$emailSent = @mail($to, $subject, $htmlContent, implode("\r\n", $headers));

if ($emailSent) {
    logMessage('INFO', "Email enviado a {$to}");
} else {
    logMessage('WARNING', "Email no pudo enviarse a {$to}");
}

// En desarrollo local, guarda enlaces en archivo temporal
if (!$emailSent && DESARROLLO_LOCAL) {
    file_put_contents('temp_emails.txt', $info, FILE_APPEND);
}
```

---

## 🌐 ESTRUCTURA DE API

### Arquitectura

**Patrón:** RESTful API
**Base URL:** `http://localhost/api/`
**Headers requeridos:**
- `Content-Type: application/json`
- `Access-Control-Allow-Credentials: true`

### CORS (Cross-Origin Resource Sharing)

**Función helper:** `setCorsHeaders()` en `config.php`

```php
header('Access-Control-Allow-Origin: ' . SITE_URL);
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');
```

**⚠️ Preflight Requests:** Siempre manejar `OPTIONS` method:

```php
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
```

### Output Buffering - CRÍTICO

**⚠️ PROBLEMA CONOCIDO:** `config.php` inicia `ob_start()` que interfiere con JSON responses.

**Solución implementada:**

```php
// En config.php - línea ~37
$isJsonEndpoint = (
    strpos($_SERVER['REQUEST_URI'] ?? '', '/api/orders/') !== false ||
    strpos($_SERVER['REQUEST_URI'] ?? '', '/api/products/') !== false
);

if (!$isJsonEndpoint) {
    ob_start('ob_gzhandler');
}
```

**En endpoints críticos (create-order.php, etc.):**

```php
// Al inicio del archivo, ANTES de require config.php
while (ob_get_level()) {
    ob_end_clean();
}

ini_set('output_buffering', 'off');
ini_set('zlib.output_compression', 'off');

// Headers PRIMERO
header('Content-Type: application/json; charset=utf-8');

// DESPUÉS cargar config.php
require_once '../config.php';

// Al final, exit limpiamente
exit();
```

### Formato de Respuesta JSON

**Estructura estándar:**

```json
{
    "success": true|false,
    "message": "Mensaje descriptivo",
    "data": { },  // Opcional
    "error": ""   // Solo si success=false
}
```

### Funciones Helper

**Ubicación:** `api/config.php`

```php
// Respuesta JSON estandarizada
jsonResponse($data, $statusCode = 200, $message = '');

// Logging
logMessage($level, $message);  // Niveles: INFO, WARNING, ERROR

// Seguridad
applySecurityMiddleware($useRateLimiting = false);

// CORS
setCorsHeaders();
handlePreflight();
```

### Endpoints Principales

#### Autenticación (`/api/auth/`)
- `POST /login.php` - Iniciar sesión
- `POST /register.php` - Registrar usuario
- `POST /logout.php` - Cerrar sesión
- `GET /validate.php` - Validar sesión activa
- `GET /confirm-email.php` - Confirmar email
- `POST /change-password.php` - Cambiar contraseña
- `GET /get-profile.php` - Obtener perfil
- `POST /update-profile.php` - Actualizar perfil

#### Productos (`/api/products/`)
- `GET /list.php` - Listar productos
- `GET /get.php?id=X` - Obtener producto
- `POST /create.php` - Crear producto (requiere auth)
- `PUT /update.php` - Actualizar producto (requiere auth)
- `DELETE /delete.php` - Eliminar producto (requiere auth)

#### Pedidos (`/api/orders/`)
- `POST /create-order.php` - Crear pedido y enviar email
- `GET /my-orders.php` - Listar pedidos del usuario
- `GET /get.php?id=X` - Obtener detalles de pedido
- `PUT /update-status.php` - Actualizar estado (admin)

---

## 🎨 FRONTEND - COMPONENTES

### Arquitectura

**Patrón:** Web Components (custom elements)
**No usamos:** React, Vue, Angular
**Routing:** Sistema custom en `app/router/app-router.js`

### Estructura de Componentes

```
app/
  components/
    cart/
      cart-modal.component.js
      cart-modal.component.css
      cart-modal.component.html
    header/
      header.component.js
      header.component.css
      header.component.html
    modals/
      payment-modal.component.js
      notification-modal.component.js
      ...
```

### Patrón de Componente

```javascript
class MiComponente {
    constructor() {
        this.element = null;
    }
    
    async render() {
        // Cargar HTML
        const html = await fetch('/ruta/componente.html').then(r => r.text());
        
        // Insertar en DOM
        const container = document.getElementById('container');
        container.innerHTML = html;
        
        // Cargar CSS
        this.loadStyles();
        
        // Setup listeners
        this.setupEventListeners();
    }
    
    loadStyles() {
        if (!document.getElementById('mi-componente-styles')) {
            const link = document.createElement('link');
            link.id = 'mi-componente-styles';
            link.rel = 'stylesheet';
            link.href = '/ruta/componente.css';
            document.head.appendChild(link);
        }
    }
    
    setupEventListeners() {
        // Event listeners aquí
    }
}
```

### Servicios Frontend

**Ubicación:** `app/services/`

#### AuthService (`auth.service.js`)
```javascript
window.authService = {
    login(email, password),
    register(userData),
    logout(),
    isAuthenticated(),
    getCurrentUser(),
    // ...
}
```

#### CartService (`cart.service.js`)
```javascript
window.cartService = {
    addItem(product),
    removeItem(productId),
    updateQuantity(productId, quantity),
    getItems(),
    getTotal(),
    clear(),
    // Persistencia en localStorage
}
```

#### PaymentService
```javascript
window.paymentService = {
    initiatePayment(data),
    // MODO DEMO implementado
}
```

### Estado Global

**Almacenamiento:** `localStorage`

```javascript
// Usuario actual
localStorage.getItem('currentUser')

// Token JWT
// NO almacenado en localStorage - está en cookie

// Carrito
localStorage.getItem('cart')
```

### Eventos Personalizados

```javascript
// Login completado
window.dispatchEvent(new CustomEvent('loginSuccess', { 
    detail: { user } 
}));

// Carrito actualizado
window.dispatchEvent(new CustomEvent('cartUpdated', {
    detail: { items, total }
}));

// Pago completado
window.dispatchEvent(new CustomEvent('paymentCompleted', {
    detail: { orderId, method }
}));
```

---

## 🔒 SEGURIDAD

### Middleware de Seguridad

**Ubicación:** `api/middleware/SecurityMiddleware.php`

#### Rate Limiting

```php
RateLimiter::check($identifier, $maxRequests = 10, $timeWindow = 60);
// Bloquea IPs que excedan límite
```

#### Sanitización de Inputs

```php
// ✅ Siempre sanitizar
$email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
$email = filter_var($email, FILTER_VALIDATE_EMAIL);

// Para SQL - usar prepared statements (automático con PDO)
$stmt = $db->prepare("SELECT * FROM users WHERE email = :email");
$stmt->execute([':email' => $email]);
```

#### Headers de Seguridad

```php
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
```

### Protección CSRF

**Estado:** No implementado aún
**TODO:** Implementar tokens CSRF para formularios

### SQL Injection

**Protección:** Prepared Statements con PDO (siempre)

```php
// ✅ CORRECTO
$stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute([':id' => $userId]);

// ❌ NUNCA HACER
$query = "SELECT * FROM users WHERE id = " . $userId; // VULNERABLE
```

### XSS (Cross-Site Scripting)

**Protección:** Escapar output HTML

```php
// En PHP
echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');

// En JavaScript
element.textContent = userInput; // No usar innerHTML
```

---

## 🗄️ BASE DE DATOS - TABLAS

### Tabla: `users`

```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    island VARCHAR(50),
    city VARCHAR(100),
    user_type ENUM('user', 'seller', 'admin') DEFAULT 'user',
    email_verified BOOLEAN DEFAULT FALSE,
    email_confirmation_token VARCHAR(255),
    profile_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Tabla: `products`

```sql
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    category VARCHAR(100),
    island VARCHAR(50),
    city VARCHAR(100),
    stock INT DEFAULT 0,
    images JSON,
    status ENUM('active', 'inactive', 'sold') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

**⚠️ ESTADO ACTUAL:** Tabla vacía. Productos mostrados son hardcoded en el componente.

### Tabla: `orders`

```sql
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(100) UNIQUE NOT NULL,
    buyer_id INT NOT NULL,
    status ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    subtotal DECIMAL(10,2) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    delivery_method VARCHAR(50),
    payment_method VARCHAR(50),
    payment_status VARCHAR(50),
    payment_reference VARCHAR(255),
    billing_name VARCHAR(255),
    shipping_phone VARCHAR(20),
    buyer_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE RESTRICT
);
```

### Tabla: `order_items`

```sql
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NULL,  -- ⚠️ PUEDE SER NULL
    seller_id INT NULL,   -- ⚠️ PUEDE SER NULL
    product_name VARCHAR(255) NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    line_total DECIMAL(10,2) NOT NULL,
    item_status ENUM('pending', 'confirmed', 'preparing', 'ready', 'delivered', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE SET NULL
);
```

**⚠️ CLAVES FORÁNEAS:**
- `product_id` y `seller_id` **pueden ser NULL**
- Esto permite crear pedidos aunque los productos no existan en la tabla `products`
- El nombre y precio del producto siempre se guardan en `order_items` (histórico)
- Si se elimina el producto o usuario, el FK se establece en NULL automáticamente

**⚠️ ESTADO ACTUAL:**
- Permite crear pedidos con productos hardcoded
- Sistema implementa validación flexible con fallback a NULL si no existe el producto
- Búsqueda inteligente: por ID → por nombre → NULL

---

## ⚙️ VARIABLES DE ENTORNO

**Archivo:** `.env` (raíz del proyecto)

```env
# Base de datos
DB_HOST=localhost
DB_NAME=canarias_ec
DB_USER=root
DB_PASS=tu_contraseña

# JWT
JWT_SECRET=tu_clave_secreta_muy_larga_y_compleja_aqui
JWT_EXPIRATION=86400

# Email
EMAIL_FROM=tu_email@gmail.com
EMAIL_FROM_NAME=Canarias Circular

# Site
SITE_URL=http://localhost
DEBUG_MODE=true
```

**Carga de variables:**

```php
// En config.php
function loadEnvironmentVariables($filePath = __DIR__ . '/../.env') {
    // Lee archivo .env y establece constantes
}
```

**Acceso:**

```php
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('JWT_SECRET', getenv('JWT_SECRET'));
```

---

## 📐 CONVENCIONES Y ESTÁNDARES

### Nomenclatura

#### PHP
- **Archivos:** `kebab-case.php` (ej: `create-order.php`)
- **Funciones:** `camelCase()` (ej: `sendOrderEmail()`)
- **Clases:** `PascalCase` (ej: `JWT`, `RateLimiter`)
- **Constantes:** `UPPER_SNAKE_CASE` (ej: `DB_HOST`, `JWT_SECRET`)

#### JavaScript
- **Archivos:** `kebab-case.component.js`
- **Clases:** `PascalCase` (ej: `CartModal`)
- **Funciones:** `camelCase()` (ej: `handlePayment()`)
- **Constantes:** `UPPER_SNAKE_CASE`

#### SQL
- **Tablas:** `snake_case` plural (ej: `order_items`)
- **Columnas:** `snake_case` (ej: `user_id`, `created_at`)

### Logging

**Función:** `logMessage($level, $message)`

```php
// Niveles
logMessage('INFO', 'Usuario logueado correctamente');
logMessage('WARNING', 'Producto no encontrado, usando fallback');
logMessage('ERROR', 'Error de conexión a BD: ' . $e->getMessage());
```

**Archivo de logs:** Configurado en `php.ini` - ubicación en `C:\Server\logs\`

### Comentarios de Código

```php
/**
 * Crear pedido y enviar email de confirmación
 * 
 * @param array $orderData Datos del pedido
 * @return bool True si se envió el email
 */
function sendOrderConfirmationEmail($orderData) {
    // ...
}
```

### Control de Versiones

```bash
# Commits descriptivos
git commit -m "feat: Implementar sistema de confirmación de pedidos por email"
git commit -m "fix: Corregir validación de claves foráneas en order_items"
git commit -m "docs: Actualizar documentación técnica"
```

**Prefijos:**
- `feat:` - Nueva característica
- `fix:` - Corrección de bug
- `docs:` - Documentación
- `refactor:` - Refactorización
- `style:` - Formato de código
- `test:` - Tests

---

## 🚨 ERRORES COMUNES Y SOLUCIONES

### 1. "Call to undefined method mysqli::beginTransaction"

**Causa:** Usar sintaxis de MySQLi en lugar de PDO
**Solución:** Usar `beginTransaction()`, `commit()`, `rollBack()` de PDO

### 2. "JSON.parse: unexpected character at line 1"

**Causa:** Output buffering interfiriendo con JSON response
**Solución:** 
- Limpiar buffers al inicio del endpoint
- Deshabilitar ob_start para endpoints JSON
- Usar `exit()` después de respuesta JSON

### 3. "Integrity constraint violation: 1452"

**Causa:** Intentar insertar FK con ID que no existe
**Solución:**
- Validar que el ID existe antes de insertar
- Usar fallback seguro (userId) si no existe
- Implementar búsqueda por nombre como alternativa

### 4. "Usuario no autenticado"

**Causa:** JWT no se está validando correctamente
**Solución:**
- Verificar que la cookie existe: `$_COOKIE[COOKIE_NAME]`
- Usar `JWT::decode()` para obtener payload (no `validate()`)
- Acceder a propiedades como objeto: `$decoded->userId`

### 5. Email no se envía

**Causa:** Sendmail no configurado o función bloqueada
**Solución:**
- Verificar que `mail()` retorna true
- Usar `@mail()` para suprimir warnings
- Revisar logs en desarrollo local
- Verificar archivo `temp_order_emails.txt` en desarrollo

---

## 🔄 ESTADO DEL PROYECTO

### ✅ Completado

- [x] Sistema de autenticación con JWT
- [x] Registro y login de usuarios
- [x] Confirmación de email
- [x] Gestión de perfil de usuario
- [x] Sistema de carrito (localStorage)
- [x] Modal de pago (MODO DEMO)
- [x] Creación de pedidos en BD
- [x] Envío de emails de confirmación
- [x] Limpieza de carrito post-compra
- [x] Redirección automática tras compra exitosa
- [x] Sistema de logging
- [x] Middleware de seguridad
- [x] CORS configurado
- [x] Routing frontend
- [x] Componentes modulares
- [x] Validación flexible de claves foráneas (NULL permitido)

### 🚧 En Desarrollo

- [ ] Productos reales en BD (actualmente hardcoded)
- [ ] Gestión de productos (CRUD completo)
- [ ] Integración de pagos real (PayPal requiere configuración)
- [ ] Dashboard de pedidos
- [ ] Sistema de búsqueda de productos
- [ ] Filtros por categoría/isla

### 📋 Pendiente

- [ ] Panel de administración
- [ ] Sistema de mensajería entre usuarios
- [ ] Valoraciones y reseñas
- [ ] Notificaciones en tiempo real
- [ ] Optimización de imágenes
- [ ] Tests automatizados
- [ ] Tokens CSRF
- [ ] Implementación de HTTPS

---

## 📞 CONTACTO Y SOPORTE

**Desarrollador:** [Tu nombre]
**Repositorio:** https://github.com/matelat-daw/Nginx
**Última revisión:** 9 de octubre de 2025

---

## 🎓 LECCIONES APRENDIDAS

### 1. PDO vs MySQLi
**SIEMPRE** verificar qué tipo de conexión estamos usando antes de escribir código de BD.

### 2. Output Buffering
Los endpoints JSON deben deshabilitar output buffering explícitamente.

### 3. Claves Foráneas
Implementar validación flexible con fallbacks cuando las FK pueden no existir.

### 4. JWT en Cookies
Usar cookies para JWT (no localStorage) para mejor seguridad contra XSS.

### 5. Logging Detallado
Los logs son cruciales para debugging. Loguear en WARNING/INFO, no solo ERROR.

---

## 🔖 CHECKLIST PRE-IMPLEMENTACIÓN

Antes de implementar cualquier nueva característica:

- [ ] ✅ He leído COMPLETAMENTE este documento
- [ ] ✅ Sé que usamos PDO, no MySQLi
- [ ] ✅ Sé cómo validar usuarios (sesión + JWT)
- [ ] ✅ Sé que usamos `mail()` para emails
- [ ] ✅ Entiendo el manejo de output buffering
- [ ] ✅ Conozco la estructura de componentes
- [ ] ✅ Sé cómo manejar claves foráneas
- [ ] ✅ Revisé los errores comunes
- [ ] ✅ Entiendo el flujo de autenticación
- [ ] ✅ Sé dónde están las configuraciones (`.env`)

---

**🎯 REGLA DE ORO:**

> "Cuando tengas dudas sobre cómo está implementado algo,
> CONSULTA ESTE DOCUMENTO primero, el código fuente después."

---

*Documento vivo - Actualizar con cada cambio arquitectónico importante*
