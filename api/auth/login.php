<?php
/**
 * Controlador de Login - Economía Circular Canarias
 * 
 * Login optimizado con configuración centralizada
 */

// Incluir configuración
require_once __DIR__ . '/../config.php';

// Headers CORS
setCorsHeaders();

// Manejar preflight requests
handlePreflight();

// Solo permitir método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(null, 405, 'Método no permitido');
}

try {
    // Obtener input JSON
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['email']) || !isset($data['password'])) {
        jsonResponse(null, 400, 'Email y contraseña son requeridos');
    }
    
    // Sanitizar input
    $email = filter_var(trim($data['email']), FILTER_SANITIZE_EMAIL);
    $password = $data['password'];
    
    // Validar email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(null, 400, 'Formato de email inválido');
    }
    
    // Usar la función centralizada para obtener conexión DB
    $pdo = getDBConnection();
    
    // Buscar usuario por email (primero en ecc_users)
    $stmt = $pdo->prepare("SELECT * FROM ecc_users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    // Si no está en ecc_users, buscar en tabla user
    if (!$user) {
        $stmt = $pdo->prepare("SELECT * FROM user WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
    }
    
    if (!$user) {
        jsonResponse(null, 401, 'Credenciales incorrectas');
    }
    
    // Verificar contraseña
    $passwordField = isset($user['password_hash']) ? 'password_hash' : 'password';
    $storedPassword = $user[$passwordField];
    
    $passwordValid = false;
    
    // Intentar verificar con password_verify (bcrypt/argon2)
    if (password_verify($password, $storedPassword)) {
        $passwordValid = true;
    }
    // Si no funciona, intentar comparación directa (para passwords legacy)
    elseif ($password === $storedPassword) {
        $passwordValid = true;
    }
    
    if (!$passwordValid) {
        jsonResponse(null, 401, 'Credenciales incorrectas');
    }
    
    // Verificar si el email está confirmado (solo para tabla ecc_users)
    $emailVerified = isset($user['email_verified']) ? (bool)$user['email_verified'] : true;
    
    if (!$emailVerified) {
        // Usuario válido pero email no confirmado
        jsonResponse([
            'user' => [
                'id' => (int)$user['id'],
                'firstName' => $user['first_name'] ?? '',
                'lastName' => $user['last_name'] ?? '',
                'email' => $user['email'],
                'emailVerified' => false
            ],
            'requiresEmailConfirmation' => true
        ], 200, 'Tu cuenta está registrada pero necesitas confirmar tu email para continuar. Revisa tu bandeja de entrada y haz clic en el enlace de confirmación.');
    }
    
    // Generar token JWT
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload = json_encode([
        'user_id' => $user['id'],
        'email' => $user['email'],
        'exp' => time() + JWT_EXPIRATION
    ]);
    
    $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    
    $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, JWT_SECRET, true);
    $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    $jwt = $base64Header . "." . $base64Payload . "." . $base64Signature;
    
    // Establecer cookie
    setcookie(COOKIE_NAME, $jwt, [
        'expires' => time() + COOKIE_EXPIRATION,
        'path' => '/',
        'domain' => '',
        'secure' => COOKIE_SECURE,
        'httponly' => COOKIE_HTTP_ONLY,
        'samesite' => COOKIE_SAME_SITE
    ]);
    
    // Preparar respuesta
    $userData = [
        'id' => (int)$user['id'],
        'firstName' => $user['first_name'] ?? $user['firstName'] ?? '',
        'lastName' => $user['last_name'] ?? $user['lastName'] ?? '',
        'email' => $user['email'],
        'island' => $user['island'] ?? '',
        'city' => $user['city'] ?? '',
        'userType' => $user['user_type'] ?? $user['userType'] ?? 'user',
        'emailVerified' => isset($user['email_verified']) ? (bool)$user['email_verified'] : true
    ];
    
    // Log del login exitoso
    logMessage('INFO', "Successful login for user: {$email}");
    
    jsonResponse([
        'user' => $userData,
        'token' => $jwt
    ], 200, "¡Bienvenido/a de nuevo, {$userData['firstName']}!");
    
} catch (PDOException $e) {
    logMessage('ERROR', "Database error in login: " . $e->getMessage());
    
    // En modo desarrollo, mostrar más detalles del error
    if (DEBUG_MODE) {
        jsonResponse(null, 500, 'Error de base de datos: ' . $e->getMessage());
    } else {
        jsonResponse(null, 500, 'Error de base de datos');
    }
} catch (Exception $e) {
    logMessage('ERROR', "Error in login: " . $e->getMessage());
    
    // En modo desarrollo, mostrar más detalles del error  
    if (DEBUG_MODE) {
        jsonResponse(null, 500, 'Error interno: ' . $e->getMessage());
    } else {
        jsonResponse(null, 500, 'Error interno del servidor');
    }
}
