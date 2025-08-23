<?php
/**
 * Endpoint de Configuración - Permite configurar la API dinámicamente
 * 
 * Permite cambiar entre diferentes perfiles de configuración
 * o establecer configuraciones personalizadas
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../config/api-config.php';
require_once __DIR__ . '/../repositories/FlexibleUserRepository.php';

// Headers CORS
setCorsHeaders();

// Manejar preflight requests
handlePreflight();

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            handleGetConfig();
            break;
            
        case 'POST':
            handleSetConfig();
            break;
            
        case 'PUT':
            handleUpdateConfig();
            break;
            
        default:
            jsonResponse(null, 405, 'Método no permitido');
    }
    
} catch (Exception $e) {
    error_log("Error en configuración: " . $e->getMessage());
    jsonResponse(null, 500, 'Error interno del servidor');
}

/**
 * Obtener configuración actual y perfiles disponibles
 */
function handleGetConfig() {
    try {
        // Obtener información de la base de datos
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
        
        $userRepository = new FlexibleUserRepository($pdo);
        $tableInfo = $userRepository->getTableInfo();
        
        $response = [
            'current' => [
                'profile' => ApiConfig::getActiveProfile(),
                'config' => ApiConfig::getConfig(),
                'database' => $tableInfo
            ],
            'available_profiles' => [],
            'recommendations' => []
        ];
        
        // Obtener información de todos los perfiles disponibles
        foreach (ApiConfig::getAvailableProfiles() as $profileName) {
            $profileInfo = ApiConfig::getProfileInfo($profileName);
            if ($profileInfo) {
                $response['available_profiles'][$profileName] = $profileInfo;
            }
        }
        
        // Generar recomendaciones basadas en la estructura de la BD
        if ($tableInfo) {
            $recommendations = generateRecommendations($tableInfo);
            $response['recommendations'] = $recommendations;
        }
        
        jsonResponse($response, 200, 'Configuración obtenida exitosamente');
        
    } catch (Exception $e) {
        error_log("Error obteniendo configuración: " . $e->getMessage());
        jsonResponse(null, 500, 'Error obteniendo configuración');
    }
}

/**
 * Establecer nueva configuración
 */
function handleSetConfig() {
    try {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data) {
            jsonResponse(null, 400, 'Datos requeridos');
        }
        
        if (isset($data['profile'])) {
            // Cambiar a un perfil predefinido
            $success = ApiConfig::setProfile($data['profile']);
            
            if (!$success) {
                jsonResponse(null, 400, 'Perfil no válido');
            }
            
            $message = "Perfil '{$data['profile']}' activado exitosamente";
            
        } elseif (isset($data['custom_config'])) {
            // Establecer configuración personalizada
            $customConfig = $data['custom_config'];
            
            // Validar configuración personalizada
            $validation = validateCustomConfig($customConfig);
            if (!$validation['valid']) {
                jsonResponse(null, 400, 'Configuración inválida: ' . implode(', ', $validation['errors']));
            }
            
            ApiConfig::setCustomConfig($customConfig);
            $message = "Configuración personalizada establecida exitosamente";
            
        } else {
            jsonResponse(null, 400, 'Debe especificar "profile" o "custom_config"');
        }
        
        // Guardar configuración persistente
        $configFile = __DIR__ . '/../config/current-config.json';
        ApiConfig::saveToFile($configFile);
        
        jsonResponse([
            'profile' => ApiConfig::getActiveProfile(),
            'config' => ApiConfig::getConfig()
        ], 200, $message);
        
    } catch (Exception $e) {
        error_log("Error estableciendo configuración: " . $e->getMessage());
        jsonResponse(null, 500, 'Error estableciendo configuración');
    }
}

/**
 * Actualizar configuración actual (solo para configuraciones personalizadas)
 */
function handleUpdateConfig() {
    try {
        if (ApiConfig::getActiveProfile() !== 'custom') {
            jsonResponse(null, 400, 'Solo se pueden actualizar configuraciones personalizadas');
        }
        
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || !isset($data['config'])) {
            jsonResponse(null, 400, 'Configuración requerida');
        }
        
        $newConfig = $data['config'];
        
        // Validar nueva configuración
        $validation = validateCustomConfig($newConfig);
        if (!$validation['valid']) {
            jsonResponse(null, 400, 'Configuración inválida: ' . implode(', ', $validation['errors']));
        }
        
        ApiConfig::setCustomConfig($newConfig);
        
        // Guardar configuración persistente
        $configFile = __DIR__ . '/../config/current-config.json';
        ApiConfig::saveToFile($configFile);
        
        jsonResponse([
            'profile' => ApiConfig::getActiveProfile(),
            'config' => ApiConfig::getConfig()
        ], 200, 'Configuración actualizada exitosamente');
        
    } catch (Exception $e) {
        error_log("Error actualizando configuración: " . $e->getMessage());
        jsonResponse(null, 500, 'Error actualizando configuración');
    }
}

/**
 * Validar configuración personalizada
 */
function validateCustomConfig($config) {
    $errors = [];
    
    // Verificar campos requeridos
    $requiredConfigFields = ['name', 'required_fields', 'optional_fields'];
    foreach ($requiredConfigFields as $field) {
        if (!isset($config[$field])) {
            $errors[] = "Campo de configuración requerido: {$field}";
        }
    }
    
    // Verificar que required_fields sea un array
    if (isset($config['required_fields']) && !is_array($config['required_fields'])) {
        $errors[] = "required_fields debe ser un array";
    }
    
    // Verificar que optional_fields sea un array
    if (isset($config['optional_fields']) && !is_array($config['optional_fields'])) {
        $errors[] = "optional_fields debe ser un array";
    }
    
    // Verificar que al menos email esté en required_fields
    if (isset($config['required_fields']) && !in_array('email', $config['required_fields'])) {
        $errors[] = "email debe estar en required_fields";
    }
    
    // Verificar que password esté en required_fields
    if (isset($config['required_fields']) && !in_array('password', $config['required_fields'])) {
        $errors[] = "password debe estar en required_fields";
    }
    
    // Verificar que no haya duplicados entre required y optional
    if (isset($config['required_fields']) && isset($config['optional_fields'])) {
        $duplicates = array_intersect($config['required_fields'], $config['optional_fields']);
        if (!empty($duplicates)) {
            $errors[] = "Campos duplicados entre required y optional: " . implode(', ', $duplicates);
        }
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Generar recomendaciones basadas en la estructura de la base de datos
 */
function generateRecommendations($tableInfo) {
    $recommendations = [];
    
    if (!$tableInfo['has_names']) {
        $recommendations[] = [
            'type' => 'profile',
            'recommendation' => 'minimal',
            'reason' => 'La tabla no tiene campos de nombre, se recomienda perfil mínimo (solo email/password)'
        ];
    } elseif ($tableInfo['has_names'] && !$tableInfo['has_location']) {
        $recommendations[] = [
            'type' => 'profile',
            'recommendation' => 'standard',
            'reason' => 'La tabla tiene campos de nombre pero no de ubicación, perfil estándar recomendado'
        ];
    } elseif ($tableInfo['has_location'] && $tableInfo['has_profile']) {
        $recommendations[] = [
            'type' => 'profile',
            'recommendation' => 'complete',
            'reason' => 'La tabla tiene todos los campos disponibles, perfil completo recomendado'
        ];
    }
    
    if (!$tableInfo['has_verification']) {
        $recommendations[] = [
            'type' => 'setting',
            'recommendation' => 'disable_email_verification',
            'reason' => 'La tabla no tiene campo de verificación de email, se recomienda deshabilitarlo'
        ];
    }
    
    $recommendations[] = [
        'type' => 'info',
        'recommendation' => 'table_analysis',
        'reason' => "Usando tabla '{$tableInfo['table']}' con {$tableInfo['total_fields']} campos disponibles"
    ];
    
    return $recommendations;
}

?>
