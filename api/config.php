<?php
/**
 * Configuración de la API - Economía Circular Canarias
 * Configuración optimizada y limpia con variables de entorno
 */

// Función para cargar variables de entorno desde archivo .env
function loadEnvironmentVariables($filePath = __DIR__ . '/../.env') {
    if (!file_exists($filePath)) {
        error_log("Archivo .env no encontrado en: " . $filePath);
        return false;
    }
    
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Ignorar comentarios
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Procesar variables
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remover comillas si las hay
            if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                $value = substr($value, 1, -1);
            }
            
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
    return true;
}

// Cargar variables de entorno
loadEnvironmentVariables();

// Configuración de entorno
define('ENVIRONMENT', $_ENV['ENVIRONMENT'] ?? 'development');
define('DEBUG_MODE', ENVIRONMENT === 'development');

// Configuración de CORS
define('CORS_ORIGIN', $_ENV['CORS_ORIGIN'] ?? 'http://localhost:8080');
define('CORS_METHODS', 'GET, POST, PUT, DELETE, OPTIONS');
define('CORS_HEADERS', 'Content-Type, Authorization, X-Requested-With, Accept, Origin');
define('CORS_MAX_AGE', 86400); // 24 horas

// Configuración de JWT
define('JWT_SECRET', $_ENV['JWT_SECRET'] ?? 'fallback_secret_key_change_in_production');
define('JWT_EXPIRATION', 24 * 60 * 60); // 24 horas
define('JWT_ISSUER', 'economia-circular-canarias');
define('JWT_AUDIENCE', 'ecc-web-app');

// Configuración de cookies
define('COOKIE_NAME', $_ENV['COOKIE_NAME'] ?? 'canarias_auth_token');
define('COOKIE_EXPIRATION', 24 * 60 * 60); // 24 horas
define('COOKIE_SECURE', isset($_SERVER['HTTPS'])); // Auto-detect HTTPS
define('COOKIE_HTTP_ONLY', true);
define('COOKIE_SAME_SITE', 'Lax');

// Configuración de base de datos
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'users');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('DB_CHARSET', $_ENV['DB_CHARSET'] ?? 'utf8mb4');
define('DB_PORT', (int)($_ENV['DB_PORT'] ?? 3306));
define('DB_TABLE_USERS', $_ENV['DB_TABLE_USERS'] ?? 'ecc_users');

// Configuración de seguridad
define('PASSWORD_MIN_LENGTH', 6);
define('LOGIN_MAX_ATTEMPTS', 5);
define('ACCOUNT_LOCKOUT_TIME', 30 * 60); // 30 minutos

// Configuración de rate limiting
define('RATE_LIMIT_ENABLED', true);
define('RATE_LIMIT_REQUESTS', (int)($_ENV['RATE_LIMIT_REQUESTS'] ?? 100)); // requests por hora
define('RATE_LIMIT_WINDOW', (int)($_ENV['RATE_LIMIT_WINDOW'] ?? 3600)); // 1 hora

// Función helper para headers CORS
function setCorsHeaders() {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    
    // Verificar orígenes permitidos
    $allowedOrigins = [
        'http://localhost:8080',
        'https://localhost:8080',
        'http://127.0.0.1:8080'
    ];
    
    if (in_array($origin, $allowedOrigins)) {
        header("Access-Control-Allow-Origin: " . $origin);
        header("Access-Control-Allow-Credentials: true");
    } else {
        header("Access-Control-Allow-Origin: " . CORS_ORIGIN);
        header("Access-Control-Allow-Credentials: true");
    }
    
    header("Access-Control-Allow-Methods: " . CORS_METHODS);
    header("Access-Control-Allow-Headers: " . CORS_HEADERS);
    header("Access-Control-Max-Age: " . CORS_MAX_AGE);
    header("Content-Type: application/json; charset=utf-8");
}

// Función helper para manejar preflight requests
function handlePreflight() {
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        setCorsHeaders();
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'CORS preflight handled']);
        exit();
    }
}

// Función helper para respuestas JSON estandarizadas
function jsonResponse($data, $statusCode = 200, $message = null) {
    http_response_code($statusCode);
    
    $response = [];
    $response['success'] = $statusCode >= 200 && $statusCode < 300;
    
    if ($message) {
        $response['message'] = $message;
    }
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    $response['timestamp'] = date('Y-m-d H:i:s');
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();
}

// Función helper para logging
function logMessage($level, $message, $context = []) {
    if (!DEBUG_MODE && $level === 'DEBUG') {
        return;
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
    $logEntry = "[$timestamp] [$level] $message$contextStr";
    
    error_log($logEntry);
}

// Función helper para rate limiting básico
function checkRateLimit($identifier = null) {
    if (!RATE_LIMIT_ENABLED) {
        return true;
    }
    
    $identifier = $identifier ?: ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $cacheFile = sys_get_temp_dir() . '/rate_limit_' . md5($identifier) . '.tmp';
    
    $now = time();
    $requests = [];
    
    // Leer requests anteriores
    if (file_exists($cacheFile)) {
        $data = file_get_contents($cacheFile);
        $requests = json_decode($data, true) ?: [];
    }
    
    // Filtrar requests dentro de la ventana de tiempo
    $requests = array_filter($requests, function($timestamp) use ($now) {
        return ($now - $timestamp) < RATE_LIMIT_WINDOW;
    });
    
    // Verificar límite
    if (count($requests) >= RATE_LIMIT_REQUESTS) {
        return false;
    }
    
    // Agregar request actual
    $requests[] = $now;
    
    // Guardar requests actualizadas
    file_put_contents($cacheFile, json_encode($requests), LOCK_EX);
    
    return true;
}

// Configuración de email
define('EMAIL_FROM', 'matelat@gmail.com');
define('EMAIL_FROM_NAME', 'Canarias Circular');
define('EMAIL_REPLY_TO', 'matelat@gmail.com');
define('EMAIL_HOST', 'localhost'); // Sendmail configurado
define('SITE_URL', 'http://localhost:8080');

/**
 * Enviar email de confirmación de registro
 */
function sendWelcomeEmail($userEmail, $userName, $userId, $confirmationToken) {
    try {
        $siteName = 'Canarias Circular';
        $confirmationUrl = SITE_URL . "/confirmar-email?token=" . urlencode($confirmationToken) . "&id=" . $userId;
        
        // Encabezados del email
        $headers = [
            'From: ' . EMAIL_FROM_NAME . ' <' . EMAIL_FROM . '>',
            'Reply-To: ' . EMAIL_REPLY_TO,
            'X-Mailer: PHP/' . phpversion(),
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8'
        ];
        
        $subject = "🏝️ ¡Bienvenido/a a Canarias Circular, $userName!";
        
        // Contenido HTML del email
        $htmlContent = "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Bienvenido a Canarias Circular</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f5f5f5;'>
            <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.1);'>
                
                <!-- Header -->
                <div style='background: linear-gradient(135deg, #1e3a8a, #3b82f6); color: white; padding: 30px 20px; text-align: center;'>
                    <h1 style='margin: 0; font-size: 28px;'>🏝️ Canarias Circular</h1>
                    <p style='margin: 5px 0 0 0; font-size: 16px; opacity: 0.9;'>Economía Circular en las Islas Canarias</p>
                </div>
                
                <!-- Content -->
                <div style='padding: 30px 20px;'>
                    <h2 style='color: #1e3a8a; margin-bottom: 20px;'>¡Bienvenido/a, $userName! 🎉</h2>
                    
                    <p>¡Nos alegra muchísimo tenerte como parte de nuestra comunidad de economía circular en Canarias!</p>
                    
                    <p>Tu cuenta ha sido creada exitosamente, pero necesitamos que confirmes tu dirección de email para activar todas las funcionalidades.</p>
                    
                    <!-- Call to Action Button -->
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='$confirmationUrl' style='background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 15px 30px; text-decoration: none; border-radius: 25px; font-weight: bold; font-size: 16px; display: inline-block; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);'>
                            ✅ Confirmar mi Email
                        </a>
                    </div>
                    
                    <div style='background-color: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #fbbf24; margin: 20px 0;'>
                        <h3 style='color: #92400e; margin-top: 0;'>📧 ¿Qué sigue?</h3>
                        <ol style='color: #451a03; margin: 0; padding-left: 20px;'>
                            <li>Haz clic en el botón \"Confirmar mi Email\"</li>
                            <li>Serás redirigido a nuestro sitio web</li>
                            <li>¡Tu cuenta estará completamente activada!</li>
                        </ol>
                    </div>
                    
                    <h3 style='color: #1e3a8a;'>🌍 Sobre Canarias Circular</h3>
                    <p>Somos una plataforma dedicada a fomentar la economía circular en las Islas Canarias, conectando productores locales con consumidores conscientes para crear un futuro más sostenible.</p>
                    
                    <p><strong>Nuestros valores:</strong></p>
                    <ul>
                        <li>🌱 <strong>Sostenibilidad:</strong> Productos y servicios respetuosos con el medio ambiente</li>
                        <li>🏝️ <strong>Local:</strong> Apoyamos a los productores y empresas canarias</li>
                        <li>♻️ <strong>Circular:</strong> Reducir, reutilizar y reciclar</li>
                        <li>🤝 <strong>Comunidad:</strong> Juntos construimos un futuro mejor</li>
                    </ul>
                    
                    <div style='background-color: #e0f2fe; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                        <p style='margin: 0; font-style: italic; color: #0277bd;'>
                            \"Si compras aquí, vuelve a ti\" - Apoya la economía local canaria 🏝️💚
                        </p>
                    </div>
                </div>
                
                <!-- Footer -->
                <div style='background-color: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #e9ecef;'>
                    <p style='margin: 0; font-size: 12px; color: #6c757d;'>
                        Si no puedes hacer clic en el botón, copia y pega este enlace en tu navegador:<br>
                        <a href='$confirmationUrl' style='color: #1e3a8a; word-break: break-all;'>$confirmationUrl</a>
                    </p>
                    
                    <p style='margin: 15px 0 0 0; font-size: 12px; color: #6c757d;'>
                        Este email fue enviado a <strong>$userEmail</strong><br>
                        Si no creaste esta cuenta, puedes ignorar este mensaje.
                    </p>
                    
                    <p style='margin: 15px 0 0 0; font-size: 12px; color: #6c757d;'>
                        © 2025 Canarias Circular | Islas Canarias, España
                    </p>
                </div>
            </div>
        </body>
        </html>";
        
        // Enviar email
        $success = mail(
            $userEmail,
            $subject,
            $htmlContent,
            implode("\r\n", $headers)
        );
        
        if ($success) {
            logMessage('INFO', "Welcome email sent successfully to: $userEmail");
            return true;
        } else {
            logMessage('ERROR', "Failed to send welcome email to: $userEmail");
            return false;
        }
        
    } catch (Exception $e) {
        logMessage('ERROR', "Error sending welcome email: " . $e->getMessage());
        return false;
    }
}

/**
 * Generar token de confirmación de email
 */
function generateEmailConfirmationToken() {
    return bin2hex(random_bytes(32));
}

// Auto-configuración de PHP
ini_set('display_errors', DEBUG_MODE ? 1 : 0);
error_reporting(DEBUG_MODE ? E_ALL : E_ERROR | E_WARNING);

// Auto-configuración de zona horaria
date_default_timezone_set('Atlantic/Canary');

// Inicialización de directorio temporal
$tempDir = sys_get_temp_dir() . '/ecc_api';
if (!is_dir($tempDir)) {
    mkdir($tempDir, 0755, true);
}
define('TEMP_DIR', $tempDir);
?>
