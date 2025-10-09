# 💳 Sistema de Pagos - Economía Circular Canarias

## 📋 Descripción

Sistema modular de pagos que soporta múltiples métodos de pago para tu aplicación de e-commerce.

## 🏦 Métodos de Pago Soportados

### 1. **Tarjetas de Crédito/Débito** 💳
- **Proveedor**: Stripe
- **Tarjetas**: Visa, Mastercard, American Express, etc.
- **Comisión**: 1.4% + 0.25€ por transacción
- **Requiere**: Cuenta Stripe

### 2. **Bizum** 📱
- **Proveedor**: Redsys (a través de tu banco)
- **Comisión**: Gratuito para particulares
- **Requiere**: Integración con banco español

### 3. **Transferencia Bancaria** 🏦
- **Tipo**: SEPA
- **Comisión**: Gratuita
- **Tiempo**: 24-48 horas
- **Requiere**: Cuenta bancaria

### 4. **PayPal** 🅿️
- **Proveedor**: PayPal
- **Comisión**: 3.4% + 0.35€ por transacción
- **Requiere**: Cuenta PayPal Business

### 5. **Contrarreembolso** 💵
- **Tipo**: Pago en efectivo al recibir
- **Comisión**: 3.00€ gastos de gestión
- **Requiere**: Acuerdo con transportista

## 🚀 Configuración

### Paso 1: Stripe (Tarjetas)

1. **Crear cuenta en Stripe**: https://stripe.com/es
2. **Obtener claves API**:
   - Dashboard → Developers → API keys
   - Clave pública (empieza con `pk_`)
   - Clave secreta (empieza con `sk_`)

3. **Actualizar código**:
```javascript
// En payment-modal.component.js, línea ~140
this.stripe = Stripe('pk_live_TU_CLAVE_PUBLICA_AQUI');
```

4. **Backend PHP** (crear archivo):
```php
// api/payments/card/create-intent.php
<?php
require_once '../../../vendor/autoload.php';

\Stripe\Stripe::setApiKey('sk_live_TU_CLAVE_SECRETA');

$input = json_decode(file_get_contents('php://input'), true);

$intent = \Stripe\PaymentIntent::create([
    'amount' => $input['amount'] * 100, // en centavos
    'currency' => 'eur',
    'metadata' => ['order_id' => $input['orderId']]
]);

echo json_encode(['success' => true, 'clientSecret' => $intent->client_secret]);
?>
```

### Paso 2: Bizum (Redsys)

1. **Contactar con tu banco** para activar Bizum empresarial
2. **Obtener credenciales** de Redsys
3. **Implementar integración**:
```php
// api/payments/bizum/initiate.php
<?php
// Código proporcionado por tu banco/Redsys
?>
```

### Paso 3: PayPal

1. **Crear cuenta PayPal Business**: https://www.paypal.com/es/business
2. **Obtener credenciales**:
   - Dashboard → Apps & Credentials
   - Client ID y Secret

3. **Actualizar código**:
```javascript
// En payment-modal.component.js, línea ~175
script.src = 'https://www.paypal.com/sdk/js?client-id=TU_CLIENT_ID&currency=EUR';
```

4. **Backend PHP**:
```php
// api/payments/paypal/create-order.php
<?php
// Usar PayPal REST API
?>
```

### Paso 4: Transferencia y Contrarreembolso

Estos métodos no requieren integración con terceros:

1. **Transferencia**: Configura tu IBAN en:
```javascript
// api/payments/transfer/initiate.php
$bankDetails = [
    'iban' => 'ES00 0000 0000 0000 0000 0000',
    'beneficiary' => 'Economía Circular Canarias',
    'bic' => 'XXXXXXXXXX'
];
```

2. **Contrarreembolso**: Solo requiere lógica de negocio

## 📦 Instalación de Dependencias

### Stripe (PHP)
```bash
composer require stripe/stripe-php
```

### PayPal (PHP)
```bash
composer require paypal/rest-api-sdk-php
```

## 🧪 Probar el Sistema

1. **Abrir demo**:
```
http://localhost/demo-pagos.html
```

2. **Usar datos de prueba** (modo test):

**Tarjeta de prueba Stripe**:
- Número: 4242 4242 4242 4242
- Fecha: Cualquier fecha futura
- CVC: Cualquier 3 dígitos

**PayPal Sandbox**:
- Crear cuenta sandbox en PayPal Developer

## 💻 Uso en tu Aplicación

```javascript
// En tu componente de checkout
const orderData = {
    orderId: 'PEDIDO-123',
    items: [
        { name: 'Producto 1', quantity: 2, price: 10.00 },
        { name: 'Producto 2', quantity: 1, price: 25.00 }
    ],
    subtotal: 45.00,
    customerInfo: {
        name: 'Juan Pérez',
        email: 'juan@example.com',
        phone: '+34 123 456 789'
    }
};

// Mostrar modal de pago
const paymentModal = new PaymentModal(orderData);
paymentModal.show();

// Escuchar resultado
window.addEventListener('paymentCompleted', (event) => {
    const { method, result } = event.detail;
    console.log(`Pago completado con ${method}:`, result);
    
    // Actualizar pedido, enviar confirmación, etc.
});
```

## 📊 Comisiones Comparadas

| Método | Comisión | Tiempo | Mejor para |
|--------|----------|--------|------------|
| Bizum | 0% | Instantáneo | Compras pequeñas locales |
| Transferencia | 0% | 24-48h | Compras grandes |
| Tarjeta | 1.4% + 0.25€ | Instantáneo | Compras internacionales |
| PayPal | 3.4% + 0.35€ | Instantáneo | Usuarios con cuenta PayPal |
| Contrarreembolso | 3.00€ fijo | Al recibir | Usuarios sin confianza online |

## 🔒 Seguridad

- ✅ Nunca almacenes datos de tarjetas en tu servidor
- ✅ Usa HTTPS en producción
- ✅ Valida todos los pagos en el backend
- ✅ Implementa webhooks para confirmaciones
- ✅ Registra todas las transacciones

## 📚 Documentación Oficial

- **Stripe**: https://stripe.com/docs
- **Redsys**: https://pagosonline.redsys.es/
- **PayPal**: https://developer.paypal.com/

## 🆘 Soporte

Para problemas o preguntas:
1. Revisa la consola del navegador
2. Verifica logs del servidor PHP
3. Consulta documentación oficial del proveedor

## 📝 Notas Importantes

- **Producción**: Cambia todas las claves de test a producción
- **Webhooks**: Implementa webhooks para confirmación asíncrona
- **Logs**: Mantén registro de todas las transacciones
- **Testing**: Prueba exhaustivamente antes de producción
- **Compliance**: Asegúrate de cumplir con PSD2 y GDPR

## 🎉 ¡Listo!

Tu sistema de pagos está configurado. Para más ayuda, consulta la documentación de cada proveedor.
