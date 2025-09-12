<?php
/**
 * Controlador de Validación de Token - Economía Circular Canarias
 * 
 * Validación optimizada con configuración centralizada
 */

// Incluir configuración
require_once __DIR__ . '/../config.php';

// Headers CORS
setCorsHeaders();

// Manejar preflight requests
handlePreflight();

try {
    // Obtener token de la cookie o del header Authorization
    $token = null;
    
    if (isset($_COOKIE[COOKIE_NAME])) {
        $token = $_COOKIE[COOKIE_NAME];
    } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $auth_header = $_SERVER['HTTP_AUTHORIZATION'];
        if (preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
            $token = $matches[1];
        }
    }
    
    if (!$token) {
        jsonResponse([
            'success' => false,
            'valid' => false,
            'message' => 'Token no encontrado'
        ], 401);
    }
    
    // Validar token JWT manualmente (mismo método que login/register)
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        jsonResponse([
            'success' => false,
            'valid' => false,
            'message' => 'Formato de token inválido'
        ], 401);
    }
    
    // Decodificar payload
    $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
    
    if (!$payload) {
        jsonResponse([
            'success' => false,
            'valid' => false,
            'message' => 'Token corrupto'
        ], 401);
    }
    
    // Verificar expiración
    if (isset($payload['exp']) && time() > $payload['exp']) {
        jsonResponse([
            'success' => false,
            'valid' => false,
            'message' => 'Token expirado'
        ], 401);
    }
    
    // Verificar signature
    $header = $parts[0];
    $payloadPart = $parts[1];
    $signature = $parts[2];
    
    $expectedSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(
        hash_hmac('sha256', $header . '.' . $payloadPart, JWT_SECRET, true)
    ));
    
    if ($signature !== $expectedSignature) {
        jsonResponse([
            'success' => false,
            'valid' => false,
            'message' => 'Token signature inválida'
        ], 401);
    }
    
    // Obtener user_id del payload
    $userId = isset($payload['user_id']) ? $payload['user_id'] : null;
    
    if (!$userId) {
        jsonResponse([
            'success' => false,
            'valid' => false,
            'message' => 'Token no contiene información de usuario'
        ], 401);
    }
    
    // Usar la función centralizada para obtener conexión DB
    $pdo = getDBConnection();
    
    // Buscar usuario por ID
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        jsonResponse([
            'success' => false,
            'valid' => false,
            'message' => 'Usuario no encontrado'
        ], 401);
    }
    
    // Preparar datos del usuario para la respuesta
    $userData = [
        'id' => (int)$user['id'],
        'firstName' => $user['first_name'],
        'lastName' => $user['last_name'],
        'email' => $user['email'],
        'island' => $user['island'],
        'city' => $user['city'],
        'userType' => $user['user_type'],
        'emailVerified' => (bool)$user['email_verified']
    ];
    
    // Log validación exitosa
    logMessage('INFO', "Token validated for user: {$user['email']} (ID: {$userId})");
    
    // Token válido
    jsonResponse([
        'success' => true,
        'valid' => true,
        'user' => $userData,
        'tokenInfo' => [
            'user_id' => $userId,
            'email' => $payload['email'],
            'expires_at' => $payload['exp'],
            'issued_for' => $user['email']
        ]
    ], 200, 'Token válido');
    
} catch (PDOException $e) {
    logMessage('ERROR', "Database error in validate: " . $e->getMessage());
    jsonResponse([
        'success' => false,
        'valid' => false,
        'message' => 'Error de base de datos'
    ], 500);
} catch (Exception $e) {
    logMessage('ERROR', "Error in validate: " . $e->getMessage());
    jsonResponse([
        'success' => false,
        'valid' => false,
        'message' => 'Error interno del servidor'
    ], 500);
}
?>
