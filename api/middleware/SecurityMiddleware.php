<?php
/**
 * SecurityMiddleware - Economía Circular Canarias
 * 
 * Middleware de seguridad enterprise que incluye:
 * - Headers de seguridad HTTP
 * - Protección XSS/CSRF
 * - Validación de entrada
 * - Detección de ataques
 * - Logging de seguridad
 */

class SecurityMiddleware {
    private static $instance = null;
    private $securityConfig;
    private $suspiciousPatterns;
    
    private function __construct() {
        $this->securityConfig = [
            'max_request_size' => 10 * 1024 * 1024, // 10MB
            'max_input_vars' => 1000,
            'blocked_extensions' => ['php', 'js', 'html', 'exe', 'bat', 'cmd'],
            'max_filename_length' => 255,
            'csrf_token_lifetime' => 3600, // 1 hora
        ];
        
        $this->suspiciousPatterns = [
            'sql_injection' => [
                '/(\bor\b|\band\b)\s+\d+\s*=\s*\d+/i',
                '/union\s+select/i',
                '/drop\s+table/i',
                '/insert\s+into/i',
                '/delete\s+from/i',
                '/update\s+set/i',
                '/exec\s*\(/i',
                '/script\s*>/i'
            ],
            'xss' => [
                '/<script[^>]*>.*?<\/script>/i',
                '/javascript:/i',
                '/on\w+\s*=/i',
                '/<iframe[^>]*>/i',
                '/<object[^>]*>/i',
                '/<embed[^>]*>/i'
            ],
            'path_traversal' => [
                '/\.\.\//i',
                '/\.\.\\/i',
                '/\.\.%2f/i',
                '/\.\.%5c/i'
            ],
            'command_injection' => [
                '/;\s*(rm|del|cat|type|echo|wget|curl)/i',
                '/\|\s*(nc|netcat|telnet)/i',
                '/`[^`]*`/i',
                '/\$\([^)]*\)/i'
            ]
        ];
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Aplicar todas las medidas de seguridad
     */
    public function applySecurity() {
        $this->setSecurityHeaders();
        $this->validateRequest();
        $this->detectAttacks();
        $this->validateFileUploads();
    }
    
    /**
     * Establecer headers de seguridad HTTP
     */
    public function setSecurityHeaders() {
        // Prevenir XSS
        header('X-XSS-Protection: 1; mode=block');
        
        // Prevenir MIME sniffing
        header('X-Content-Type-Options: nosniff');
        
        // Prevenir clickjacking
        header('X-Frame-Options: SAMEORIGIN');
        
        // Referrer policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Content Security Policy básico
        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net; " .
               "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net; " .
               "font-src 'self' https://fonts.gstatic.com; " .
               "img-src 'self' data: https:; " .
               "connect-src 'self' http://localhost:8080;";
        
        header("Content-Security-Policy: {$csp}");
        
        // Strict Transport Security (solo para HTTPS)
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
        
        // Permissions Policy
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    }
    
    /**
     * Validar request básico
     */
    public function validateRequest() {
        // Verificar tamaño de request
        $contentLength = $_SERVER['CONTENT_LENGTH'] ?? 0;
        if ($contentLength > $this->securityConfig['max_request_size']) {
            $this->blockRequest('REQUEST_TOO_LARGE', 'Request size exceeds limit');
        }
        
        // Verificar número de variables
        if (count($_REQUEST) > $this->securityConfig['max_input_vars']) {
            $this->blockRequest('TOO_MANY_VARS', 'Too many input variables');
        }
        
        // Verificar User-Agent básico
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (empty($userAgent) || strlen($userAgent) < 10) {
            logMessage('WARNING', 'Suspicious request without proper User-Agent: ' . $userAgent);
        }
        
        // Verificar métodos HTTP permitidos
        $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'];
        if (!in_array($_SERVER['REQUEST_METHOD'], $allowedMethods)) {
            $this->blockRequest('INVALID_METHOD', 'HTTP method not allowed');
        }
    }
    
    /**
     * Detectar ataques en input
     */
    public function detectAttacks() {
        $allInput = array_merge($_GET, $_POST, $_COOKIE);
        
        foreach ($allInput as $key => $value) {
            if (is_string($value)) {
                $this->scanForAttacks($key, $value);
            } elseif (is_array($value)) {
                $this->scanArrayForAttacks($key, $value);
            }
        }
        
        // Verificar headers sospechosos
        $this->scanHeaders();
    }
    
    /**
     * Escanear valor por patrones de ataque
     */
    private function scanForAttacks($key, $value) {
        foreach ($this->suspiciousPatterns as $attackType => $patterns) {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $value)) {
                    $this->logSecurityIncident($attackType, $key, $value, $pattern);
                    
                    // Bloquear ataques críticos
                    if (in_array($attackType, ['sql_injection', 'command_injection'])) {
                        $this->blockRequest($attackType, "Potential {$attackType} detected");
                    }
                }
            }
        }
    }
    
    /**
     * Escanear arrays recursivamente
     */
    private function scanArrayForAttacks($key, $array, $depth = 0) {
        if ($depth > 10) return; // Prevenir recursión infinita
        
        foreach ($array as $subKey => $value) {
            if (is_string($value)) {
                $this->scanForAttacks("{$key}[{$subKey}]", $value);
            } elseif (is_array($value)) {
                $this->scanArrayForAttacks("{$key}[{$subKey}]", $value, $depth + 1);
            }
        }
    }
    
    /**
     * Escanear headers HTTP
     */
    private function scanHeaders() {
        $suspiciousHeaders = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'HTTP_USER_AGENT',
            'HTTP_REFERER'
        ];
        
        foreach ($suspiciousHeaders as $header) {
            if (isset($_SERVER[$header])) {
                $value = $_SERVER[$header];
                
                // Detectar headers con contenido sospechoso
                if (strpos($value, '<script') !== false || 
                    strpos($value, 'javascript:') !== false ||
                    preg_match('/[<>"\']/', $value)) {
                    
                    logMessage('WARNING', "Suspicious header {$header}: {$value}");
                }
            }
        }
    }
    
    /**
     * Validar uploads de archivos
     */
    public function validateFileUploads() {
        if (!empty($_FILES)) {
            foreach ($_FILES as $fieldName => $file) {
                if ($file['error'] === UPLOAD_ERR_OK) {
                    $this->validateFile($fieldName, $file);
                }
            }
        }
    }
    
    /**
     * Validar archivo individual
     */
    private function validateFile($fieldName, $file) {
        $filename = $file['name'];
        $tmpName = $file['tmp_name'];
        $size = $file['size'];
        
        // Verificar tamaño
        if ($size > $this->securityConfig['max_request_size']) {
            $this->blockRequest('FILE_TOO_LARGE', 'Uploaded file too large');
        }
        
        // Verificar longitud del nombre
        if (strlen($filename) > $this->securityConfig['max_filename_length']) {
            $this->blockRequest('FILENAME_TOO_LONG', 'Filename too long');
        }
        
        // Verificar extensión
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($extension, $this->securityConfig['blocked_extensions'])) {
            $this->blockRequest('BLOCKED_EXTENSION', "File extension '{$extension}' not allowed");
        }
        
        // Verificar content type vs extensión
        $this->validateMimeType($file);
        
        // Escanear contenido del archivo
        $this->scanFileContent($tmpName, $fieldName);
    }
    
    /**
     * Validar MIME type
     */
    private function validateMimeType($file) {
        $allowedMimes = [
            'image/jpeg' => ['jpg', 'jpeg'],
            'image/png' => ['png'],
            'image/gif' => ['gif'],
            'image/webp' => ['webp'],
            'application/pdf' => ['pdf'],
            'text/plain' => ['txt'],
            'application/json' => ['json'],
            'text/csv' => ['csv']
        ];
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        $validMime = false;
        foreach ($allowedMimes as $mime => $extensions) {
            if ($detectedType === $mime && in_array($extension, $extensions)) {
                $validMime = true;
                break;
            }
        }
        
        if (!$validMime) {
            logMessage('WARNING', "MIME type mismatch: {$detectedType} for extension {$extension}");
        }
    }
    
    /**
     * Escanear contenido de archivo
     */
    private function scanFileContent($tmpName, $fieldName) {
        $content = file_get_contents($tmpName, false, null, 0, 8192); // Primeros 8KB
        
        // Buscar patrones sospechosos en el contenido
        $suspiciousPatterns = [
            '/<\?php/i',
            '/<script/i',
            '/eval\s*\(/i',
            '/exec\s*\(/i',
            '/shell_exec/i',
            '/system\s*\(/i'
        ];
        
        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                logMessage('CRITICAL', "Malicious content detected in uploaded file {$fieldName}");
                $this->blockRequest('MALICIOUS_FILE', 'Malicious content detected in file');
            }
        }
    }
    
    /**
     * Generar token CSRF
     */
    public function generateCSRFToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_token_time'] = time();
        
        return $token;
    }
    
    /**
     * Validar token CSRF
     */
    public function validateCSRFToken($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
            return false;
        }
        
        // Verificar expiración
        if (time() - $_SESSION['csrf_token_time'] > $this->securityConfig['csrf_token_lifetime']) {
            unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
            return false;
        }
        
        // Verificar token
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Log de incidente de seguridad
     */
    private function logSecurityIncident($attackType, $key, $value, $pattern) {
        $incident = [
            'type' => $attackType,
            'key' => $key,
            'value' => substr($value, 0, 100), // Limitar longitud
            'pattern' => $pattern,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'timestamp' => date('Y-m-d H:i:s'),
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
        ];
        
        logMessage('SECURITY', 'Security incident: ' . json_encode($incident));
    }
    
    /**
     * Bloquear request malicioso
     */
    private function blockRequest($reason, $message) {
        $blockInfo = [
            'reason' => $reason,
            'message' => $message,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'timestamp' => date('Y-m-d H:i:s'),
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
        ];
        
        logMessage('CRITICAL', 'Request blocked: ' . json_encode($blockInfo));
        
        http_response_code(403);
        header('Content-Type: application/json');
        
        echo json_encode([
            'success' => false,
            'error' => 'SECURITY_VIOLATION',
            'message' => 'Request blocked by security filter',
            'code' => $reason
        ]);
        
        exit;
    }
    
    /**
     * Sanitizar input general
     */
    public static function sanitizeInput($input, $type = 'string') {
        if (is_array($input)) {
            return array_map(function($item) use ($type) {
                return self::sanitizeInput($item, $type);
            }, $input);
        }
        
        switch ($type) {
            case 'email':
                return filter_var($input, FILTER_SANITIZE_EMAIL);
            
            case 'url':
                return filter_var($input, FILTER_SANITIZE_URL);
            
            case 'int':
                return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
            
            case 'float':
                return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            
            case 'string':
            default:
                // Remover tags HTML y caracteres especiales
                $input = strip_tags($input);
                $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
                return trim($input);
        }
    }
    
    /**
     * Verificar si IP está bloqueada
     */
    public function isIPBlocked($ip = null) {
        if (!$ip) {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        }
        
        // Lista simple de IPs bloqueadas (en producción usar base de datos)
        $blockedIPs = [
            '127.0.0.1' => false, // localhost nunca bloqueado en desarrollo
            // Añadir IPs maliciosas conocidas
        ];
        
        return isset($blockedIPs[$ip]) && $blockedIPs[$ip];
    }
}

/**
 * Función helper para aplicar seguridad
 */
function applySecurity() {
    $security = SecurityMiddleware::getInstance();
    $security->applySecurity();
}

/**
 * Función helper para sanitizar input
 */
function sanitizeInput($input, $type = 'string') {
    return SecurityMiddleware::sanitizeInput($input, $type);
}
?>
