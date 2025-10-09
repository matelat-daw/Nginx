# 📧 Sistema de Emails - Economía Circular Canarias

## 📋 Descripción General

El sistema de emails envía automáticamente confirmaciones por correo electrónico a los usuarios en diferentes eventos de la aplicación.

## ✉️ Tipos de Emails Implementados

### 1. 🎉 Email de Bienvenida y Confirmación de Registro

**Archivo**: `api/config.php` → función `sendWelcomeEmail()`

**Cuándo se envía**: Al registrarse un nuevo usuario

**Contenido**:
- Saludo personalizado con el nombre del usuario
- Enlace de confirmación de email
- Diseño con gradiente morado corporativo
- Botón destacado para confirmar

**Ejemplo de uso**:
```php
$emailResult = sendWelcomeEmail($email, $firstName, $userId, $emailConfirmationToken);
```

### 2. 🎁 Email de Confirmación de Pedido

**Archivo**: `api/config.php` → función `sendOrderConfirmationEmail()`

**Cuándo se envía**: Después de completar un pedido exitosamente

**Contenido**:
- Número de pedido único
- Método de pago utilizado
- Fecha y hora del pedido
- Lista detallada de productos:
  - Cantidad x Nombre del producto
  - Precio unitario
  - Subtotal por línea
- Total del pedido
- Próximos pasos
- Botón para ver el pedido

**Ejemplo de uso**:
```php
$orderData = [
    'orderId' => 'PEDIDO-1728567890',
    'items' => [
        ['name' => 'Producto 1', 'quantity' => 2, 'price' => 15.99],
        ['name' => 'Producto 2', 'quantity' => 1, 'price' => 25.00]
    ],
    'subtotal' => 56.98,
    'customerInfo' => [
        'name' => 'Juan Pérez',
        'email' => 'juan@example.com'
    ],
    'paymentMethod' => 'card'
];

$emailResult = sendOrderConfirmationEmail($orderData);
```

## ⚙️ Configuración

### Variables de Email (config.php)

```php
define('EMAIL_FROM', 'matelat@gmail.com');
define('EMAIL_FROM_NAME', 'Canarias Circular');
define('SITE_URL', 'https://localhost');
```

### Servidor SMTP

El sistema usa la función `mail()` de PHP. Para producción, configura un servidor SMTP en `php.ini`:

```ini
[mail function]
SMTP = smtp.gmail.com
smtp_port = 587
sendmail_from = matelat@gmail.com
sendmail_path = "\"C:\xampp\sendmail\sendmail.exe\" -t"
```

O usa **PHPMailer** para mayor compatibilidad:

```bash
composer require phpmailer/phpmailer
```

## 🔄 Flujo del Email de Pedido

```
Usuario completa pago
        ↓
PaymentModal.handlePaymentSuccess()
        ↓
POST /api/orders/create-order.php
        ↓
1. Insertar pedido en DB
2. Insertar items del pedido
3. sendOrderConfirmationEmail() ← 📧
        ↓
Usuario recibe email
```

## 🎨 Diseño de Emails

### Características de Diseño

- **Responsive**: Ancho máximo 600px, se adapta a móviles
- **Colores corporativos**: 
  - Header: Gradiente morado (`#667eea` → `#764ba2`)
  - Primario: `#667eea`
  - Éxito: `#10b981`
- **Tipografía**: Arial, sans-serif
- **Emojis**: Para mejorar la experiencia visual 🏝️ 🎉 📦 💳
- **Sombras**: Box-shadow para profundidad
- **Tablas**: Para mostrar productos de forma estructurada

### Estructura HTML

```html
<!DOCTYPE html>
<html>
<head><meta charset='UTF-8'></head>
<body style='font-family: Arial, sans-serif;'>
    <div style='max-width: 600px; margin: 0 auto;'>
        <!-- Header con gradiente -->
        <div style='background: linear-gradient(135deg, #667eea, #764ba2);'>
            <h1>🏝️ Canarias Circular</h1>
        </div>
        
        <!-- Contenido principal -->
        <div style='padding: 30px 20px;'>
            <!-- Mensaje personalizado -->
            <!-- Información destacada -->
            <!-- Tabla de productos -->
            <!-- Botón de acción -->
        </div>
    </div>
</body>
</html>
```

## 🧪 Modo Desarrollo

### Comportamiento en Localhost

Cuando `SITE_URL` contiene `localhost` o `127.0.0.1`:

1. **No se envía email real**
2. **Se registra en logs**:
   ```
   DESARROLLO - Email de pedido no enviado para juan@example.com. Pedido: PEDIDO-1728567890
   ```
3. **Se guarda en archivo temporal**:
   - Archivo: `temp_order_emails.txt`
   - Contenido: Timestamp, email, pedido, total

4. **Retorna información de debug**:
   ```php
   [
       'sent' => false,
       'development' => true,
       'message' => 'Email no enviado - Desarrollo local'
   ]
   ```

### Ver Emails Guardados

```bash
# Windows PowerShell
Get-Content c:\Projects\Canarias-EC\temp_order_emails.txt -Tail 10

# Ejemplo de salida:
[2025-10-09 14:30:45] Usuario: cliente@example.com | Pedido: PEDIDO-1728567890 | Total: 56.98€
```

## 🚀 Migración a Producción

### 1. Configurar Servidor SMTP Real

#### Opción A: Gmail SMTP

1. Crear App Password en Google Account
2. Configurar en `php.ini`:
```ini
[mail function]
SMTP=smtp.gmail.com
smtp_port=587
auth_username=tu-email@gmail.com
auth_password=tu-app-password
```

#### Opción B: SendGrid

```bash
composer require sendgrid/sendgrid
```

```php
$from = new SendGrid\Mail\From("matelat@gmail.com", "Canarias Circular");
$to = new SendGrid\Mail\To($userEmail);
$subject = "Confirmación de Pedido";
$content = new SendGrid\Mail\Content("text/html", $htmlContent);
$mail = new SendGrid\Mail\Mail($from, $to, $subject, $content);

$sendgrid = new \SendGrid(getenv('SENDGRID_API_KEY'));
$response = $sendgrid->send($mail);
```

#### Opción C: Mailgun

```bash
composer require mailgun/mailgun-php
```

### 2. Actualizar Variables

```php
define('EMAIL_FROM', 'noreply@canariascircular.com');
define('EMAIL_FROM_NAME', 'Canarias Circular');
define('SITE_URL', 'https://canariascircular.com');
```

### 3. Probar Envíos

```php
// Test email de pedido
$testOrder = [
    'orderId' => 'TEST-' . time(),
    'items' => [
        ['name' => 'Test Product', 'quantity' => 1, 'price' => 9.99]
    ],
    'subtotal' => 9.99,
    'customerInfo' => [
        'name' => 'Test User',
        'email' => 'tu-email@example.com'
    ],
    'paymentMethod' => 'card'
];

$result = sendOrderConfirmationEmail($testOrder);

if ($result) {
    echo "✅ Email de prueba enviado correctamente";
} else {
    echo "❌ Error al enviar email";
}
```

## 📊 Monitoreo y Logs

### Ver Logs de Emails

```php
// En config.php, la función logMessage() guarda:
// - INFO: Emails enviados exitosamente
// - WARNING: Errores al enviar emails
// - ERROR: Fallos críticos

// Consultar logs
tail -f /ruta/a/logs/app.log | grep "Email"
```

### Métricas Importantes

- ✅ **Tasa de envío**: % de emails enviados vs intentados
- ✅ **Tasa de apertura**: Usar servicio como SendGrid para tracking
- ✅ **Tasa de click**: En botones del email
- ✅ **Bounces**: Emails rebotados (direcciones inválidas)

## 🔐 Seguridad

### Mejores Prácticas

1. **No incluir información sensible**:
   - ❌ Contraseñas
   - ❌ Números de tarjeta completos
   - ✅ Solo últimos 4 dígitos si es necesario

2. **Validar destinatarios**:
```php
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    throw new Exception("Email inválido");
}
```

3. **Rate limiting**:
```php
// Limitar emails por usuario/hora
$emailsSentThisHour = getEmailCountLastHour($userId);
if ($emailsSentThisHour > 10) {
    throw new Exception("Límite de emails excedido");
}
```

4. **SPF y DKIM**:
   - Configurar registros DNS para evitar spam

## 🛠️ Troubleshooting

### Email no se envía

1. **Verificar función mail() disponible**:
```php
if (function_exists('mail')) {
    echo "✅ mail() disponible";
} else {
    echo "❌ mail() no disponible - instalar/configurar SMTP";
}
```

2. **Verificar logs de PHP**:
```bash
tail -f /xampp/php/logs/php_error_log
```

3. **Verificar configuración SMTP**:
```bash
telnet smtp.gmail.com 587
```

### Email va a spam

1. Configurar SPF record en DNS
2. Configurar DKIM
3. Usar dominio corporativo (`@canariascircular.com`)
4. Evitar palabras spam ("gratis", "oferta", etc.)
5. Incluir enlace de "Darse de baja"

### HTML no se renderiza

1. Usar estilos inline (no CSS externo)
2. Usar tablas para layout
3. Evitar JavaScript
4. Probar en [Litmus](https://litmus.com) o [Email on Acid](https://www.emailonacid.com)

## 📝 Plantillas Futuras

### Emails Pendientes de Implementar

- [ ] 📦 **Pedido enviado**: Con tracking number
- [ ] 🚚 **Pedido en camino**: Actualización de estado
- [ ] ✅ **Pedido entregado**: Solicitar review
- [ ] 🔄 **Devolución procesada**: Confirmación de reembolso
- [ ] 💰 **Recordatorio de pago**: Para transferencias pendientes
- [ ] 🎂 **Felicitación de cumpleaños**: Con descuento especial
- [ ] 📰 **Newsletter mensual**: Productos destacados
- [ ] ⚠️ **Recuperación de carrito**: Carrito abandonado

---

**Última actualización**: 9 de octubre de 2025  
**Desarrollado por**: Equipo Economía Circular Canarias
