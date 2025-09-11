# ✅ Configuración HTTPS - Economía Circular Canarias

## 📋 Configuración actualizada para HTTPS

### 🔒 Protocolo
- **Servidor:** HTTPS localhost
- **Puerto:** 443 (estándar HTTPS)
- **Certificado:** Certificado local/desarrollo

### 🌐 URLs de acceso
- **Aplicación:** `https://localhost/Canarias-EC/`
- **API Auth:** `https://localhost/Canarias-EC/api/auth/`
- **Configuración:** `https://localhost/Canarias-EC/api/https-info.php`

### 🔧 Archivos actualizados

#### `.env`
```env
CORS_ORIGIN=https://localhost
```

#### `api/config.php`
```php
define('SITE_URL', 'https://localhost');

// CORS solo permite HTTPS
$allowedOrigins = [
    'https://localhost',
    'https://localhost:443', 
    'https://127.0.0.1',
    'https://127.0.0.1:443'
];
```

### 🗄️ Base de datos
- **Tipo:** MySQL (localhost)
- **Host:** localhost:3306
- **Base de datos:** canarias_ec
- **Usuario:** root
- **Contraseña:** (vacía)

### 🔐 Seguridad
- ✅ Solo conexiones HTTPS permitidas
- ✅ CORS configurado para HTTPS
- ✅ Headers de seguridad aplicados
- ✅ Tokens JWT seguros

### 📝 Diferencia con Nexus Astralis
- **Nexus Astralis:** SQL Server + IP pública + HTTPS
- **Canarias EC:** MySQL + localhost + HTTPS

¡Todo configurado para HTTPS! 🚀
