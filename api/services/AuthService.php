<?php
/**
 * AuthService - Servicio de autenticación compatible con ASP.NET Identity
 * Implementa PBKDF2 + SHA512 con 10,000 iteraciones
 */

class AuthService {
    
    /**
     * Verificar contraseña con el hash del usuario
     * Compatible con ASP.NET Identity y PHP password_hash
     */
    public static function verifyPassword($password, $hashedPassword) {
        // Si el password hash está vacío, no permitir login
        if (empty($hashedPassword)) {
            return false;
        }
        
        // Detectar formato del hash
        if (self::isAspNetIdentityHash($hashedPassword)) {
            // Verificar con formato ASP.NET Identity
            return self::verifyAspNetIdentityPassword($password, $hashedPassword);
        } else {
            // Verificar con formato PHP estándar
            return password_verify($password, $hashedPassword);
        }
    }
    
    /**
     * Detectar si el hash es formato ASP.NET Identity
     */
    private static function isAspNetIdentityHash($hash) {
        // Los hashes de ASP.NET Identity son Base64 y empiezan con 'A' (0x01 en Base64)
        return strlen($hash) > 60 && preg_match('/^[A-Za-z0-9+\/]+=*$/', $hash);
    }
    
    /**
     * Verificar contraseña con formato ASP.NET Identity
     */
    private static function verifyAspNetIdentityPassword($password, $hashedPassword) {
        try {
            // El hash de ASP.NET Identity está en Base64
            $hashBytes = base64_decode($hashedPassword);
            
            if ($hashBytes === false || strlen($hashBytes) < 61) {
                return false;
            }
            
            // Estructura del hash ASP.NET Identity v3:
            // Byte 0: Format marker (0x01)
            // Bytes 1-4: PRF (Pseudo-random function) - 2 = HMACSHA512
            // Bytes 5-8: Iteration count (10,000)
            // Bytes 9-12: Salt length (16)
            // Bytes 13-28: Salt (16 bytes)
            // Bytes 29-60: Subkey (32 bytes)
            
            $format = ord($hashBytes[0]);
            if ($format !== 0x01) {
                return false; // Solo soportamos formato 0x01
            }
            
            $prf = unpack('N', substr($hashBytes, 1, 4))[1];
            $iterations = unpack('N', substr($hashBytes, 5, 4))[1];
            $saltLen = unpack('N', substr($hashBytes, 9, 4))[1];
            
            // Validar que sea el formato esperado de ASP.NET Identity
            if ($prf !== 2 || $saltLen !== 16) {
                return false;
            }
            
            // Extraer salt y subkey
            $salt = substr($hashBytes, 13, 16);
            $expectedSubkey = substr($hashBytes, 29, 32);
            
            // Generar hash de la contraseña proporcionada usando PBKDF2 con SHA512
            $actualSubkey = hash_pbkdf2('sha512', $password, $salt, $iterations, 32, true);
            
            // Comparar subkeys de forma segura
            return hash_equals($expectedSubkey, $actualSubkey);
            
        } catch (Exception $e) {
            error_log("Error verificando contraseña ASP.NET Identity: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generar hash de contraseña compatible con ASP.NET Identity
     */
    public static function hashPassword($password) {
        // Configuración ASP.NET Identity v3
        $prf = 2;           // 2 = HMACSHA512
        $iterCount = 10000; // 10,000 iteraciones (estándar ASP.NET)
        $saltLen = 16;      // 16 bytes de salt
        $subKeyLen = 32;    // 32 bytes de subkey
        
        // Generar salt aleatorio
        $salt = random_bytes($saltLen);
        
        // Generar subkey usando PBKDF2 con SHA512
        $subKey = hash_pbkdf2('sha512', $password, $salt, $iterCount, $subKeyLen, true);
        
        // Construir el hash en formato ASP.NET Identity
        $buffer = pack('C', 0x01) .         // Format marker
                  pack('N', $prf) .         // PRF
                  pack('N', $iterCount) .   // Iteration count
                  pack('N', $saltLen) .     // Salt length
                  $salt .                   // Salt
                  $subKey;                  // Subkey
        
        return base64_encode($buffer);
    }
    
    /**
     * Migrar hash de PHP a formato ASP.NET Identity
     */
    public static function migratePasswordHash($plainPassword, $currentHash) {
        // Verificar que la contraseña actual es correcta
        if (!password_verify($plainPassword, $currentHash)) {
            return false;
        }
        
        // Generar nuevo hash en formato ASP.NET Identity
        return self::hashPassword($plainPassword);
    }
    
    /**
     * Validar credenciales de login
     */
    public static function validateCredentials($email, $password) {
        if (empty($email) || empty($password)) {
            return false;
        }
        
        // Validar formato de email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        
        // Validar longitud de contraseña
        if (strlen($password) < 6) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Verificar si un usuario puede hacer login
     */
    public static function canLogin($user) {
        // Convertir array a objeto si es necesario
        if (is_array($user)) {
            $user = (object)$user;
        }
        
        // Verificar que el usuario existe
        if (!$user || !isset($user->id)) {
            return ['can_login' => false, 'reason' => 'Usuario no encontrado'];
        }
        
        // Verificar que tiene contraseña
        if (empty($user->password_hash)) {
            return ['can_login' => false, 'reason' => 'Usuario sin contraseña configurada'];
        }
        
        // Verificar email verificado (opcional, basado en configuración)
        if (defined('REQUIRE_EMAIL_VERIFICATION') && REQUIRE_EMAIL_VERIFICATION) {
            if (!$user->email_verified) {
                return ['can_login' => false, 'reason' => 'Email no verificado'];
            }
        }
        
        // Verificar cuenta no bloqueada
        if (isset($user->account_locked) && $user->account_locked) {
            return ['can_login' => false, 'reason' => 'Cuenta bloqueada'];
        }
        
        // Verificar intentos fallidos (si está implementado)
        if (isset($user->failed_login_attempts) && $user->failed_login_attempts >= 5) {
            return ['can_login' => false, 'reason' => 'Demasiados intentos fallidos'];
        }
        
        return ['can_login' => true, 'reason' => ''];
    }
    
    /**
     * Registrar intento de login fallido
     */
    public static function recordFailedLogin($userId, $dbConnection) {
        try {
            $stmt = $dbConnection->prepare("
                UPDATE ecc_users 
                SET failed_login_attempts = COALESCE(failed_login_attempts, 0) + 1,
                    last_failed_login = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
        } catch (Exception $e) {
            error_log("Error recording failed login: " . $e->getMessage());
        }
    }
    
    /**
     * Limpiar intentos fallidos después de login exitoso
     */
    public static function clearFailedLogins($userId, $dbConnection) {
        try {
            $stmt = $dbConnection->prepare("
                UPDATE ecc_users 
                SET failed_login_attempts = 0,
                    last_successful_login = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
        } catch (Exception $e) {
            error_log("Error clearing failed logins: " . $e->getMessage());
        }
    }
    
    /**
     * Generar token de verificación de email
     */
    public static function generateEmailVerificationToken($userId) {
        $data = $userId . '|' . time() . '|' . random_bytes(16);
        return base64_encode($data);
    }
    
    /**
     * Validar token de verificación de email
     */
    public static function validateEmailVerificationToken($token) {
        try {
            $data = base64_decode($token);
            $parts = explode('|', $data, 3);
            
            if (count($parts) !== 3) {
                return false;
            }
            
            $userId = $parts[0];
            $timestamp = $parts[1];
            
            // Token válido por 24 horas
            if (time() - $timestamp > 86400) {
                return false;
            }
            
            return $userId;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Generar datos para JWT basados en usuario
     */
    public static function generateJwtPayload($user, $rememberMe = false) {
        // Convertir array a objeto si es necesario
        if (is_array($user)) {
            $user = (object)$user;
        }
        
        $now = time();
        $expiration = $rememberMe ? (86400 * 30) : 86400; // 30 días vs 1 día
        
        return [
            // Claims estándar JWT
            'iss' => 'economia-circular-canarias',
            'aud' => 'ecc-web-app',
            'iat' => $now,
            'exp' => $now + $expiration,
            'nbf' => $now,
            'jti' => uniqid('ecc_', true),
            
            // Subject (User ID)
            'sub' => (string)$user->id,
            
            // Claims personalizados
            'email' => $user->email,
            'given_name' => $user->first_name ?? $user->firstName ?? '',
            'family_name' => $user->last_name ?? $user->lastName ?? '',
            'role' => $user->user_type ?? $user->userType ?? 'individual',
            'island' => $user->island ?? '',
            'city' => $user->city ?? '',
            'email_verified' => (bool)($user->email_verified ?? $user->emailVerified ?? false),
            'remember_me' => $rememberMe
        ];
    }
}
?>
