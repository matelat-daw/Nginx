<?php
/**
 * Configuración API Optimizada - Economía Circular Canarias
 * Versión 2.0 - Todas las optimizaciones implementadas
 */

// ====================================
// INICIAR SESIÓN
// ====================================
if (session_status() === PHP_SESSION_NONE) {
    @session_start(); // @ para suprimir warnings si hay output previo
}

// ====================================
// AUTOLOADER PSR-4
// ====================================
spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/models/',
        __DIR__ . '/repositories/',
        __DIR__ . '/services/',
        __DIR__ . '/middleware/'
    ];
    
    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// ====================================
// COMPRESIÓN GZIP PARA RESPUESTAS
// ====================================
// Solo activar output buffering si NO estamos en un endpoint de API JSON
$isJsonEndpoint = (
    strpos($_SERVER['REQUEST_URI'] ?? '', '/api/orders/') !== false ||
    strpos($_SERVER['REQUEST_URI'] ?? '', '/api/products/') !== false ||
    (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)
);

if (!$isJsonEndpoint) {
    if (!ob_start('ob_gzhandler')) {
        ob_start();
    }
}

// ====================================
// JWT UNIFICADO - Implementación completa y optimizada
// ====================================
class JWT {
    /**
     * Generar token JWT para login/register
     */
    public static function generateToken($userId, $email, $expiration = null) {
        $exp = $expiration ?? (time() + JWT_EXPIRATION);
        $payload = [
            'userId' => $userId,
            'user_id' => $userId, // Compatibilidad
            'email' => $email,
            'exp' => $exp,
            'iat' => time()
        ];
        
        return self::encode($payload, JWT_SECRET);
    }
    
    /**
     * Codificar payload en JWT
     */
    public static function encode($payload, $key) {
        if (is_array($payload)) {
            $payload = json_encode($payload);
        }
        
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        
        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        
        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $key, true);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        return $base64Header . "." . $base64Payload . "." . $base64Signature;
    }
    
    /**
     * Decodificar y validar JWT
     */
    public static function decode($jwt, $key) {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new Exception('Invalid JWT format');
        }
        
        list($base64Header, $base64Payload, $base64Signature) = $parts;
        
        $header = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $base64Header)), true);
        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $base64Payload)), true);
        
        $expectedSignature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $key, true);
        $actualSignature = base64_decode(str_replace(['-', '_'], ['+', '/'], $base64Signature));
        
        if (!hash_equals($expectedSignature, $actualSignature)) {
            throw new Exception('Invalid JWT signature');
        }
        
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            throw new Exception('JWT token expired');
        }
        
        return (object) $payload;
    }
    
    /**
     * Validar token simple (retorna true/false)
     */
    public static function validate($jwt, $key) {
        try {
            self::decode($jwt, $key);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}

class Key {
    public $key;
    public $algorithm;
    
    public function __construct($key, $algorithm = 'HS256') {
        $this->key = $key;
        $this->algorithm = $algorithm;
    }
}

// Cargar variables de entorno
function loadEnvironmentVariables($filePath = __DIR__ . '/../.env') {
    if (!file_exists($filePath)) {
        error_log("Archivo .env no encontrado en: $filePath");
        return false;
    }
    
    $content = file_get_contents($filePath);
    $lines = explode("\n", $content);
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Saltar líneas vacías y comentarios
        if (empty($line) || strpos($line, '#') === 0) continue;
        
        // Verificar que tenga formato clave=valor
        if (strpos($line, '=') === false) continue;
        
        // Dividir en clave y valor
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        
        // Remover comillas si las hay, pero mantener el contenido
        if ((strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) ||
            (strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1)) {
            $value = substr($value, 1, -1);
        }
        
        $_ENV[$key] = $value;
        putenv("$key=$value");
        
        // Log para debugging (solo en modo desarrollo)
        if (defined('DEBUG_MODE') && DEBUG_MODE && $key === 'DB_PASS') {
            error_log("Variable $key cargada con longitud: " . strlen($value));
        }
    }
    return true;
}

loadEnvironmentVariables();

// Configuración esencial
define('ENVIRONMENT', $_ENV['ENVIRONMENT'] ?? 'development');
define('DEBUG_MODE', ENVIRONMENT === 'development');

// Base de datos
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'users');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('DB_CHARSET', $_ENV['DB_CHARSET'] ?? 'utf8mb4');
define('DB_PORT', (int)($_ENV['DB_PORT'] ?? 3306));

/**
 * Función centralizada para obtener conexión PDO segura
 * Evita repetir código de conexión en cada archivo
 */
function getDBConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false, // No usar conexiones persistentes por seguridad
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false, // Para desarrollo local
                PDO::ATTR_TIMEOUT => 30 // Timeout de 30 segundos
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            
            // Log de conexión exitosa solo en modo debug
            if (DEBUG_MODE) {
                error_log("BD: Conexión establecida exitosamente a " . DB_HOST . ":" . DB_PORT . "/" . DB_NAME);
            }
            
        } catch (PDOException $e) {
            // Log seguro del error (sin exponer credenciales)
            $errorMsg = "Error de conexión a BD: " . $e->getMessage();
            error_log($errorMsg);
            
            // En producción, no mostrar detalles del error
            if (!DEBUG_MODE) {
                throw new Exception('Error de conexión a la base de datos');
            } else {
                throw new Exception($errorMsg);
            }
        }
    }
    
    return $pdo;
}

// JWT
define('JWT_SECRET', $_ENV['JWT_SECRET'] ?? 'fallback_secret_key_change_in_production');
define('JWT_EXPIRATION', 24 * 60 * 60);

// Cookies
define('COOKIE_NAME', 'ecc_auth_token');
define('COOKIE_EXPIRATION', 24 * 60 * 60);
define('COOKIE_SECURE', false);
define('COOKIE_HTTP_ONLY', false);
define('COOKIE_SAME_SITE', 'Lax');

// Seguridad
define('PASSWORD_MIN_LENGTH', 6);

// Rate Limiting
define('RATE_LIMIT_REQUESTS', 100);
define('RATE_LIMIT_WINDOW', 3600); // 1 hora

// Email
define('EMAIL_FROM', 'matelat@gmail.com');
define('EMAIL_FROM_NAME', 'Canarias Circular');
define('SITE_URL', 'https://localhost');

// ====================================
// FUNCIONES HELPER OPTIMIZADAS
// ====================================

/**
 * Headers CORS centralizados
 */
function setCorsHeaders() {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $allowedOrigins = [
        'https://localhost',
        'https://localhost:443', 
        'https://127.0.0.1',
        'https://127.0.0.1:443'
    ];
    
    header("Access-Control-Allow-Origin: " . (in_array($origin, $allowedOrigins) ? $origin : 'https://localhost'));
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin");
    header("Content-Type: application/json; charset=utf-8");
}

/**
 * Manejar preflight requests
 */
function handlePreflight() {
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        setCorsHeaders();
        http_response_code(200);
        echo json_encode(['success' => true]);
        exit();
    }
}

/**
 * Respuestas JSON estandarizadas
 */
function jsonResponse($data, $statusCode = 200, $message = null) {
    http_response_code($statusCode);
    
    $response = [
        'success' => $statusCode >= 200 && $statusCode < 300,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    if ($message) $response['message'] = $message;
    if ($data !== null) $response['data'] = $data;
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * Validar y extraer token JWT de headers
 * @param bool $required Si es true, termina la ejecución si no hay token válido
 * @return object|null Payload del token o null
 */
function validateAuthToken($required = true) {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
        if ($required) {
            jsonResponse(null, 401, 'Token de autorización requerido');
        }
        return null;
    }
    
    try {
        $token = substr($authHeader, 7);
        return JWT::decode($token, JWT_SECRET);
    } catch (Exception $e) {
        if ($required) {
            jsonResponse(null, 401, 'Token inválido o expirado');
        }
        return null;
    }
}

/**
 * Aplicar middleware de seguridad y rate limiting
 */
function applySecurityMiddleware($rateLimit = true) {
    // Headers de seguridad
    header('X-XSS-Protection: 1; mode=block');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // CSP básico
    $csp = "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';";
    header("Content-Security-Policy: {$csp}");
    
    // Rate limiting si está habilitado
    if ($rateLimit) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $cacheFile = sys_get_temp_dir() . '/rate_limit_' . md5($ip) . '.tmp';
        
        $now = time();
        $requests_data = [];
        
        // Leer requests anteriores
        if (file_exists($cacheFile)) {
            $data = file_get_contents($cacheFile);
            $requests_data = json_decode($data, true) ?: [];
        }
        
        // Filtrar requests dentro de la ventana de tiempo
        $requests_data = array_filter($requests_data, function($timestamp) use ($now) {
            return ($now - $timestamp) < RATE_LIMIT_WINDOW;
        });
        
        // Verificar límite
        if (count($requests_data) >= RATE_LIMIT_REQUESTS) {
            jsonResponse(null, 429, 'Demasiadas solicitudes. Intenta de nuevo más tarde.');
        }
        
        // Agregar request actual
        $requests_data[] = $now;
        file_put_contents($cacheFile, json_encode($requests_data), LOCK_EX);
    }
}

/**
 * Cacheo simple de consultas
 */
function getCachedData($key, $callback, $ttl = 3600) {
    $cacheFile = sys_get_temp_dir() . '/cache_' . md5($key) . '.tmp';
    
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $ttl)) {
        $data = file_get_contents($cacheFile);
        return json_decode($data, true);
    }
    
    $data = $callback();
    file_put_contents($cacheFile, json_encode($data), LOCK_EX);
    return $data;
}

// Logging básico
function logMessage($level, $message) {
    if (DEBUG_MODE || $level !== 'DEBUG') {
        error_log("[" . date('Y-m-d H:i:s') . "] [$level] $message");
    }
}

// Email de bienvenida con fallback para desarrollo
function sendWelcomeEmail($userEmail, $userName, $userId, $confirmationToken) {
    $confirmationUrl = SITE_URL . "/api/auth/confirm-email.php?token=" . urlencode($confirmationToken) . "&id=" . $userId;
    
    $headers = [
        'From: ' . EMAIL_FROM_NAME . ' <' . EMAIL_FROM . '>',
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8'
    ];
    
    $subject = "🏝️ ¡Bienvenido/a a Canarias Circular, $userName!";
    
    $htmlContent = "<!DOCTYPE html><html><head><meta charset='UTF-8'></head>
    <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
        <div style='max-width: 600px; margin: 0 auto; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.1);'>
            <div style='background: linear-gradient(135deg, #1e3a8a, #3b82f6); color: white; padding: 30px 20px; text-align: center;'>
                <h1 style='margin: 0; font-size: 28px;'>🏝️ Canarias Circular</h1>
                <p style='margin: 5px 0 0 0; opacity: 0.9;'>Economía Circular en las Islas Canarias</p>
            </div>
            <div style='padding: 30px 20px;'>
                <h2 style='color: #1e3a8a;'>¡Bienvenido/a, $userName! 🎉</h2>
                <p>Tu cuenta ha sido creada exitosamente. Confirma tu email para activar todas las funcionalidades.</p>
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='$confirmationUrl' style='background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 15px 30px; text-decoration: none; border-radius: 25px; font-weight: bold; display: inline-block;'>
                        ✅ Confirmar mi Email
                    </a>
                </div>
                <p style='font-size: 12px; color: #666;'>Si no puedes hacer clic, copia este enlace: $confirmationUrl</p>
            </div>
        </div>
    </body></html>";
    
    // Intentar enviar email
    $emailSent = false;
    
    try {
        if (function_exists('mail')) {
            $emailSent = mail($userEmail, $subject, $htmlContent, implode("\r\n", $headers));
        }
    } catch (Exception $e) {
        logMessage('WARNING', "Error sending email: " . $e->getMessage());
    }
    
    // Si es desarrollo local y el email no se pudo enviar, guardar el enlace en logs
    if (!$emailSent && (strpos(SITE_URL, 'localhost') !== false || strpos(SITE_URL, '127.0.0.1') !== false)) {
        $logMessage = "DESARROLLO - Email no enviado para {$userEmail}. Enlace de confirmación: {$confirmationUrl}";
        logMessage('INFO', $logMessage);
        
        // También guardar en archivo temporal para mostrar al usuario
        $tempFile = __DIR__ . '/../temp_confirmation_links.txt';
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($tempFile, "[$timestamp] Usuario: $userEmail | Enlace: $confirmationUrl\n", FILE_APPEND | LOCK_EX);
        
        // Retornar array con información adicional para desarrollo
        return [
            'sent' => false,
            'development' => true,
            'confirmationUrl' => $confirmationUrl,
            'message' => 'Email no enviado - Desarrollo local'
        ];
    }
    
    return $emailSent;
}

// Enviar email de confirmación de pedido
function sendOrderConfirmationEmail($orderData) {
    $userEmail = $orderData['customerInfo']['email'];
    $userName = $orderData['customerInfo']['name'];
    $orderId = $orderData['orderId'];
    $items = $orderData['items'];
    $subtotal = $orderData['subtotal'];
    $paymentMethod = $orderData['paymentMethod'] ?? 'No especificado';
    
    // Traducir métodos de pago
    $paymentMethodLabels = [
        'card' => 'Tarjeta de Crédito/Débito 💳',
        'bizum' => 'Bizum 📱',
        'transfer' => 'Transferencia Bancaria 🏦',
        'paypal' => 'PayPal 🅿️',
        'cash_on_delivery' => 'Contrarreembolso 💵'
    ];
    
    $paymentMethodLabel = $paymentMethodLabels[$paymentMethod] ?? $paymentMethod;
    
    $headers = [
        'From: ' . EMAIL_FROM_NAME . ' <' . EMAIL_FROM . '>',
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8'
    ];
    
    $subject = "🎉 Confirmación de Pedido #$orderId - Canarias Circular";
    
    // Generar lista de productos
    $itemsHtml = '';
    foreach ($items as $item) {
        $itemTotal = $item['price'] * $item['quantity'];
        $itemsHtml .= "<tr>
            <td style='padding: 12px; border-bottom: 1px solid #e5e7eb;'>{$item['quantity']}x {$item['name']}</td>
            <td style='padding: 12px; border-bottom: 1px solid #e5e7eb; text-align: right;'>" . number_format($item['price'], 2) . "€</td>
            <td style='padding: 12px; border-bottom: 1px solid #e5e7eb; text-align: right; font-weight: bold;'>" . number_format($itemTotal, 2) . "€</td>
        </tr>";
    }
    
    $htmlContent = "<!DOCTYPE html><html><head><meta charset='UTF-8'></head>
    <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f3f4f6; padding: 20px;'>
        <div style='max-width: 600px; margin: 0 auto; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.1);'>
            <!-- Header -->
            <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px 20px; text-align: center;'>
                <h1 style='margin: 0; font-size: 28px;'>🏝️ Canarias Circular</h1>
                <p style='margin: 5px 0 0 0; opacity: 0.9;'>Economía Circular en las Islas Canarias</p>
            </div>
            
            <!-- Contenido Principal -->
            <div style='padding: 30px 20px;'>
                <h2 style='color: #667eea; margin-top: 0;'>¡Gracias por tu pedido, $userName! 🎉</h2>
                <p style='font-size: 16px; color: #4b5563;'>Tu pedido ha sido recibido y está siendo procesado.</p>
                
                <!-- Información del Pedido -->
                <div style='background: #f9fafb; border-left: 4px solid #667eea; padding: 15px; margin: 20px 0; border-radius: 4px;'>
                    <p style='margin: 0 0 10px 0;'><strong>📦 Número de Pedido:</strong> $orderId</p>
                    <p style='margin: 0 0 10px 0;'><strong>💳 Método de Pago:</strong> $paymentMethodLabel</p>
                    <p style='margin: 0;'><strong>📅 Fecha:</strong> " . date('d/m/Y H:i') . "</p>
                </div>
                
                <!-- Detalles del Pedido -->
                <h3 style='color: #374151; margin-top: 30px;'>📋 Detalles del Pedido</h3>
                <table style='width: 100%; border-collapse: collapse; margin: 15px 0;'>
                    <thead>
                        <tr style='background: #f3f4f6;'>
                            <th style='padding: 12px; text-align: left; border-bottom: 2px solid #d1d5db;'>Producto</th>
                            <th style='padding: 12px; text-align: right; border-bottom: 2px solid #d1d5db;'>Precio</th>
                            <th style='padding: 12px; text-align: right; border-bottom: 2px solid #d1d5db;'>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        $itemsHtml
                        <tr style='background: #f9fafb;'>
                            <td colspan='2' style='padding: 15px; font-weight: bold; font-size: 18px; border-top: 2px solid #667eea;'>Total:</td>
                            <td style='padding: 15px; text-align: right; font-weight: bold; font-size: 18px; color: #667eea; border-top: 2px solid #667eea;'>" . number_format($subtotal, 2) . "€</td>
                        </tr>
                    </tbody>
                </table>
                
                <!-- Próximos Pasos -->
                <div style='background: linear-gradient(135deg, #dbeafe 0%, #e0f2fe 100%); border-left: 4px solid #3b82f6; padding: 15px; margin: 20px 0; border-radius: 4px;'>
                    <h4 style='margin: 0 0 10px 0; color: #1e40af;'>📬 Próximos Pasos</h4>
                    <ul style='margin: 0; padding-left: 20px; color: #1e40af;'>
                        <li>Recibirás un email cuando tu pedido sea enviado</li>
                        <li>Puedes seguir el estado de tu pedido en tu cuenta</li>
                        <li>Si tienes dudas, contáctanos</li>
                    </ul>
                </div>
                
                <!-- Botón Ver Pedido -->
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='" . SITE_URL . "/orders' style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 25px; font-weight: bold; display: inline-block;'>
                        📦 Ver Mi Pedido
                    </a>
                </div>
                
                <!-- Footer Info -->
                <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb;'>
                    <p style='font-size: 14px; color: #6b7280; text-align: center; margin: 5px 0;'>
                        💚 Gracias por apoyar la economía circular en Canarias
                    </p>
                    <p style='font-size: 12px; color: #9ca3af; text-align: center; margin: 5px 0;'>
                        Este es un email automático, por favor no respondas a este mensaje.
                    </p>
                </div>
            </div>
        </div>
    </body></html>";
    
    // Intentar enviar email
    $emailSent = false;
    
    try {
        if (function_exists('mail')) {
            $emailSent = @mail($userEmail, $subject, $htmlContent, implode("\r\n", $headers));
            
            if ($emailSent) {
                logMessage('INFO', "Email de confirmación de pedido enviado exitosamente a {$userEmail} - Pedido: {$orderId}");
            } else {
                logMessage('WARNING', "La función mail() retornó false para {$userEmail} - Pedido: {$orderId}");
            }
        } else {
            logMessage('ERROR', "La función mail() no está disponible");
        }
    } catch (Exception $e) {
        logMessage('ERROR', "Excepción al enviar email de confirmación de pedido: " . $e->getMessage());
    }
    
    // Si es desarrollo local y el email no se pudo enviar, guardar en logs
    if (!$emailSent && (strpos(SITE_URL, 'localhost') !== false || strpos(SITE_URL, '127.0.0.1') !== false)) {
        $logMessage = "DESARROLLO - Email de pedido no enviado para {$userEmail}. Pedido: {$orderId}";
        logMessage('INFO', $logMessage);
        
        // Guardar en archivo temporal
        $tempFile = __DIR__ . '/../temp_order_emails.txt';
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($tempFile, "[$timestamp] Usuario: $userEmail | Pedido: $orderId | Total: {$subtotal}€\n", FILE_APPEND | LOCK_EX);
    }
    
    return $emailSent;
}

// Token de confirmación
function generateEmailConfirmationToken() {
    return bin2hex(random_bytes(32));
}

// Configuración PHP
ini_set('display_errors', DEBUG_MODE ? 1 : 0);
error_reporting(DEBUG_MODE ? E_ALL : E_ERROR | E_WARNING);
date_default_timezone_set('Atlantic/Canary');

// ====================================
// CONFIGURACIÓN FLEXIBLE - NUEVA FUNCIONALIDAD
// ====================================

// Cargar sistema de configuración flexible
require_once __DIR__ . '/config/api-config.php';

// Función para obtener configuración activa (para compatibilidad)
function getActiveApiConfig() {
    return ApiConfig::getConfig();
}

// Función para verificar si un campo es requerido en la configuración activa
function isFieldRequired($fieldName) {
    return ApiConfig::isFieldRequired($fieldName);
}

// Función para validar datos según configuración activa
function validateDataWithConfig($data) {
    return ApiConfig::validateData($data);
}

// Función para filtrar datos según configuración activa  
function filterDataWithConfig($data) {
    return ApiConfig::filterData($data);
}

// Función para crear respuesta de usuario según configuración activa
function createUserResponseWithConfig($userData) {
    return ApiConfig::createUserResponse($userData);
}

// Información de la API flexible
define('API_FLEXIBLE_VERSION', '1.0.0');
define('API_FLEXIBLE_ENABLED', true);

// Log de inicialización
if (defined('DEBUG_MODE') && DEBUG_MODE) {
    $activeProfile = ApiConfig::getActiveProfile();
    logMessage('INFO', "API Flexible inicializada - Perfil activo: {$activeProfile}");
}

?>