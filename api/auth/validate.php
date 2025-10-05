<?php
/**
 * Controlador de Validación de Token - Economía Circular Canarias
 * Versión 2.0 - Optimizado con nuevas funciones helper
 */

require_once __DIR__ . '/../config.php';

// Headers CORS y seguridad
setCorsHeaders();
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
    
    // Validar token usando función optimizada
    if (!JWT::validate($token, JWT_SECRET)) {
        jsonResponse([
            'success' => false,
            'valid' => false,
            'message' => 'Token inválido o expirado'
        ], 401);
    }
    
    // Decodificar payload usando nuestro método personalizado
    $decoded = JWT::decode($token, JWT_SECRET);
    
    // Obtener user_id del payload
    $userId = $decoded->userId ?? $decoded->user_id ?? null;
    
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
            'email' => $decoded->email,
            'expires_at' => $decoded->exp,
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
