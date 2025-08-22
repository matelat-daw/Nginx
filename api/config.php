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
    if (!file_exists($filePath)) return false;
    
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
        
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        
        $_ENV[$key] = $value;
        putenv("$key=$value");
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

// JWT
define('JWT_SECRET', $_ENV['JWT_SECRET'] ?? 'fallback_secret_key_change_in_production');
define('JWT_EXPIRATION', 24 * 60 * 60);

// Cookies
define('COOKIE_NAME', 'ecc_auth_token');
define('COOKIE_EXPIRATION', 24 * 60 * 60);
define('COOKIE_SECURE', false);
define('COOKIE_HTTP_ONLY', true);
define('COOKIE_SAME_SITE', 'Lax');

// Seguridad
define('PASSWORD_MIN_LENGTH', 6);

// Email
define('EMAIL_FROM', 'matelat@gmail.com');
define('EMAIL_FROM_NAME', 'Canarias Circular');
define('SITE_URL', 'http://localhost:8080');

// Headers CORS
function setCorsHeaders() {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $allowedOrigins = ['http://localhost:8080', 'https://localhost:8080', 'http://127.0.0.1:8080'];
    
    header("Access-Control-Allow-Origin: " . (in_array($origin, $allowedOrigins) ? $origin : 'http://localhost:8080'));
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
?>