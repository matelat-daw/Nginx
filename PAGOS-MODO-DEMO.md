# 🔧 Sistema de Pagos - Modo DEMO

## ⚠️ Estado Actual

El sistema de pagos está actualmente funcionando en **MODO SIMULACIÓN** (DEMO). Los pagos NO son reales y NO se procesan transacciones reales.

## 🎯 ¿Por qué modo simulación?

Los endpoints del backend PHP para procesar pagos reales aún no están implementados. Para permitir el desarrollo y pruebas del frontend, todos los métodos de pago devuelven respuestas simuladas exitosas.

## 📋 Métodos de Pago Disponibles (Simulados)

### 1. 💳 Tarjeta de Crédito/Débito
- **Estado**: Simulado
- **Comisión**: +2% del total
- **Respuesta**: Éxito inmediato después de 1.5 segundos
- **Transaction ID**: `CARD-{timestamp}`

### 2. 📱 Bizum
- **Estado**: Simulado
- **Comisión**: Sin gastos adicionales
- **Respuesta**: Genera código y teléfono falsos
- **Transaction ID**: `BIZUM-{timestamp}`
- **Datos simulados**:
  - Teléfono: `600123456`
  - Código: `EC` + 6 caracteres aleatorios

### 3. 🏦 Transferencia Bancaria
- **Estado**: Simulado
- **Comisión**: Sin gastos adicionales
- **Respuesta**: Proporciona datos bancarios ficticios
- **Transaction ID**: `TRANSFER-{timestamp}`
- **Datos simulados**:
  - IBAN: `ES00 0000 0000 0000 0000 0000`
  - Banco: `Banco de Canarias`
  - Titular: `Economía Circular Canarias`

### 4. 🅿️ PayPal
- **Estado**: Simulado
- **Comisión**: +3.4% + 0.35€
- **Respuesta**: Éxito sin redirección real a PayPal
- **Transaction ID**: `PAYPAL-{timestamp}`

### 5. 💵 Contrarreembolso
- **Estado**: Simulado
- **Comisión**: +2.50€ fijos
- **Respuesta**: Confirmación inmediata
- **Transaction ID**: `COD-{timestamp}`

## 🔄 Flujo Actual (Simulado)

```
Usuario selecciona método de pago
         ↓
Click en "Pagar"
         ↓
Delay simulado (0.8-1.5s)
         ↓
✅ Pago "exitoso" (simulado)
         ↓
Carrito se vacía
         ↓
Notificación de éxito
         ↓
Redirección a /orders
```

## 🚀 Migración a Producción

Para activar los pagos reales, necesitas:

### 1. Crear Endpoints Backend

Crear los siguientes archivos PHP en `/api/payments/`:

#### `/api/payments/card/create-intent.php`
```php
<?php
require_once '../../config.php';
// Integración con Stripe
// Crear Payment Intent
// Devolver clientSecret
?>
```

#### `/api/payments/bizum/initiate.php`
```php
<?php
require_once '../../config.php';
// Integración con Redsys/Bizum
// Iniciar pago Bizum
// Devolver código y teléfono
?>
```

#### `/api/payments/transfer/initiate.php`
```php
<?php
require_once '../../config.php';
// Generar referencia única
// Registrar transferencia pendiente
// Devolver datos bancarios
?>
```

#### `/api/payments/paypal/create-order.php`
```php
<?php
require_once '../../config.php';
// Integración con PayPal SDK
// Crear orden PayPal
// Devolver approvalUrl
?>
```

#### `/api/payments/cash-on-delivery/create.php`
```php
<?php
require_once '../../config.php';
// Registrar pedido con pago pendiente
// Calcular comisión contrarreembolso
// Devolver confirmación
?>
```

### 2. Descomentar Código Real

En `app/services/payment.service.js`, en cada método:

1. **Descomentar** el bloque de código comentado con `/* */`
2. **Eliminar** el bloque de simulación
3. **Probar** con las APIs reales

Ejemplo:
```javascript
async processCardPayment(paymentData) {
    // DESCOMENTAR ESTO:
    const response = await fetch(`${this.apiEndpoint}/card/create-intent`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify(paymentData)
    });
    
    if (!response.ok) {
        throw new Error(`Error del servidor: ${response.status}`);
    }
    
    const result = await response.json();
    return result;
    
    // ELIMINAR SIMULACIÓN:
    // return { success: true, ... }
}
```

### 3. Configurar Credenciales

Crear `/api/config/payment-credentials.php`:
```php
<?php
return [
    'stripe' => [
        'publishable_key' => 'pk_live_...',
        'secret_key' => 'sk_live_...'
    ],
    'paypal' => [
        'client_id' => 'YOUR_PAYPAL_CLIENT_ID',
        'client_secret' => 'YOUR_PAYPAL_SECRET'
    ],
    'redsys' => [
        'merchant_code' => 'YOUR_MERCHANT_CODE',
        'terminal' => '001',
        'secret_key' => 'YOUR_REDSYS_KEY'
    ],
    'bank_transfer' => [
        'iban' => 'ES12 3456 7890 1234 5678 9012',
        'bank_name' => 'Banco Real',
        'account_holder' => 'Economía Circular Canarias S.L.'
    ]
];
?>
```

### 4. Instalar Dependencias

```bash
# Stripe PHP SDK
composer require stripe/stripe-php

# PayPal PHP SDK
composer require paypal/rest-api-sdk-php
```

### 5. Crear Tabla de Transacciones

```sql
CREATE TABLE payment_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id VARCHAR(50) NOT NULL,
    user_id INT NOT NULL,
    payment_method ENUM('card', 'bizum', 'transfer', 'paypal', 'cash_on_delivery'),
    transaction_id VARCHAR(100) UNIQUE,
    amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    metadata JSON,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## 📊 Testing

### Probar Modo Simulación (Actual)

1. Agregar productos al carrito
2. Hacer clic en "Proceder al Pago"
3. Seleccionar cualquier método de pago
4. Hacer clic en "Pagar"
5. ✅ Debería mostrar éxito inmediatamente

### Probar Modo Producción (Futuro)

1. Configurar credenciales reales
2. Usar tarjetas de prueba (Stripe Test Mode)
3. Verificar que las transacciones se registren en la base de datos
4. Verificar webhooks de confirmación
5. Probar flujos de error y reembolso

## 🔐 Seguridad

### Importante antes de pasar a producción:

- ✅ Validar todos los datos en el backend
- ✅ Usar HTTPS en todas las peticiones
- ✅ Implementar rate limiting
- ✅ Registrar todas las transacciones en logs
- ✅ Implementar sistema de webhooks
- ✅ Validar firmas de respuestas de pasarelas
- ✅ Encriptar credenciales sensibles
- ✅ Implementar sistema de auditoría

## 📝 Notas Importantes

1. **NO** usar credenciales reales en código versionado
2. **SIEMPRE** usar variables de entorno para secretos
3. **NUNCA** exponer claves API en el frontend
4. **IMPLEMENTAR** sistema de logging robusto
5. **CONFIGURAR** alertas para transacciones fallidas
6. **MANTENER** backups de transacciones

---

**Estado**: 🟡 DEMO - Simulación activa
**Última actualización**: 9 de octubre de 2025
**Desarrollado por**: Equipo Economía Circular Canarias
