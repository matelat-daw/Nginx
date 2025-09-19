<?php
/**
 * Configuración API Optimizada - Economía Circular Canarias
 * Solo contiene las funciones y configuraciones que realmente se usan
 */

// Implementación simple de JWT sin dependencias externas
class JWT {
    public static function encode($payload, $key) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode($payload);
        
        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        
        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $key, true);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        return $base64Header . "." . $base64Payload . "." . $base64Signature;
    }
    
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
}

class Key {
    public $key;
    public $algorithm;
    
    public function __construct($key, $algorithm) {
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

// Email
define('EMAIL_FROM', 'matelat@gmail.com');
define('EMAIL_FROM_NAME', 'Canarias Circular');
define('SITE_URL', 'https://localhost');

// Headers CORS
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

// Preflight requests
function handlePreflight() {
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        setCorsHeaders();
        http_response_code(200);
        echo json_encode(['success' => true]);
        exit();
    }
}

// Respuestas JSON
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