# API Flexible - Documentación

## Descripción General

Esta API flexible permite adaptarse a diferentes tipos de frontends, desde aplicaciones simples que solo requieren email/contraseña hasta sistemas complejos con perfiles completos de usuario.

## Características Principales

- **Configuración Dinámica**: Cambia entre diferentes perfiles según las necesidades del frontend
- **Validación Adaptable**: Los campos requeridos y opcionales se ajustan automáticamente
- **Base de Datos Flexible**: Se adapta a diferentes esquemas de tabla de usuarios
- **Múltiples Perfiles**: Perfiles predefinidos para casos comunes de uso

## Perfiles Disponibles

### 1. Perfil Mínimo (`minimal`)
**Uso**: Aplicaciones básicas de login
- **Campos requeridos**: email, password
- **Campos opcionales**: ninguno
- **Verificación email**: deshabilitada
- **Ideal para**: Aplicaciones simples, prototipos, MVPs

### 2. Perfil Estándar (`standard`)
**Uso**: Aplicaciones con registro de nombres
- **Campos requeridos**: email, password, firstName, lastName
- **Campos opcionales**: phoneNumber
- **Verificación email**: habilitada
- **Ideal para**: Aplicaciones web estándar, SaaS básicos

### 3. Perfil Completo (`complete`)
**Uso**: Sistemas con perfiles detallados
- **Campos requeridos**: email, password, firstName, lastName
- **Campos opcionales**: island, city, userType, phoneNumber, about
- **Verificación email**: habilitada
- **Ideal para**: Plataformas sociales, marketplaces, sistemas complejos

### 4. Perfil Personalizado (`custom`)
**Uso**: Configuraciones específicas del usuario
- **Campos**: definidos por el usuario
- **Verificación email**: configurable
- **Ideal para**: Casos específicos, integraciones personalizadas

## Endpoints

### Configuración

#### GET /api/config/
Obtiene la configuración actual y perfiles disponibles

**Respuesta:**
```json
{
  "current": {
    "profile": "standard",
    "config": {
      "name": "Registro Estándar",
      "required_fields": ["email", "password", "firstName", "lastName"],
      "optional_fields": ["phoneNumber"],
      "email_verification": true
    },
    "database": {
      "table": "ecc_users",
      "fields": ["id", "email", "first_name", "last_name", "password_hash", ...],
      "has_names": true,
      "has_verification": true
    }
  },
  "available_profiles": {
    "minimal": {...},
    "standard": {...},
    "complete": {...}
  },
  "recommendations": [...]
}
```

#### POST /api/config/
Cambia el perfil de configuración

**Cambiar a perfil predefinido:**
```json
{
  "profile": "minimal"
}
```

**Establecer configuración personalizada:**
```json
{
  "custom_config": {
    "name": "Mi Configuración",
    "required_fields": ["email", "password"],
    "optional_fields": ["firstName"],
    "email_verification": false,
    "profile_fields": ["id", "email", "firstName"]
  }
}
```

### Autenticación Flexible

#### POST /api/auth/flexible-register.php
Registro de usuarios adaptado a la configuración

**Ejemplo - Perfil Mínimo:**
```json
{
  "email": "usuario@ejemplo.com",
  "password": "mipassword123"
}
```

**Ejemplo - Perfil Estándar:**
```json
{
  "email": "usuario@ejemplo.com",
  "password": "mipassword123",
  "firstName": "Juan",
  "lastName": "Pérez"
}
```

**Ejemplo - Perfil Completo:**
```json
{
  "email": "usuario@ejemplo.com",
  "password": "mipassword123",
  "firstName": "Juan",
  "lastName": "Pérez",
  "island": "Gran Canaria",
  "city": "Las Palmas",
  "userType": "individual",
  "phoneNumber": "+34123456789"
}
```

**Respuesta exitosa:**
```json
{
  "user": {
    "id": 1,
    "email": "usuario@ejemplo.com",
    "firstName": "Juan",
    "lastName": "Pérez"
  },
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "message": "Usuario registrado exitosamente"
}
```

#### POST /api/auth/flexible-login.php
Login de usuarios adaptado a la configuración

**Solicitud:**
```json
{
  "email": "usuario@ejemplo.com",
  "password": "mipassword123"
}
```

**Respuesta exitosa:**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "user": {
    "id": 1,
    "email": "usuario@ejemplo.com",
    "firstName": "Juan",
    "lastName": "Pérez",
    "emailVerified": true
  },
  "token_expires": "2024-08-24T10:30:00+00:00",
  "message": "Login exitoso"
}
```

### Perfil de Usuario

#### GET /api/auth/flexible-profile.php
Obtiene el perfil del usuario autenticado

**Headers:**
```
Authorization: Bearer your-jwt-token
```

**Respuesta:**
```json
{
  "user": {
    "id": 1,
    "email": "usuario@ejemplo.com",
    "firstName": "Juan",
    "lastName": "Pérez"
  },
  "available_fields": ["email", "firstName", "lastName", "phoneNumber"],
  "profile_fields": ["id", "email", "firstName", "lastName"],
  "profile": "standard"
}
```

#### PUT /api/auth/flexible-profile.php
Actualiza el perfil del usuario

**Headers:**
```
Authorization: Bearer your-jwt-token
```

**Solicitud:**
```json
{
  "firstName": "Juan Carlos",
  "phoneNumber": "+34987654321"
}
```

## Ejemplos de Integración

### Frontend Mínimo (Solo Login)

```javascript
// 1. Configurar API para perfil mínimo
await fetch('/api/config/', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ profile: 'minimal' })
});

// 2. Registrar usuario
const registerResponse = await fetch('/api/auth/flexible-register.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    email: 'usuario@ejemplo.com',
    password: 'mipassword123'
  })
});

// 3. Login
const loginResponse = await fetch('/api/auth/flexible-login.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    email: 'usuario@ejemplo.com',
    password: 'mipassword123'
  })
});
```

### Frontend Estándar (Con Nombres)

```javascript
// 1. Configurar API para perfil estándar
await fetch('/api/config/', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ profile: 'standard' })
});

// 2. Registrar usuario con nombres
const registerResponse = await fetch('/api/auth/flexible-register.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    email: 'usuario@ejemplo.com',
    password: 'mipassword123',
    firstName: 'Juan',
    lastName: 'Pérez'
  })
});
```

### Configuración Personalizada

```javascript
// Configuración para una app específica
await fetch('/api/config/', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    custom_config: {
      name: 'Mi App Personalizada',
      required_fields: ['email', 'password', 'firstName'],
      optional_fields: ['lastName', 'phoneNumber'],
      email_verification: false,
      profile_fields: ['id', 'email', 'firstName', 'phoneNumber']
    }
  })
});
```

## Migración desde API Existente

### Paso 1: Analizar configuración actual
```bash
GET /api/config/
```
Esto te mostrará la configuración detectada automáticamente y recomendaciones.

### Paso 2: Elegir perfil apropiado
Basado en las recomendaciones, selecciona el perfil que mejor se adapte:
- Si solo usas email/password → `minimal`
- Si incluyes nombres → `standard`  
- Si tienes campos adicionales → `complete`
- Si necesitas algo específico → `custom`

### Paso 3: Cambiar endpoints gradualmente
- Cambia `/api/auth/register.php` → `/api/auth/flexible-register.php`
- Cambia `/api/auth/login.php` → `/api/auth/flexible-login.php`
- Usa `/api/auth/flexible-profile.php` para perfiles

### Paso 4: Verificar compatibilidad
Los nuevos endpoints son retrocompatibles con los formatos de datos existentes.

## Configuración de Base de Datos

La API se adapta automáticamente a diferentes esquemas de tabla:

### Tabla Mínima
```sql
CREATE TABLE users (
  id INT PRIMARY KEY AUTO_INCREMENT,
  email VARCHAR(255) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Tabla Estándar
```sql
CREATE TABLE ecc_users (
  id INT PRIMARY KEY AUTO_INCREMENT,
  email VARCHAR(255) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  first_name VARCHAR(100),
  last_name VARCHAR(100),
  email_verified BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Tabla Completa
```sql
CREATE TABLE ecc_users (
  id INT PRIMARY KEY AUTO_INCREMENT,
  email VARCHAR(255) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  first_name VARCHAR(100),
  last_name VARCHAR(100),
  phone_number VARCHAR(20),
  island VARCHAR(100),
  city VARCHAR(100),
  user_type ENUM('individual', 'business') DEFAULT 'individual',
  about TEXT,
  profile_image VARCHAR(255),
  email_verified BOOLEAN DEFAULT FALSE,
  account_locked BOOLEAN DEFAULT FALSE,
  failed_login_attempts INT DEFAULT 0,
  last_successful_login TIMESTAMP NULL,
  last_failed_login TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

## Códigos de Error

- `400` - Datos inválidos o campos requeridos faltantes
- `401` - Credenciales incorrectas o token inválido
- `403` - Acceso denegado
- `404` - Usuario no encontrado
- `405` - Método no permitido
- `409` - Email ya registrado
- `423` - Cuenta bloqueada
- `500` - Error interno del servidor

## Notas de Seguridad

- Todos los passwords se hashean con `password_hash()` de PHP
- Los tokens JWT incluyen tiempo de expiración
- Las cuentas se bloquean después de múltiples intentos fallidos
- Validación de email opcional según configuración
- Headers CORS configurables

## Persistencia de Configuración

La configuración se guarda automáticamente en `/api/config/current-config.json` y se carga al inicializar la API, manteniendo la configuración entre reinicios del servidor.
