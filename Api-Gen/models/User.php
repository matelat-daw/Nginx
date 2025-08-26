<?php
/**
 * Modelo User - Compatible con ASP.NET Identity
 * Representa un usuario del sistema con validación y mapeo automático
 */

class User {
    // Propiedades principales
    public $id;
    public $email;
    public $firstName;
    public $lastName;
    public $passwordHash;
    public $island;
    public $city;
    public $userType;
    public $emailVerified;
    public $createdAt;
    public $updatedAt;
    
    // Propiedades adicionales para compatibilidad
    public $phoneNumber;
    public $profileImage;
    public $about;
    public $accountLocked;
    public $failedLoginAttempts;
    public $lastSuccessfulLogin;
    public $lastFailedLogin;
    
    private $validationErrors = [];
    
    /**
     * Constructor - Inicializar desde array de datos
     */
    public function __construct($data = []) {
        if (!empty($data)) {
            $this->fillFromArray($data);
        }
    }
    
    /**
     * Llenar propiedades desde array de datos
     */
    public function fillFromArray($data) {
        // Campos principales (múltiples formatos para compatibilidad)
        $this->id = $data['id'] ?? $data['Id'] ?? null;
        $this->email = $data['email'] ?? $data['Email'] ?? null;
        $this->firstName = $data['first_name'] ?? $data['firstName'] ?? $data['FirstName'] ?? null;
        $this->lastName = $data['last_name'] ?? $data['lastName'] ?? $data['LastName'] ?? null;
        $this->passwordHash = $data['password_hash'] ?? $data['passwordHash'] ?? $data['PasswordHash'] ?? null;
        $this->island = $data['island'] ?? $data['Island'] ?? null;
        $this->city = $data['city'] ?? $data['City'] ?? null;
        $this->userType = $data['user_type'] ?? $data['userType'] ?? $data['UserType'] ?? 'individual';
        $this->emailVerified = (bool)($data['email_verified'] ?? $data['emailVerified'] ?? $data['EmailVerified'] ?? false);
        
        // Timestamps
        $this->createdAt = $data['created_at'] ?? $data['createdAt'] ?? $data['CreatedAt'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? $data['updatedAt'] ?? $data['UpdatedAt'] ?? null;
        
        // Campos adicionales
        $this->phoneNumber = $data['phone_number'] ?? $data['phoneNumber'] ?? $data['PhoneNumber'] ?? null;
        $this->profileImage = $data['profile_image'] ?? $data['profileImage'] ?? $data['ProfileImage'] ?? null;
        $this->about = $data['about'] ?? $data['About'] ?? null;
        $this->accountLocked = (bool)($data['account_locked'] ?? $data['accountLocked'] ?? $data['AccountLocked'] ?? false);
        $this->failedLoginAttempts = (int)($data['failed_login_attempts'] ?? $data['failedLoginAttempts'] ?? 0);
        $this->lastSuccessfulLogin = $data['last_successful_login'] ?? $data['lastSuccessfulLogin'] ?? null;
        $this->lastFailedLogin = $data['last_failed_login'] ?? $data['lastFailedLogin'] ?? null;
    }
    
    /**
     * Validar todos los datos del usuario
     */
    public function isValid($validatePassword = false) {
        $this->validationErrors = [];
        
        // Validar email
        if (empty($this->email)) {
            $this->validationErrors[] = 'Email es requerido';
        } elseif (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $this->validationErrors[] = 'Email no tiene formato válido';
        }
        
        // Validar nombres
        if (empty($this->firstName)) {
            $this->validationErrors[] = 'Nombre es requerido';
        } elseif (strlen($this->firstName) < 2) {
            $this->validationErrors[] = 'Nombre debe tener al menos 2 caracteres';
        }
        
        if (empty($this->lastName)) {
            $this->validationErrors[] = 'Apellido es requerido';
        } elseif (strlen($this->lastName) < 2) {
            $this->validationErrors[] = 'Apellido debe tener al menos 2 caracteres';
        }
        
        // Validar contraseña solo si se solicita
        if ($validatePassword && empty($this->passwordHash)) {
            $this->validationErrors[] = 'Contraseña es requerida';
        }
        
        // Validar isla
        $validIslands = ['Tenerife', 'Gran Canaria', 'Lanzarote', 'Fuerteventura', 
                        'La Palma', 'La Gomera', 'El Hierro', 'Otra'];
        if (!empty($this->island) && !in_array($this->island, $validIslands)) {
            $this->validationErrors[] = 'Isla no válida';
        }
        
        // Validar tipo de usuario
        $validUserTypes = ['individual', 'empresa', 'organizacion'];
        if (!empty($this->userType) && !in_array($this->userType, $validUserTypes)) {
            $this->validationErrors[] = 'Tipo de usuario no válido';
        }
        
        // Validar email único (esto se debería hacer en el repository)
        // Lo dejamos como placeholder para implementar en UserRepository
        
        return empty($this->validationErrors);
    }
    
    /**
     * Obtener errores de validación
     */
    public function getValidationErrors() {
        return $this->validationErrors;
    }
    
    /**
     * Sanitizar datos de entrada
     */
    public function sanitize() {
        $this->email = filter_var($this->email, FILTER_SANITIZE_EMAIL);
        $this->email = strtolower(trim($this->email));
        
        $this->firstName = trim(ucfirst(strtolower($this->firstName)));
        $this->lastName = trim(ucfirst(strtolower($this->lastName)));
        
        if ($this->city) {
            $this->city = trim(ucwords(strtolower($this->city)));
        }
        
        if ($this->about) {
            $this->about = trim($this->about);
        }
    }
    
    /**
     * Convertir a array para JSON (sin datos sensibles)
     */
    public function toArray($includeSecure = false) {
        $userData = [
            'id' => $this->id,
            'email' => $this->email,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'island' => $this->island,
            'city' => $this->city,
            'userType' => $this->userType,
            'emailVerified' => $this->emailVerified,
            'phoneNumber' => $this->phoneNumber,
            'profileImage' => $this->profileImage,
            'about' => $this->about,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt
        ];
        
        // Incluir campos seguros solo si se solicita (para operaciones internas)
        if ($includeSecure) {
            $userData['passwordHash'] = $this->passwordHash;
            $userData['accountLocked'] = $this->accountLocked;
            $userData['failedLoginAttempts'] = $this->failedLoginAttempts;
            $userData['lastSuccessfulLogin'] = $this->lastSuccessfulLogin;
            $userData['lastFailedLogin'] = $this->lastFailedLogin;
        }
        
        return $userData;
    }
    
    /**
     * Convertir a array para base de datos
     */
    public function toDatabaseArray() {
        return [
            'email' => $this->email,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'password_hash' => $this->passwordHash,
            'island' => $this->island,
            'city' => $this->city,
            'user_type' => $this->userType,
            'email_verified' => $this->emailVerified ? 1 : 0,
            'phone_number' => $this->phoneNumber,
            'profile_image' => $this->profileImage,
            'about' => $this->about,
            'account_locked' => $this->accountLocked ? 1 : 0,
            'failed_login_attempts' => $this->failedLoginAttempts,
            'updated_at' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Obtener nombre completo
     */
    public function getFullName() {
        return trim($this->firstName . ' ' . $this->lastName);
    }
    
    /**
     * Verificar si el usuario puede hacer login
     */
    public function canLogin() {
        if ($this->accountLocked) {
            return false;
        }
        
        if ($this->failedLoginAttempts >= 5) {
            return false;
        }
        
        // Verificar email si es requerido
        if (defined('REQUIRE_EMAIL_VERIFICATION') && REQUIRE_EMAIL_VERIFICATION && !$this->emailVerified) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Marcar email como verificado
     */
    public function markEmailAsVerified() {
        $this->emailVerified = true;
        $this->updatedAt = date('Y-m-d H:i:s');
    }
    
    /**
     * Incrementar intentos fallidos
     */
    public function incrementFailedAttempts() {
        $this->failedLoginAttempts++;
        $this->lastFailedLogin = date('Y-m-d H:i:s');
        
        // Bloquear cuenta después de 5 intentos
        if ($this->failedLoginAttempts >= 5) {
            $this->accountLocked = true;
        }
        
        $this->updatedAt = date('Y-m-d H:i:s');
    }
    
    /**
     * Limpiar intentos fallidos (login exitoso)
     */
    public function clearFailedAttempts() {
        $this->failedLoginAttempts = 0;
        $this->lastSuccessfulLogin = date('Y-m-d H:i:s');
        $this->updatedAt = date('Y-m-d H:i:s');
    }
    
    /**
     * Establecer nueva contraseña
     */
    public function setPassword($plainPassword) {
        require_once __DIR__ . '/../services/AuthService.php';
        $this->passwordHash = AuthService::hashPassword($plainPassword);
        $this->updatedAt = date('Y-m-d H:i:s');
    }
}
?>
