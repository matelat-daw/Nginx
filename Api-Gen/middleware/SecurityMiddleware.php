<?php
/**
 * SecurityMiddleware Simple - Funciones básicas de seguridad
 * Solo headers esenciales y sanitización básica
 */

class SecuritySimple {
    
    /**
     * Establecer headers de seguridad básicos
     */
    public static function setSecurityHeaders() {
        header('X-XSS-Protection: 1; mode=block');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // CSP básico
        $csp = "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';";
        header("Content-Security-Policy: {$csp}");
    }
    
    /**
     * Sanitizar input básico
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
            case 'int':
                return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
            case 'string':
            default:
                return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
        }
    }
    
    /**
     * Generar token CSRF simple
     */
    public static function generateCSRFToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        return $token;
    }
    
    /**
     * Validar token CSRF
     */
    public static function validateCSRFToken($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

/**
 * Función helper para headers de seguridad
 */
function setSecurityHeaders() {
    SecuritySimple::setSecurityHeaders();
}

/**
 * Función helper para sanitizar input
 */
function sanitizeInput($input, $type = 'string') {
    return SecuritySimple::sanitizeInput($input, $type);
}
?>
