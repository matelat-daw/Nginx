<?php
/**
 * JWT Simple - Utilidades básicas para JWT
 * Solo contiene las funciones realmente utilizadas
 */

class JWTSimple {
    
    /**
     * Generar token JWT básico (usado en login/register)
     */
    public static function generateToken($userId, $email) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode([
            'user_id' => $userId,
            'email' => $email,
            'exp' => time() + JWT_EXPIRATION
        ]);
        
        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        
        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, JWT_SECRET, true);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        return $base64Header . "." . $base64Payload . "." . $base64Signature;
    }
    
    /**
     * Validar token JWT (usado en validate.php)
     */
    public static function validateToken($jwt) {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) return false;
        
        list($header, $payloadPart, $signature) = $parts;
        
        // Verificar signature
        $expectedSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(
            hash_hmac('sha256', $header . '.' . $payloadPart, JWT_SECRET, true)
        ));
        
        if ($signature !== $expectedSignature) return false;
        
        // Decodificar payload
        $payload = json_decode(base64_decode(str_pad(strtr($payloadPart, '-_', '+/'), strlen($payloadPart) % 4, '=', STR_PAD_RIGHT)), true);
        
        // Verificar expiración
        if (!$payload || !isset($payload['exp']) || $payload['exp'] < time()) return false;
        
        return $payload;
    }
    
    /**
     * Establecer cookie JWT básica
     */
    public static function setCookie($token, $name = 'canarias_auth_token') {
        setcookie($name, $token, [
            'expires' => time() + JWT_EXPIRATION,
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
}
?>
?>
