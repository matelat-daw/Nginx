<?php
/**
 * AuthService Optimizado - Servicio de autenticación simplificado
 * Solo contiene las funciones que realmente se utilizan
 */

class AuthService {
    
    /**
     * Verificar contraseña con el hash del usuario
     * Soporta tanto password_verify PHP como comparación directa para passwords legacy
     */
    public static function verifyPassword($password, $hashedPassword) {
        if (empty($hashedPassword)) return false;
        
        // Intentar con password_verify (formato estándar PHP)
        if (password_verify($password, $hashedPassword)) return true;
        
        // Fallback: comparación directa para passwords legacy
        return $password === $hashedPassword;
    }
    
    /**
     * Generar hash de contraseña usando estándar PHP
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    /**
     * Validar credenciales básicas de login
     */
    public static function validateCredentials($email, $password) {
        return !empty($email) && 
               !empty($password) && 
               filter_var($email, FILTER_VALIDATE_EMAIL) && 
               strlen($password) >= 6;
    }
}
?>