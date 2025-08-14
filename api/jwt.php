<?php
/**
 * JWT Avanzado - Compatible con RFC 7519 y ASP.NET Identity
 * Implementa todas las funcionalidades enterprise de JWT
 */

class JWTAdvanced {
    private $secret_key;
    private $algorithm = 'HS256';
    private $issuer = 'economia-circular-canarias';
    private $audience = 'ecc-web-app';
    
    public function __construct() {
        $this->secret_key = JWT_SECRET;
    }
    
    /**
     * Generar token JWT con claims RFC 7519 completos
     */
    public function generateToken($user, $rememberMe = false) {
        $now = time();
        $expiration = $rememberMe ? (86400 * 30) : 86400; // 30 días vs 1 día
        
        // Claims estándar RFC 7519 (compatibles con ASP.NET)
        $payload = [
            // Claims estándar JWT
            'iss' => $this->issuer,                 // Issuer
            'aud' => $this->audience,               // Audience
            'iat' => $now,                          // Issued At
            'exp' => $now + $expiration,            // Expiration Time
            'nbf' => $now,                          // Not Before
            'jti' => uniqid('ecc_', true),          // JWT ID único
            
            // Subject (User ID)
            'sub' => is_object($user) ? (string)$user->id : (string)$user['id'],
            
            // Claims personalizados (compatibles con ASP.NET Identity)
            'email' => is_object($user) ? $user->email : $user['email'],
            'given_name' => is_object($user) ? ($user->firstName ?? $user->first_name) : $user['first_name'],
            'family_name' => is_object($user) ? ($user->lastName ?? $user->last_name) : $user['last_name'],
            'role' => is_object($user) ? ($user->userType ?? $user->user_type) : $user['user_type'],
            'island' => is_object($user) ? $user->island : $user['island'],
            'city' => is_object($user) ? $user->city : ($user['city'] ?? ''),
            'email_verified' => is_object($user) ? (bool)$user->emailVerified : (bool)$user['email_verified'],
            
            // Metadata adicional
            'remember_me' => $rememberMe,
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 200),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
        ];
        
        return $this->encode($payload);
    }
    
    /**
     * Codificar JWT
     */
    private function encode($payload) {
        // Header del JWT
        $header = json_encode([
            'typ' => 'JWT',
            'alg' => $this->algorithm
        ]);
        
        // Payload del JWT
        $payloadJson = json_encode($payload);
        
        // Codificar header y payload en base64url
        $headerEncoded = $this->base64UrlEncode($header);
        $payloadEncoded = $this->base64UrlEncode($payloadJson);
        
        // Crear la signature
        $signature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, $this->secret_key, true);
        $signatureEncoded = $this->base64UrlEncode($signature);
        
        // Retornar el token JWT completo
        return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
    }
    
    /**
     * Validar y decodificar un token JWT
     */
    public function validateToken($jwt) {
        try {
            $parts = explode('.', $jwt);
            
            if (count($parts) !== 3) {
                return false;
            }
            
            list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;
            
            // Decodificar header y payload
            $header = json_decode($this->base64UrlDecode($headerEncoded), true);
            $payload = json_decode($this->base64UrlDecode($payloadEncoded), true);
            
            if (!$header || !$payload) {
                return false;
            }
            
            // Verificar el algoritmo
            if ($header['alg'] !== $this->algorithm) {
                return false;
            }
            
            // Verificar la signature
            $expectedSignature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, $this->secret_key, true);
            $expectedSignatureEncoded = $this->base64UrlEncode($expectedSignature);
            
            if (!hash_equals($signatureEncoded, $expectedSignatureEncoded)) {
                return false;
            }
            
            // Verificar claims estándar
            $now = time();
            
            // Verificar expiración
            if (isset($payload['exp']) && $payload['exp'] < $now) {
                return false;
            }
            
            // Verificar not before
            if (isset($payload['nbf']) && $payload['nbf'] > $now) {
                return false;
            }
            
            // Verificar issuer
            if (isset($payload['iss']) && $payload['iss'] !== $this->issuer) {
                return false;
            }
            
            // Verificar audience
            if (isset($payload['aud']) && $payload['aud'] !== $this->audience) {
                return false;
            }
            
            return $payload;
            
        } catch (Exception $e) {
            error_log("JWT Validation Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Establecer cookie JWT con configuración avanzada
     */
    public function setCookie($token, $name = null, $expiration = null) {
        if ($name === null) {
            $name = COOKIE_NAME;
        }
        
        if ($expiration === null) {
            $expiration = COOKIE_EXPIRATION;
        }
        
        // Detectar si estamos en HTTPS
        $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        
        // Detectar si estamos en Ngrok para ajustar configuración
        $isNgrok = isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'ngrok') !== false;
        
        // Configuración de cookie avanzada
        $cookieOptions = [
            'expires' => time() + $expiration,
            'path' => '/',
            'domain' => '', // Vacío para permitir subdominios
            'secure' => $isSecure, // True en HTTPS
            'httponly' => true, // Previene acceso desde JavaScript
            'samesite' => $isNgrok ? 'None' : 'Lax' // None para Ngrok, Lax para local
        ];
        
        // Para Ngrok, usar header personalizado que incluye Partitioned
        if ($isNgrok && $isSecure) {
            $cookieString = "{$name}={$token}; expires=" . gmdate('D, d M Y H:i:s T', time() + $expiration) . 
                           "; path=/; secure; httponly; samesite=None; partitioned";
            header("Set-Cookie: {$cookieString}", false);
        } else {
            setcookie($name, $token, $cookieOptions);
        }
        
        // Log cookie establecida
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("JWT Cookie set: name=$name, expires=" . date('Y-m-d H:i:s', time() + $expiration) . ", secure=" . ($isSecure ? 'true' : 'false'));
        }
    }
    
    /**
     * Obtener token desde cookie
     */
    public function getTokenFromCookie($name = null) {
        if ($name === null) {
            $name = COOKIE_NAME;
        }
        
        return $_COOKIE[$name] ?? null;
    }
    
    /**
     * Limpiar cookie JWT (logout)
     */
    public function clearCookie($name = null) {
        if ($name === null) {
            $name = COOKIE_NAME;
        }
        
        setcookie($name, '', [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
    
    /**
     * Codificación Base64 URL-safe
     */
    private function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Decodificación Base64 URL-safe
     */
    private function base64UrlDecode($data) {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
    
    /**
     * Verificar si un token es válido sin decodificar
     */
    public function isValidToken($jwt) {
        return $this->validateToken($jwt) !== false;
    }
    
    /**
     * Obtener información del token sin validar expiración
     */
    public function decodeTokenUnsafe($jwt) {
        try {
            $parts = explode('.', $jwt);
            if (count($parts) !== 3) {
                return false;
            }
            
            $payload = json_decode($this->base64UrlDecode($parts[1]), true);
            return $payload;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Generar un nuevo token basado en uno existente (refresh)
     */
    public function refreshToken($currentToken, $extendExpiration = true) {
        $payload = $this->validateToken($currentToken);
        
        if (!$payload) {
            return false;
        }
        
        // Mantener datos del usuario pero actualizar timestamps
        $now = time();
        $payload['iat'] = $now;
        $payload['jti'] = uniqid('ecc_', true); // Nuevo ID
        
        if ($extendExpiration) {
            $rememberMe = $payload['remember_me'] ?? false;
            $expiration = $rememberMe ? (86400 * 30) : 86400;
            $payload['exp'] = $now + $expiration;
        }
        
        return $this->encode($payload);
    }
}
?>
