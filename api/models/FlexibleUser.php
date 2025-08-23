<?php
/**
 * Modelo User Flexible - Compatible con múltiples configuraciones
 * Se adapta dinámicamente según la configuración de API activa
 */

require_once __DIR__ . '/../config/api-config.php';

class FlexibleUser {
    // Propiedades principales siempre disponibles
    public $id;
    public $email;
    public $passwordHash;
    public $createdAt;
    public $updatedAt;
    
    // Propiedades opcionales que pueden estar presentes
    public $firstName;
    public $lastName;
    public $phoneNumber;
    public $island;
    public $city;
    public $userType;
    public $emailVerified;
    public $profileImage;
    public $about;
    public $accountLocked;
    public $failedLoginAttempts;
    public $lastSuccessfulLogin;
    public $lastFailedLogin;
    
    private $validationErrors = [];
    private $dynamicProperties = [];
    
    /**
     * Constructor - Inicializar desde array de datos
     */
    public function __construct($data = []) {
        if (!empty($data)) {
            $this->fillFromArray($data);
        }
    }
    
    /**
     * Llenar propiedades desde array de datos con compatibilidad de formatos
     */
    public function fillFromArray($data) {
        // Campos principales (múltiples formatos para compatibilidad)
        $this->id = $data['id'] ?? $data['Id'] ?? null;
        $this->email = $data['email'] ?? $data['Email'] ?? null;
        $this->passwordHash = $data['password_hash'] ?? $data['passwordHash'] ?? $data['PasswordHash'] ?? null;
        
        // Timestamps
        $this->createdAt = $data['created_at'] ?? $data['createdAt'] ?? $data['CreatedAt'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? $data['updatedAt'] ?? $data['UpdatedAt'] ?? null;
        
        // Campos opcionales con compatibilidad de naming
        $this->firstName = $data['first_name'] ?? $data['firstName'] ?? $data['FirstName'] ?? null;
        $this->lastName = $data['last_name'] ?? $data['lastName'] ?? $data['LastName'] ?? null;
        $this->phoneNumber = $data['phone_number'] ?? $data['phoneNumber'] ?? $data['PhoneNumber'] ?? null;
        $this->island = $data['island'] ?? $data['Island'] ?? null;
        $this->city = $data['city'] ?? $data['City'] ?? null;
        $this->userType = $data['user_type'] ?? $data['userType'] ?? $data['UserType'] ?? 'individual';
        $this->emailVerified = isset($data['email_verified']) ? (bool)$data['email_verified'] : 
                              (isset($data['emailVerified']) ? (bool)$data['emailVerified'] : 
                              (isset($data['EmailVerified']) ? (bool)$data['EmailVerified'] : false));
        $this->profileImage = $data['profile_image'] ?? $data['profileImage'] ?? $data['ProfileImage'] ?? null;
        $this->about = $data['about'] ?? $data['About'] ?? null;
        $this->accountLocked = isset($data['account_locked']) ? (bool)$data['account_locked'] : 
                              (isset($data['accountLocked']) ? (bool)$data['accountLocked'] : 
                              (isset($data['AccountLocked']) ? (bool)$data['AccountLocked'] : false));
        $this->failedLoginAttempts = (int)($data['failed_login_attempts'] ?? $data['failedLoginAttempts'] ?? 0);
        $this->lastSuccessfulLogin = $data['last_successful_login'] ?? $data['lastSuccessfulLogin'] ?? null;
        $this->lastFailedLogin = $data['last_failed_login'] ?? $data['lastFailedLogin'] ?? null;
        
        // Almacenar propiedades dinámicas adicionales
        $knownProperties = [
            'id', 'email', 'password_hash', 'passwordHash', 'PasswordHash',
            'created_at', 'createdAt', 'CreatedAt', 'updated_at', 'updatedAt', 'UpdatedAt',
            'first_name', 'firstName', 'FirstName', 'last_name', 'lastName', 'LastName',
            'phone_number', 'phoneNumber', 'PhoneNumber', 'island', 'Island', 'city', 'City',
            'user_type', 'userType', 'UserType', 'email_verified', 'emailVerified', 'EmailVerified',
            'profile_image', 'profileImage', 'ProfileImage', 'about', 'About',
            'account_locked', 'accountLocked', 'AccountLocked', 'failed_login_attempts', 
            'failedLoginAttempts', 'last_successful_login', 'lastSuccessfulLogin',
            'last_failed_login', 'lastFailedLogin'
        ];
        
        foreach ($data as $key => $value) {
            if (!in_array($key, $knownProperties)) {
                $this->dynamicProperties[$key] = $value;
            }
        }
    }
    
    /**
     * Validar según configuración activa
     */
    public function isValid($includePassword = false) {
        $this->validationErrors = [];
        $config = ApiConfig::getConfig();
        
        // Validar campos requeridos según configuración
        $requiredFields = $config['required_fields'] ?? [];
        
        foreach ($requiredFields as $field) {
            $value = $this->getFieldValue($field);
            if (empty($value)) {
                $this->validationErrors[] = "Campo requerido faltante: {$field}";
            }
        }
        
        // Validaciones específicas
        if ($this->email !== null) {
            if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
                $this->validationErrors[] = 'Email no tiene formato válido';
            }
        }
        
        if ($includePassword && empty($this->passwordHash)) {
            $this->validationErrors[] = 'Contraseña es requerida';
        }
        
        // Validaciones adicionales según el tipo de validación configurado
        $validationType = $config['user_validation'] ?? 'standard';
        $this->performSpecificValidation($validationType);
        
        return empty($this->validationErrors);
    }
    
    /**
     * Realizar validaciones específicas según el tipo
     */
    private function performSpecificValidation($type) {
        switch ($type) {
            case 'basic':
                // Solo email y password
                break;
                
            case 'standard':
                // Validar nombres si están presentes y son requeridos
                if (ApiConfig::isFieldRequired('firstName') && strlen(trim($this->firstName ?? '')) < 2) {
                    $this->validationErrors[] = 'Nombre debe tener al menos 2 caracteres';
                }
                if (ApiConfig::isFieldRequired('lastName') && strlen(trim($this->lastName ?? '')) < 2) {
                    $this->validationErrors[] = 'Apellido debe tener al menos 2 caracteres';
                }
                break;
                
            case 'complete':
                // Validaciones completas
                if (ApiConfig::isFieldRequired('firstName') && strlen(trim($this->firstName ?? '')) < 2) {
                    $this->validationErrors[] = 'Nombre debe tener al menos 2 caracteres';
                }
                if (ApiConfig::isFieldRequired('lastName') && strlen(trim($this->lastName ?? '')) < 2) {
                    $this->validationErrors[] = 'Apellido debe tener al menos 2 caracteres';
                }
                if ($this->phoneNumber && !preg_match('/^[\+]?[0-9\s\-\(\)]+$/', $this->phoneNumber)) {
                    $this->validationErrors[] = 'Formato de teléfono inválido';
                }
                break;
                
            case 'custom':
                // Validaciones personalizadas pueden agregarse aquí
                break;
        }
    }
    
    /**
     * Obtener valor de un campo por nombre
     */
    private function getFieldValue($fieldName) {
        switch ($fieldName) {
            case 'email': return $this->email;
            case 'password': return $this->passwordHash;
            case 'firstName': return $this->firstName;
            case 'lastName': return $this->lastName;
            case 'phoneNumber': return $this->phoneNumber;
            case 'island': return $this->island;
            case 'city': return $this->city;
            case 'userType': return $this->userType;
            case 'about': return $this->about;
            default:
                return $this->dynamicProperties[$fieldName] ?? null;
        }
    }
    
    /**
     * Establecer valor de un campo por nombre
     */
    public function setFieldValue($fieldName, $value) {
        switch ($fieldName) {
            case 'email': $this->email = $value; break;
            case 'password': $this->passwordHash = $value; break;
            case 'firstName': $this->firstName = $value; break;
            case 'lastName': $this->lastName = $value; break;
            case 'phoneNumber': $this->phoneNumber = $value; break;
            case 'island': $this->island = $value; break;
            case 'city': $this->city = $value; break;
            case 'userType': $this->userType = $value; break;
            case 'about': $this->about = $value; break;
            default:
                $this->dynamicProperties[$fieldName] = $value;
        }
    }
    
    /**
     * Obtener errores de validación
     */
    public function getValidationErrors() {
        return $this->validationErrors;
    }
    
    /**
     * Convertir a array según configuración activa
     */
    public function toArray($includePassword = false) {
        $result = [];
        $profileFields = ApiConfig::getProfileFields();
        
        // Si no hay campos específicos, usar campos básicos
        if (empty($profileFields)) {
            $profileFields = ['id', 'email'];
            if (ApiConfig::isFieldValid('firstName')) $profileFields[] = 'firstName';
            if (ApiConfig::isFieldValid('lastName')) $profileFields[] = 'lastName';
        }
        
        // Siempre incluir id
        if ($this->id !== null) {
            $result['id'] = (int)$this->id;
        }
        
        foreach ($profileFields as $field) {
            $value = $this->getFieldValue($field);
            if ($value !== null) {
                $result[$field] = $value;
            }
        }
        
        // Incluir verificación de email si está habilitada
        if (ApiConfig::isEmailVerificationEnabled()) {
            $result['emailVerified'] = $this->emailVerified;
        }
        
        // Incluir password hash solo si se solicita explícitamente
        if ($includePassword && $this->passwordHash !== null) {
            $result['passwordHash'] = $this->passwordHash;
        }
        
        // Incluir timestamps si están disponibles
        if ($this->createdAt !== null) {
            $result['createdAt'] = $this->createdAt;
        }
        if ($this->updatedAt !== null) {
            $result['updatedAt'] = $this->updatedAt;
        }
        
        return $result;
    }
    
    /**
     * Convertir a array para base de datos
     */
    public function toDatabaseArray() {
        $result = [];
        
        if ($this->email !== null) $result['email'] = $this->email;
        if ($this->passwordHash !== null) $result['password_hash'] = $this->passwordHash;
        if ($this->firstName !== null) $result['first_name'] = $this->firstName;
        if ($this->lastName !== null) $result['last_name'] = $this->lastName;
        if ($this->phoneNumber !== null) $result['phone_number'] = $this->phoneNumber;
        if ($this->island !== null) $result['island'] = $this->island;
        if ($this->city !== null) $result['city'] = $this->city;
        if ($this->userType !== null) $result['user_type'] = $this->userType;
        if ($this->emailVerified !== null) $result['email_verified'] = $this->emailVerified ? 1 : 0;
        if ($this->profileImage !== null) $result['profile_image'] = $this->profileImage;
        if ($this->about !== null) $result['about'] = $this->about;
        if ($this->accountLocked !== null) $result['account_locked'] = $this->accountLocked ? 1 : 0;
        if ($this->failedLoginAttempts !== null) $result['failed_login_attempts'] = $this->failedLoginAttempts;
        if ($this->lastSuccessfulLogin !== null) $result['last_successful_login'] = $this->lastSuccessfulLogin;
        if ($this->lastFailedLogin !== null) $result['last_failed_login'] = $this->lastFailedLogin;
        if ($this->createdAt !== null) $result['created_at'] = $this->createdAt;
        if ($this->updatedAt !== null) $result['updated_at'] = $this->updatedAt;
        
        // Incluir propiedades dinámicas
        foreach ($this->dynamicProperties as $key => $value) {
            $result[$key] = $value;
        }
        
        return $result;
    }
    
    /**
     * Crear desde datos de entrada con filtrado automático
     */
    public static function fromInputData($data) {
        $filteredData = ApiConfig::filterData($data);
        return new self($filteredData);
    }
    
    /**
     * Aplicar valores por defecto según configuración
     */
    public function applyDefaults() {
        $config = ApiConfig::getConfig();
        
        // Aplicar valores por defecto comunes
        if ($this->userType === null && ApiConfig::isFieldValid('userType')) {
            $this->userType = 'individual';
        }
        
        if ($this->emailVerified === null && ApiConfig::isEmailVerificationEnabled()) {
            $this->emailVerified = false;
        }
        
        if ($this->accountLocked === null) {
            $this->accountLocked = false;
        }
        
        if ($this->failedLoginAttempts === null) {
            $this->failedLoginAttempts = 0;
        }
        
        // Timestamps
        $now = date('Y-m-d H:i:s');
        if ($this->createdAt === null) {
            $this->createdAt = $now;
        }
        $this->updatedAt = $now;
    }
}

?>
