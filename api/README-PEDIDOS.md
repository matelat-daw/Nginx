# 💰 Sistema de Pedidos y Facturación - Economía Circular Canarias

## 🔄 **Comparación: Tu Sistema vs. Nueva Estructura**

### ❌ **Tu Sistema Anterior (invoice + sold)**
```sql
-- Limitado y simple
CREATE TABLE invoice (
    id, total, date, user_id
);

CREATE TABLE sold (
    id, invoice_id, product_id, quantity, price
);
```

**Problemas:**
- ❌ No maneja estados de pedido
- ❌ Sin trazabilidad de cambios  
- ❌ No soporta múltiples vendedores
- ❌ Sin gestión de pagos
- ❌ Sin información de entrega
- ❌ Limitado para e-commerce

### ✅ **Nueva Estructura Profesional**
```sql
-- Sistema completo de e-commerce
orders (pedidos principales)
order_items (artículos del pedido)  
payments (trazabilidad de pagos)
order_status_history (auditoría completa)
invoices (facturación fiscal opcional)
coupons (descuentos y promociones)
```

**Ventajas:**
- ✅ **Escalable** para crecimiento
- ✅ **Multi-vendedor** nativo
- ✅ **Estados de pedido** completos
- ✅ **Trazabilidad** total
- ✅ **Múltiples métodos de pago**
- ✅ **Facturación fiscal** preparada
- ✅ **Sistema de cupones**
- ✅ **Auditoría completa**

---

## 🗄️ **Nueva Estructura Detallada**

### 📋 **Tabla `orders` (Pedidos)**
**Reemplaza tu tabla `invoice` con funcionalidad avanzada**

```sql
-- Información básica
order_number VARCHAR(20) UNIQUE     -- ECC-2025-000001
buyer_id, seller_id                 -- Multi-vendedor
status ENUM(pending, paid, delivered...)
payment_status ENUM(pending, paid, failed...)

-- Importes detallados  
subtotal, shipping_cost, tax_amount
discount_amount, coupon_discount, total_amount

-- Entrega y ubicación (Canarias)
delivery_method ENUM(pickup, shipping, digital)
shipping_island ENUM(Gran Canaria, Tenerife...)
pickup_location, billing_address

-- Métodos de pago
payment_method ENUM(bizum, card, transfer, stripe)
payment_reference

-- Fechas y trazabilidad
estimated_delivery_date, actual_delivery_date
buyer_notes, seller_notes, admin_notes
```

### 📦 **Tabla `order_items` (Artículos)**
**Reemplaza tu tabla `sold` con información completa**

```sql
-- Relaciones
order_id, product_id, seller_id

-- Snapshot del producto (importante para histórico)
product_name, product_description, product_sku
unit_price, original_price, quantity, line_total

-- Variantes y personalización
variant_info JSON                   -- {"talla": "M", "color": "azul"}

-- Estados individuales
item_status ENUM(pending, delivered...)
tracking_number, delivered_at

-- Comisiones de la plataforma
platform_commission_rate DECIMAL(5,2)
platform_commission_amount, seller_payout
```

### 💳 **Tabla `payments` (Pagos)**
**Nueva: Trazabilidad completa de transacciones**

```sql
order_id, payment_method, amount
status ENUM(pending, completed, failed...)
external_payment_id                 -- ID de Stripe, Bizum, etc.
card_last_four, bizum_phone
processor_response JSON
```

### 📊 **Tabla `order_status_history` (Auditoría)**
**Nueva: Historial completo de cambios**

```sql
order_id, from_status, to_status
changed_by_user_id, changed_by_role
reason, notes, created_at
```

### 🎫 **Tabla `coupons` (Descuentos)**
**Nueva: Sistema de promociones**

```sql
code VARCHAR(50)                    -- BIENVENIDO10
discount_type ENUM(percentage, fixed_amount, free_shipping)
discount_value, max_uses, valid_from, valid_until
```

---

## 🚀 **Flujo de Pedido Completo**

### 1. 🛒 **Cliente crea pedido**
```javascript
// POST /api/orders/create.php
{
  "items": [
    {
      "product_id": 123,
      "quantity": 2,
      "variant_info": {"talla": "M"}
    }
  ],
  "delivery_method": "pickup",
  "pickup_island": "Gran Canaria",
  "payment_method": "bizum",
  "coupon_code": "BIENVENIDO10"
}
```

### 2. 📝 **Sistema procesa**
- ✅ Valida productos y stock
- ✅ Aplica descuentos y cupones
- ✅ Calcula comisiones
- ✅ Genera número de pedido único
- ✅ Estado inicial: `pending`

### 3. 💳 **Pago procesado**
- ✅ Integración con Stripe/Bizum
- ✅ Estado cambia a `paid`
- ✅ **Stock se reduce automáticamente** (trigger)
- ✅ Registro en tabla `payments`

### 4. 📦 **Vendedor gestiona**
```javascript
// PUT /api/orders/update-status.php
{
  "order_id": 123,
  "new_status": "processing",
  "reason": "Preparando pedido"
}
```

### 5. 🚚 **Entrega**
- `ready_pickup` → `delivered`
- ✅ **Historial completo** registrado
- ✅ **Comisiones** calculadas para payout

---

## 📡 **API Endpoints Disponibles**

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `POST` | `/api/orders/create.php` | Crear pedido |
| `GET` | `/api/orders/get.php` | Obtener pedido |
| `GET` | `/api/orders/my-orders.php` | Mis pedidos |
| `PUT` | `/api/orders/update-status.php` | Cambiar estado |

### 🛒 **Ejemplo: Crear Pedido**
```javascript
const response = await fetch('/api/orders/create.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + token
    },
    body: JSON.stringify({
        items: [
            {
                product_id: 123,
                quantity: 2
            }
        ],
        delivery_method: 'pickup',
        pickup_island: 'Gran Canaria',
        payment_method: 'bizum',
        buyer_notes: 'Recoger por la tarde'
    })
});
```

### 📋 **Ejemplo: Mis Pedidos**
```javascript
// Como comprador
const orders = await fetch('/api/orders/my-orders.php?type=buyer&include_items=true');

// Como vendedor
const sales = await fetch('/api/orders/my-orders.php?type=seller&status=paid');
```

---

## 🏝️ **Características Específicas Canarias**

### 🌊 **Soporte Multi-Isla**
```sql
shipping_island ENUM(
    'Gran Canaria', 'Tenerife', 'Lanzarote', 
    'Fuerteventura', 'La Palma', 'La Gomera', 'El Hierro'
)
```

### 💰 **Fiscalidad Canaria**
```sql
tax_amount DECIMAL(8,2)             -- IGIC aplicable
billing_tax_id VARCHAR(20)          -- NIF/CIF para facturas
```

### 🚚 **Métodos de Entrega**
- **`pickup`** - Recogida local (principal)
- **`shipping`** - Envío inter-islas
- **`digital`** - Productos digitales

---

## 🔧 **Instalación**

### 1. **Ejecutar SQL**
```bash
mysql -u root -p tu_base_datos < api/create-orders-tables.sql
```

### 2. **Verificar Instalación**
```
http://localhost:8080/api/test-orders.php
```

Deberías ver:
```json
{
    "success": true,
    "tables_check": {
        "orders": "✅ Existe",
        "order_items": "✅ Existe",
        "payments": "✅ Existe"
    },
    "triggers": "✅ 6 triggers encontrados",
    "coupons": "✅ 3 cupones encontrados"
}
```

---

## 🎯 **Ventajas de la Nueva Estructura**

### 📈 **Escalabilidad**
- ✅ Soporta millones de pedidos
- ✅ Multi-vendedor desde día 1
- ✅ Preparado para marketplace

### 🔍 **Trazabilidad**
- ✅ Historial completo de cambios
- ✅ Auditoría de cada transacción
- ✅ Debugging facilitado

### 💼 **Comercial**
- ✅ Sistema de comisiones
- ✅ Facturación fiscal
- ✅ Reportes de ventas
- ✅ Cupones y promociones

### 🚀 **Técnica**
- ✅ APIs RESTful modernas
- ✅ Validación automática
- ✅ Triggers de base de datos
- ✅ Índices optimizados

---

## 🔮 **Próximos Pasos**

1. **✅ Estructura creada**
2. **✅ Modelos PHP implementados**
3. **✅ API endpoints básicos**
4. **🔄 Siguiente: Carrito JavaScript**
5. **🔄 Después: Integración Stripe + Bizum**
6. **🔄 Futuro: Dashboard vendedores**

---

## 💡 **Recomendación Final**

**Tu sistema anterior `invoice + sold` era correcto para empezar**, pero esta nueva estructura te da:

- 🚀 **Capacidad de crecimiento**
- 💰 **Monetización avanzada**  
- 🔧 **Mantenimiento simplificado**
- 📊 **Analytics detallados**
- 🛡️ **Seguridad mejorada**

**¡Es una inversión que vale la pena para el futuro de tu plataforma! 🏝️💼**
