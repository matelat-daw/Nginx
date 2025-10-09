# 🛒 Sistema de Persistencia del Carrito

## 📋 Descripción General

El carrito de compras mantiene los productos guardados automáticamente, permitiendo a los usuarios navegar, cerrar sesión, o registrarse sin perder sus artículos.

## ✨ Características Principales

### 1. **Persistencia Automática**
- Los productos se guardan en `localStorage` automáticamente
- Clave de almacenamiento: `canarias_cart`
- Se guarda después de cada operación (agregar, actualizar, eliminar)

### 2. **Flujo de Autenticación Mejorado**

Cuando un usuario NO autenticado intenta proceder al pago, ve un modal con 3 opciones:

```
┌────────────────────────────────────────┐
│  🔐 Autenticación Requerida            │
├────────────────────────────────────────┤
│  Para proceder con el pago, necesitas  │
│  iniciar sesión en tu cuenta.          │
│                                        │
│  💡 Tus productos quedarán guardados   │
│                                        │
│  [Cancelar] [✨ Registrarte] [🔐 Iniciar Sesión] │
└────────────────────────────────────────┘
```

#### Opciones:

1. **Cancelar** - Cierra el modal y permite seguir comprando
2. **✨ Registrarte** - Va a `/register` manteniendo el carrito
3. **🔐 Iniciar Sesión** - Va a `/login` manteniendo el carrito

### 3. **Cómo Funciona la Persistencia**

#### Guardar Carrito
```javascript
// En app/services/cart.service.js
saveToStorage() {
    const cartData = {
        items: this.items,
        total: this.total,
        timestamp: new Date().toISOString()
    };
    localStorage.setItem('canarias_cart', JSON.stringify(cartData));
}
```

#### Cargar Carrito
```javascript
loadFromStorage() {
    const savedCart = localStorage.getItem('canarias_cart');
    if (savedCart) {
        const cartData = JSON.parse(savedCart);
        this.items = cartData.items || [];
        this.total = cartData.total || 0;
    }
}
```

## 🔄 Flujo Completo del Usuario

### Escenario 1: Usuario se registra
1. Usuario agrega productos al carrito ✅
2. Click en "Proceder al Pago" 🛒
3. Ve modal de autenticación 🔐
4. Click en "✨ Registrarte" 
5. Se registra en `/register` 📝
6. **El carrito se mantiene** (localStorage persiste)
7. Después del registro, puede continuar con el pago 💳

### Escenario 2: Usuario inicia sesión
1. Usuario agrega productos al carrito ✅
2. Click en "Proceder al Pago" 🛒
3. Ve modal de autenticación 🔐
4. Click en "🔐 Iniciar Sesión"
5. Inicia sesión en `/login` 🔑
6. **El carrito se mantiene** (localStorage persiste)
7. Puede continuar con el pago inmediatamente 💳

### Escenario 3: Usuario cierra navegador
1. Usuario agrega productos al carrito ✅
2. Cierra el navegador ❌
3. Abre el navegador más tarde 🌐
4. **El carrito sigue ahí** (localStorage persiste entre sesiones)

## 🎨 Mejoras de Diseño

### Contraste Mejorado
- ✅ Header con gradiente morado (`#667eea` → `#764ba2`)
- ✅ Texto blanco sobre fondo oscuro para mejor legibilidad
- ✅ Backdrop blur para enfocar la atención
- ✅ Sombras más pronunciadas
- ✅ Botones con gradientes y efectos hover

### Accesibilidad
- ✅ Contraste WCAG AAA compliant
- ✅ Tamaños de fuente legibles (14-22px)
- ✅ Botones con área de click generosa (12px padding)
- ✅ Cierre con tecla `Escape`
- ✅ Cierre con click fuera del modal

## 📦 Estructura de Datos

```javascript
// Formato en localStorage
{
    "items": [
        {
            "id": 123,
            "name": "Producto Ecológico",
            "price": 15.99,
            "quantity": 2,
            "image": "url-imagen.jpg",
            "seller": "Agricultor Local"
        }
    ],
    "total": 31.98,
    "timestamp": "2025-10-09T10:30:00.000Z"
}
```

## 🔧 Mantenimiento

### Limpiar Carrito Manualmente
```javascript
// En la consola del navegador
localStorage.removeItem('canarias_cart');
cartService.items = [];
cartService.total = 0;
```

### Ver Carrito Guardado
```javascript
// En la consola del navegador
console.log(JSON.parse(localStorage.getItem('canarias_cart')));
```

## 🚀 Futuras Mejoras

- [ ] Sincronizar carrito con backend después del login
- [ ] Asociar carrito a usuario en base de datos
- [ ] Detectar productos eliminados o sin stock
- [ ] Validar precios al proceder al pago
- [ ] Caducidad del carrito (ej: 7 días)
- [ ] Notificación si un producto cambia de precio

## 📝 Notas Importantes

1. **localStorage tiene límite**: ~5MB por dominio
2. **Los datos persisten**: Solo se borran si el usuario limpia el navegador
3. **No es seguro**: Nunca guardar información sensible (contraseñas, tarjetas)
4. **Validar en backend**: Siempre verificar precios y stock al crear la orden

---

**Última actualización**: 9 de octubre de 2025
**Desarrollado por**: Equipo Economía Circular Canarias
