<?php
/**
 * Configuración Flexible de API - Sistema Multi-Frontend
 * 
 * Permite configurar la API para diferentes tipos de aplicaciones
 * desde un simple login con email/password hasta sistemas complejos
 */

class ApiConfig {
    
    // Perfiles predefinidos de configuración
    private static $profiles = [
        'minimal' => [
            'name' => 'Login Básico',
            'description' => 'Solo email y contraseña',
            'required_fields' => ['email', 'password'],
            'optional_fields' => [],
            'user_validation' => 'basic',
            'registration_flow' => 'simple',
            'email_verification' => false,
            'profile_fields' => []
        ],
        
        'standard' => [
            'name' => 'Registro Estándar',
            'description' => 'Email, contraseña, nombre y apellido',
            'required_fields' => ['email', 'password', 'firstName', 'lastName'],
            'optional_fields' => ['phoneNumber'],
            'user_validation' => 'standard',
            'registration_flow' => 'with_names',
            'email_verification' => true,
            'profile_fields' => ['firstName', 'lastName', 'email', 'phoneNumber']
        ],
        
        'complete' => [
            'name' => 'Sistema Completo',
            'description' => 'Todos los campos disponibles',
            'required_fields' => ['email', 'password', 'firstName', 'lastName'],
            'optional_fields' => ['island', 'city', 'userType', 'phoneNumber', 'about'],
            'user_validation' => 'complete',
            'registration_flow' => 'full_profile',
            'email_verification' => true,
            'profile_fields' => ['firstName', 'lastName', 'email', 'phoneNumber', 'island', 'city', 'userType', 'about', 'profileImage']
        ],
        
        'custom' => [
            'name' => 'Configuración Personalizada',
            'description' => 'Configuración definida por el usuario',
            'required_fields' => [],
            'optional_fields' => [],
            'user_validation' => 'custom',
            'registration_flow' => 'custom',
            'email_verification' => false,
            'profile_fields' => []
        ]
    ];
    
    // Configuración actual activa
    private static $activeProfile = 'standard'; // Por defecto
    private static $customConfig = null;
    
    /**
     * Establecer perfil de configuración activo
     */
    public static function setProfile($profileName) {
        if (isset(self::$profiles[$profileName])) {
            self::$activeProfile = $profileName;
            return true;
        }
        return false;
    }
    
    /**
     * Obtener perfil activo
     */
    public static function getActiveProfile() {
        return self::$activeProfile;
    }
    
    /**
     * Obtener configuración activa
     */
    public static function getConfig() {
        if (self::$activeProfile === 'custom' && self::$customConfig) {
            return self::$customConfig;
        }
        return self::$profiles[self::$activeProfile] ?? self::$profiles['standard'];
    }
    
    /**
     * Establecer configuración personalizada
     */
    public static function setCustomConfig($config) {
        self::$customConfig = $config;
        self::$activeProfile = 'custom';
    }
    
    /**
     * Obtener todos los perfiles disponibles
     */
    public static function getAvailableProfiles() {
        return array_keys(self::$profiles);
    }
    
    /**
     * Obtener información de un perfil específico
     */
    public static function getProfileInfo($profileName) {
        return self::$profiles[$profileName] ?? null;
    }
    
    /**
     * Obtener campos requeridos para el perfil activo
     */
    public static function getRequiredFields() {
        $config = self::getConfig();
        return $config['required_fields'] ?? [];
    }
    
    /**
     * Obtener campos opcionales para el perfil activo
     */
    public static function getOptionalFields() {
        $config = self::getConfig();
        return $config['optional_fields'] ?? [];
    }
    
    /**
     * Obtener todos los campos (requeridos + opcionales)
     */
    public static function getAllFields() {
        return array_merge(self::getRequiredFields(), self::getOptionalFields());
    }
    
    /**
     * Verificar si un campo es requerido
     */
    public static function isFieldRequired($fieldName) {
        return in_array($fieldName, self::getRequiredFields());
    }
    
    /**
     * Verificar si un campo es opcional
     */
    public static function isFieldOptional($fieldName) {
        return in_array($fieldName, self::getOptionalFields());
    }
    
    /**
     * Verificar si un campo es válido (requerido u opcional)
     */
    public static function isFieldValid($fieldName) {
        return self::isFieldRequired($fieldName) || self::isFieldOptional($fieldName);
    }
    
    /**
     * Obtener campos de perfil para respuestas
     */
    public static function getProfileFields() {
        $config = self::getConfig();
        return $config['profile_fields'] ?? [];
    }
    
    /**
     * Verificar si la verificación de email está habilitada
     */
    public static function isEmailVerificationEnabled() {
        $config = self::getConfig();
        return $config['email_verification'] ?? false;
    }
    
    /**
     * Obtener tipo de validación de usuario
     */
    public static function getUserValidationType() {
        $config = self::getConfig();
        return $config['user_validation'] ?? 'standard';
    }
    
    /**
     * Obtener tipo de flujo de registro
     */
    public static function getRegistrationFlow() {
        $config = self::getConfig();
        return $config['registration_flow'] ?? 'standard';
    }
    
    /**
     * Validar datos según configuración activa
     */
    public static function validateData($data) {
        $errors = [];
        $config = self::getConfig();
        
        // Verificar campos requeridos
        foreach ($config['required_fields'] as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                $errors[] = "Campo requerido faltante: {$field}";
            }
        }
        
        // Validaciones específicas según el campo
        if (isset($data['email']) && !empty($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Formato de email inválido";
            }
        }
        
        if (isset($data['password']) && !empty($data['password'])) {
            if (strlen($data['password']) < 6) {
                $errors[] = "La contraseña debe tener al menos 6 caracteres";
            }
        }
        
        // Validaciones para nombres si están presentes
        if (isset($data['firstName']) && !empty($data['firstName'])) {
            if (strlen(trim($data['firstName'])) < 2) {
                $errors[] = "El nombre debe tener al menos 2 caracteres";
            }
        }
        
        if (isset($data['lastName']) && !empty($data['lastName'])) {
            if (strlen(trim($data['lastName'])) < 2) {
                $errors[] = "El apellido debe tener al menos 2 caracteres";
            }
        }
        
        return $errors;
    }
    
    /**
     * Filtrar datos según campos válidos de la configuración
     */
    public static function filterData($data) {
        $validFields = self::getAllFields();
        $filtered = [];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $validFields) || $key === 'password') { // password siempre permitido
                $filtered[$key] = $value;
            }
        }
        
        return $filtered;
    }
    
    /**
     * Crear respuesta de usuario según campos de perfil configurados
     */
    public static function createUserResponse($userData) {
        $profileFields = self::getProfileFields();
        $response = [];
        
        // Si no hay campos específicos configurados, usar campos básicos
        if (empty($profileFields)) {
            $profileFields = ['id', 'email', 'firstName', 'lastName'];
        }
        
        // Siempre incluir id y email
        if (!in_array('id', $profileFields)) {
            $profileFields[] = 'id';
        }
        if (!in_array('email', $profileFields)) {
            $profileFields[] = 'email';
        }
        
        foreach ($profileFields as $field) {
            if (isset($userData[$field])) {
                $response[$field] = $userData[$field];
            }
        }
        
        // Agregar información de verificación de email si está habilitada
        if (self::isEmailVerificationEnabled()) {
            $response['emailVerified'] = isset($userData['emailVerified']) 
                ? (bool)$userData['emailVerified'] 
                : (isset($userData['email_verified']) ? (bool)$userData['email_verified'] : false);
        }
        
        return $response;
    }
    
    /**
     * Cargar configuración desde archivo (para persistencia)
     */
    public static function loadFromFile($filePath) {
        if (file_exists($filePath)) {
            $config = json_decode(file_get_contents($filePath), true);
            if ($config && isset($config['profile'])) {
                if ($config['profile'] === 'custom' && isset($config['custom_config'])) {
                    self::setCustomConfig($config['custom_config']);
                } else {
                    self::setProfile($config['profile']);
                }
                return true;
            }
        }
        return false;
    }
    
    /**
     * Guardar configuración actual en archivo
     */
    public static function saveToFile($filePath) {
        $data = [
            'profile' => self::$activeProfile,
            'custom_config' => self::$customConfig
        ];
        
        return file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT)) !== false;
    }
}

// Cargar configuración persistente si existe
$configFile = __DIR__ . '/current-config.json';
ApiConfig::loadFromFile($configFile);

?>
