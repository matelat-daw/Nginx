<?php
/**
 * Endpoint de Registro Flexible - Se adapta a la configuración activa
 * 
 * Registro dinámico que valida según la configuración establecida
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
    
    if (!$data) {
        jsonResponse(null, 400, 'Datos de entrada requeridos');
    }
    
    // Validar datos según configuración activa
    $validationErrors = ApiConfig::validateData($data);
    
    if (!empty($validationErrors)) {
        jsonResponse(null, 400, implode(', ', $validationErrors));
    }
    
    // Filtrar y procesar datos según configuración
    $filteredData = ApiConfig::filterData($data);
    
    // Validar contraseña si está presente
    if (isset($filteredData['password'])) {
        if (strlen($filteredData['password']) < 6) {
            jsonResponse(null, 400, 'La contraseña debe tener al menos 6 caracteres');
        }
        
        // Hash de la contraseña
        $filteredData['password'] = AuthService::hashPassword($filteredData['password']);
    }
    
    // Crear usuario desde datos filtrados
    $user = FlexibleUser::fromInputData($filteredData);
    
    // Si la contraseña se procesó, establecerla
    if (isset($filteredData['password'])) {
        $user->passwordHash = $filteredData['password'];
    }
    
    // Validar usuario
    if (!$user->isValid(true)) {
        $errors = $user->getValidationErrors();
        jsonResponse(null, 400, implode(', ', $errors));
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
    
    // Crear repositorio y verificar si el email ya existe
    $userRepository = new FlexibleUserRepository($pdo);
    
    if ($userRepository->emailExists($user->email)) {
        jsonResponse(null, 409, 'El email ya está registrado');
    }
    
    // Crear usuario en base de datos
    $createdUser = $userRepository->create($user);
    
    if (!$createdUser) {
        jsonResponse(null, 500, 'Error creando usuario');
    }
    
    // Preparar respuesta según configuración
    $userResponse = ApiConfig::createUserResponse($createdUser->toArray());
    
    $response = [
        'user' => $userResponse,
        'message' => 'Usuario creado exitosamente'
    ];
    
    // Agregar información adicional según configuración
    $config = ApiConfig::getConfig();
    
    if (ApiConfig::isEmailVerificationEnabled()) {
        if (!$createdUser->emailVerified) {
            $response['requiresEmailConfirmation'] = true;
            $response['message'] = 'Usuario creado. Se requiere confirmación de email.';
            
            // Aquí podrías enviar email de confirmación
            // sendEmailConfirmation($createdUser->email, $createdUser->id);
        }
    }
    
    // Generar token JWT si el usuario está verificado o no se requiere verificación
    if (!ApiConfig::isEmailVerificationEnabled() || $createdUser->emailVerified) {
        $tokenPayload = [
            'user_id' => $createdUser->id,
            'email' => $createdUser->email,
            'iat' => time(),
            'exp' => time() + (24 * 60 * 60) // 24 horas
        ];
        
        $token = JWT::encode($tokenPayload, JWT_SECRET);
        $response['token'] = $token;
    }
    
    // Información de configuración para debugging (opcional)
    if (isset($data['include_config_info']) && $data['include_config_info']) {
        $response['config_info'] = [
            'profile' => ApiConfig::getActiveProfile(),
            'fields_used' => array_keys($filteredData),
            'table' => $userRepository->getTable()
        ];
    }
    
    jsonResponse($response, 201, 'Usuario registrado exitosamente');
    
} catch (Exception $e) {
    error_log("Error en registro flexible: " . $e->getMessage());
    
    // Proporcionar mensajes más específicos según el tipo de error
    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
        jsonResponse(null, 409, 'El email ya está registrado');
    } elseif (strpos($e->getMessage(), 'email') !== false) {
        jsonResponse(null, 400, 'Error relacionado con el email');
    } else {
        jsonResponse(null, 500, 'Error interno del servidor');
    }
}

/**
 * Función auxiliar para envío de email de confirmación (placeholder)
 */
function sendEmailConfirmation($email, $userId) {
    // Implementar envío de email de confirmación
    // Esta función sería específica según el proveedor de email usado
    
    error_log("Email de confirmación enviado a: {$email} para usuario ID: {$userId}");
    return true;
}

?>
