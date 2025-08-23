<?php
/**
 * Endpoint de Login Flexible - Se adapta a la configuración activa
 * 
 * Login dinámico que responde según la configuración establecida
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../config/api-config.php';
require_once __DIR__ . '/../models/FlexibleUser.php';
require_once __DIR__ . '/../repositories/FlexibleUserRepository.php';
require_once __DIR__ . '/../services/AuthService.php';

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
    
    // Conectar a base de datos
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    
    // Buscar usuario usando repositorio flexible
    $userRepository = new FlexibleUserRepository($pdo);
    $user = $userRepository->findByEmail($email);
    
    if (!$user) {
        jsonResponse(null, 401, 'Credenciales incorrectas');
    }
    
    // Verificar contraseña
    if (!AuthService::verifyPassword($password, $user->passwordHash)) {
        // Opcional: incrementar intentos fallidos
        incrementFailedAttempts($userRepository, $user);
        jsonResponse(null, 401, 'Credenciales incorrectas');
    }
    
    // Verificar si la cuenta está bloqueada
    if ($user->accountLocked) {
        jsonResponse(null, 423, 'Cuenta bloqueada. Contacte al administrador.');
    }
    
    // Verificar email si la verificación está habilitada
    if (ApiConfig::isEmailVerificationEnabled() && !$user->emailVerified) {
        // Usuario válido pero email no confirmado
        $userResponse = ApiConfig::createUserResponse($user->toArray());
        
        jsonResponse([
            'user' => $userResponse,
            'requiresEmailConfirmation' => true
        ], 200, 'Email no confirmado. Revise su bandeja de entrada.');
    }
    
    // Login exitoso - actualizar información de login
    updateSuccessfulLogin($userRepository, $user);
    
    // Crear token JWT
    $tokenPayload = [
        'user_id' => $user->id,
        'email' => $user->email,
        'iat' => time(),
        'exp' => time() + (24 * 60 * 60) // 24 horas
    ];
    
    // Agregar información adicional al token según configuración
    $config = ApiConfig::getConfig();
    if (ApiConfig::isFieldValid('firstName') && $user->firstName) {
        $tokenPayload['firstName'] = $user->firstName;
    }
    if (ApiConfig::isFieldValid('lastName') && $user->lastName) {
        $tokenPayload['lastName'] = $user->lastName;
    }
    if (ApiConfig::isFieldValid('userType') && $user->userType) {
        $tokenPayload['userType'] = $user->userType;
    }
    
    $token = JWT::encode($tokenPayload, JWT_SECRET);
    
    // Preparar respuesta de usuario según configuración
    $userResponse = ApiConfig::createUserResponse($user->toArray());
    
    $response = [
        'token' => $token,
        'user' => $userResponse,
        'message' => 'Login exitoso'
    ];
    
    // Información adicional según configuración
    if (isset($data['include_config_info']) && $data['include_config_info']) {
        $response['config_info'] = [
            'profile' => ApiConfig::getActiveProfile(),
            'table_used' => $userRepository->getTable(),
            'fields_returned' => array_keys($userResponse)
        ];
    }
    
    // Información de expiración del token
    $response['token_expires'] = date('c', $tokenPayload['exp']);
    
    jsonResponse($response, 200, 'Login exitoso');
    
} catch (Exception $e) {
    error_log("Error en login flexible: " . $e->getMessage());
    jsonResponse(null, 500, 'Error interno del servidor');
}

/**
 * Incrementar intentos fallidos de login
 */
function incrementFailedAttempts($userRepository, $user) {
    try {
        if ($user->failedLoginAttempts !== null) {
            $user->failedLoginAttempts += 1;
            $user->lastFailedLogin = date('Y-m-d H:i:s');
            
            // Bloquear cuenta después de 5 intentos fallidos (configurable)
            $maxAttempts = 5;
            if ($user->failedLoginAttempts >= $maxAttempts) {
                $user->accountLocked = true;
                error_log("Cuenta bloqueada por múltiples intentos fallidos: {$user->email}");
            }
            
            $userRepository->update($user);
        }
    } catch (Exception $e) {
        error_log("Error incrementando intentos fallidos: " . $e->getMessage());
    }
}

/**
 * Actualizar información de login exitoso
 */
function updateSuccessfulLogin($userRepository, $user) {
    try {
        if ($user->lastSuccessfulLogin !== null || $user->failedLoginAttempts !== null) {
            $user->lastSuccessfulLogin = date('Y-m-d H:i:s');
            $user->failedLoginAttempts = 0; // Resetear intentos fallidos
            
            $userRepository->update($user);
        }
    } catch (Exception $e) {
        error_log("Error actualizando login exitoso: " . $e->getMessage());
    }
}

?>
