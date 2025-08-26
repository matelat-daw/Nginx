<?php
/**
 * Endpoint de Perfil Flexible - Se adapta a la configuración activa
 * 
 * Obtiene y actualiza perfiles de usuario según la configuración establecida
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

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Verificar autenticación
    $user = authenticateUser();
    if (!$user) {
        jsonResponse(null, 401, 'Token inválido o expirado');
    }
    
    switch ($method) {
        case 'GET':
            handleGetProfile($user);
            break;
            
        case 'PUT':
            handleUpdateProfile($user);
            break;
            
        default:
            jsonResponse(null, 405, 'Método no permitido');
    }
    
} catch (Exception $e) {
    error_log("Error en perfil flexible: " . $e->getMessage());
    jsonResponse(null, 500, 'Error interno del servidor');
}

/**
 * Obtener perfil de usuario
 */
function handleGetProfile($authenticatedUser) {
    try {
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
        
        // Obtener usuario completo de la base de datos
        $userRepository = new FlexibleUserRepository($pdo);
        $user = $userRepository->findById($authenticatedUser->user_id);
        
        if (!$user) {
            jsonResponse(null, 404, 'Usuario no encontrado');
        }
        
        // Preparar respuesta según configuración activa
        $userResponse = ApiConfig::createUserResponse($user->toArray());
        
        // Información adicional sobre campos disponibles
        $availableFields = ApiConfig::getAllFields();
        $profileFields = ApiConfig::getProfileFields();
        
        $response = [
            'user' => $userResponse,
            'available_fields' => $availableFields,
            'profile_fields' => $profileFields,
            'profile' => ApiConfig::getActiveProfile()
        ];
        
        jsonResponse($response, 200, 'Perfil obtenido exitosamente');
        
    } catch (Exception $e) {
        error_log("Error obteniendo perfil: " . $e->getMessage());
        jsonResponse(null, 500, 'Error obteniendo perfil');
    }
}

/**
 * Actualizar perfil de usuario
 */
function handleUpdateProfile($authenticatedUser) {
    try {
        // Obtener datos de entrada
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data) {
            jsonResponse(null, 400, 'Datos requeridos');
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
        
        // Obtener usuario actual
        $userRepository = new FlexibleUserRepository($pdo);
        $user = $userRepository->findById($authenticatedUser->user_id);
        
        if (!$user) {
            jsonResponse(null, 404, 'Usuario no encontrado');
        }
        
        // Filtrar datos según configuración activa
        $filteredData = ApiConfig::filterData($data);
        
        // Remover campos que no se pueden actualizar
        $nonUpdatableFields = ['id', 'email', 'password', 'passwordHash', 'createdAt'];
        foreach ($nonUpdatableFields as $field) {
            unset($filteredData[$field]);
        }
        
        // Validar cambio de email si está presente y es permitido
        if (isset($data['email']) && $data['email'] !== $user->email) {
            // Verificar si el cambio de email está permitido
            if (!ApiConfig::isFieldValid('email')) {
                jsonResponse(null, 400, 'Cambio de email no permitido en esta configuración');
            }
            
            // Verificar que el nuevo email no exista
            if ($userRepository->emailExists($data['email'])) {
                jsonResponse(null, 409, 'El nuevo email ya está en uso');
            }
            
            $filteredData['email'] = filter_var($data['email'], FILTER_VALIDATE_EMAIL);
            if (!$filteredData['email']) {
                jsonResponse(null, 400, 'Formato de email inválido');
            }
            
            // Si la verificación de email está habilitada, marcar como no verificado
            if (ApiConfig::isEmailVerificationEnabled()) {
                $filteredData['emailVerified'] = false;
            }
        }
        
        // Manejar cambio de contraseña si está presente
        if (isset($data['password']) || isset($data['newPassword'])) {
            $newPassword = $data['password'] ?? $data['newPassword'];
            
            if (strlen($newPassword) < 6) {
                jsonResponse(null, 400, 'La nueva contraseña debe tener al menos 6 caracteres');
            }
            
            // Si se requiere contraseña actual para cambio
            if (isset($data['currentPassword'])) {
                if (!AuthService::verifyPassword($data['currentPassword'], $user->passwordHash)) {
                    jsonResponse(null, 400, 'Contraseña actual incorrecta');
                }
            }
            
            $filteredData['passwordHash'] = AuthService::hashPassword($newPassword);
        }
        
        // Actualizar campos del usuario
        foreach ($filteredData as $field => $value) {
            if ($field === 'passwordHash') {
                $user->passwordHash = $value;
            } else {
                $user->setFieldValue($field, $value);
            }
        }
        
        // Validar usuario actualizado
        if (!$user->isValid()) {
            $errors = $user->getValidationErrors();
            jsonResponse(null, 400, implode(', ', $errors));
        }
        
        // Guardar cambios
        $success = $userRepository->update($user);
        
        if (!$success) {
            jsonResponse(null, 500, 'Error actualizando perfil');
        }
        
        // Preparar respuesta
        $userResponse = ApiConfig::createUserResponse($user->toArray());
        
        $response = [
            'user' => $userResponse,
            'message' => 'Perfil actualizado exitosamente'
        ];
        
        // Si se cambió el email y hay verificación habilitada
        if (isset($filteredData['email']) && ApiConfig::isEmailVerificationEnabled()) {
            $response['requiresEmailConfirmation'] = true;
            $response['message'] = 'Perfil actualizado. Se requiere confirmar el nuevo email.';
        }
        
        // Si se cambió la contraseña, generar nuevo token
        if (isset($data['password']) || isset($data['newPassword'])) {
            $tokenPayload = [
                'user_id' => $user->id,
                'email' => $user->email,
                'iat' => time(),
                'exp' => time() + (24 * 60 * 60) // 24 horas
            ];
            
            $newToken = JWT::encode($tokenPayload, JWT_SECRET);
            $response['token'] = $newToken;
            $response['message'] = 'Perfil y contraseña actualizados. Nuevo token generado.';
        }
        
        jsonResponse($response, 200, 'Perfil actualizado exitosamente');
        
    } catch (Exception $e) {
        error_log("Error actualizando perfil: " . $e->getMessage());
        jsonResponse(null, 500, 'Error actualizando perfil');
    }
}

/**
 * Autenticar usuario desde token JWT
 */
function authenticateUser() {
    try {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        
        if (!$authHeader) {
            return null;
        }
        
        // Extraer token del header "Bearer token"
        if (strpos($authHeader, 'Bearer ') === 0) {
            $token = substr($authHeader, 7);
        } else {
            $token = $authHeader;
        }
        
        if (!$token) {
            return null;
        }
        
        // Decodificar y verificar token
        $decoded = JWT::decode($token, JWT_SECRET);
        
        return $decoded;
        
    } catch (Exception $e) {
        error_log("Error autenticando usuario: " . $e->getMessage());
        return null;
    }
}

?>
