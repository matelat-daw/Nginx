<?php
/**
 * Modelo Login - Validación de datos de login
 * Maneja la validación y sanitización de credenciales de login
 */

class LoginModel {
    public $email;
    public $password;
    public $rememberMe;
    
    private $validationErrors = [];
    
    /**
     * Constructor
     */
    public function __construct($data = []) {
        if (!empty($data)) {
            $this->fillFromArray($data);
        }
    }
    
    /**
     * Llenar desde array de datos
     */
    public function fillFromArray($data) {
        $this->email = $data['email'] ?? '';
        $this->password = $data['password'] ?? '';
        $this->rememberMe = isset($data['rememberMe']) ? (bool)$data['rememberMe'] : false;
    }
    
    /**
     * Validar datos de login
     */
    public function isValid() {
        $this->validationErrors = [];
        
        // Validar email
        if (empty($this->email)) {
            $this->validationErrors[] = 'Email es requerido';
        } elseif (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $this->validationErrors[] = 'Email no tiene formato válido';
        } elseif (strlen($this->email) > 255) {
            $this->validationErrors[] = 'Email demasiado largo';
        }
        
        // Validar contraseña
        if (empty($this->password)) {
            $this->validationErrors[] = 'Contraseña es requerida';
        } elseif (strlen($this->password) < 6) {
            $this->validationErrors[] = 'Contraseña debe tener al menos 6 caracteres';
        } elseif (strlen($this->password) > 128) {
            $this->validationErrors[] = 'Contraseña demasiado larga';
        }
        
        return empty($this->validationErrors);
    }
    
    /**
     * Obtener errores de validación
     */
    public function getValidationErrors() {
        return $this->validationErrors;
    }
    
    /**
     * Sanitizar email
     */
    public function sanitizeEmail() {
        $this->email = filter_var($this->email, FILTER_SANITIZE_EMAIL);
        $this->email = strtolower(trim($this->email));
    }
    
    /**
     * Convertir a array
     */
    public function toArray() {
        return [
            'email' => $this->email,
            'rememberMe' => $this->rememberMe
            // No incluir password por seguridad
        ];
    }
}

/**
 * Modelo Register - Validación de datos de registro
 */
class RegisterModel {
    public $email;
    public $password;
    public $confirmPassword;
    public $firstName;
    public $lastName;
    public $island;
    public $city;
    public $userType;
    public $acceptTerms;
    
    private $validationErrors = [];
    
    /**
     * Constructor
     */
    public function __construct($data = []) {
        if (!empty($data)) {
            $this->fillFromArray($data);
        }
    }
    
    /**
     * Llenar desde array de datos
     */
    public function fillFromArray($data) {
        $this->email = $data['email'] ?? '';
        $this->password = $data['password'] ?? '';
        $this->confirmPassword = $data['confirmPassword'] ?? '';
        $this->firstName = $data['firstName'] ?? '';
        $this->lastName = $data['lastName'] ?? '';
        $this->island = $data['island'] ?? '';
        $this->city = $data['city'] ?? '';
        $this->userType = $data['userType'] ?? 'individual';
        $this->acceptTerms = isset($data['acceptTerms']) ? (bool)$data['acceptTerms'] : false;
    }
    
    /**
     * Validar datos de registro
     */
    public function isValid() {
        $this->validationErrors = [];
        
        // Validar email
        if (empty($this->email)) {
            $this->validationErrors[] = 'Email es requerido';
        } elseif (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $this->validationErrors[] = 'Email no tiene formato válido';
        } elseif (strlen($this->email) > 255) {
            $this->validationErrors[] = 'Email demasiado largo';
        }
        
        // Validar contraseña
        if (empty($this->password)) {
            $this->validationErrors[] = 'Contraseña es requerida';
        } elseif (strlen($this->password) < 6) {
            $this->validationErrors[] = 'Contraseña debe tener al menos 6 caracteres';
        } elseif (strlen($this->password) > 128) {
            $this->validationErrors[] = 'Contraseña demasiado larga';
        } elseif (!$this->isPasswordStrong($this->password)) {
            $this->validationErrors[] = 'Contraseña debe contener al menos una letra y un número';
        }
        
        // Validar confirmación de contraseña
        if (empty($this->confirmPassword)) {
            $this->validationErrors[] = 'Confirmación de contraseña es requerida';
        } elseif ($this->password !== $this->confirmPassword) {
            $this->validationErrors[] = 'Las contraseñas no coinciden';
        }
        
        // Validar nombres
        if (empty($this->firstName)) {
            $this->validationErrors[] = 'Nombre es requerido';
        } elseif (strlen($this->firstName) < 2) {
            $this->validationErrors[] = 'Nombre debe tener al menos 2 caracteres';
        } elseif (strlen($this->firstName) > 50) {
            $this->validationErrors[] = 'Nombre demasiado largo';
        }
        
        if (empty($this->lastName)) {
            $this->validationErrors[] = 'Apellido es requerido';
        } elseif (strlen($this->lastName) < 2) {
            $this->validationErrors[] = 'Apellido debe tener al menos 2 caracteres';
        } elseif (strlen($this->lastName) > 50) {
            $this->validationErrors[] = 'Apellido demasiado largo';
        }
        
        // Validar isla
        $validIslands = ['Tenerife', 'Gran Canaria', 'Lanzarote', 'Fuerteventura', 
                        'La Palma', 'La Gomera', 'El Hierro', 'Otra'];
        if (empty($this->island)) {
            $this->validationErrors[] = 'Isla es requerida';
        } elseif (!in_array($this->island, $validIslands)) {
            $this->validationErrors[] = 'Isla no válida';
        }
        
        // Validar tipo de usuario
        $validUserTypes = ['individual', 'empresa', 'organizacion'];
        if (!in_array($this->userType, $validUserTypes)) {
            $this->validationErrors[] = 'Tipo de usuario no válido';
        }
        
        // Validar aceptación de términos
        if (!$this->acceptTerms) {
            $this->validationErrors[] = 'Debe aceptar los términos y condiciones';
        }
        
        return empty($this->validationErrors);
    }
    
    /**
     * Verificar si la contraseña es fuerte
     */
    private function isPasswordStrong($password) {
        // Al menos una letra y un número
        return preg_match('/[A-Za-z]/', $password) && preg_match('/[0-9]/', $password);
    }
    
    /**
     * Obtener errores de validación
     */
    public function getValidationErrors() {
        return $this->validationErrors;
    }
    
    /**
     * Sanitizar datos
     */
    public function sanitize() {
        $this->email = filter_var($this->email, FILTER_SANITIZE_EMAIL);
        $this->email = strtolower(trim($this->email));
        
        $this->firstName = trim(ucfirst(strtolower($this->firstName)));
        $this->lastName = trim(ucfirst(strtolower($this->lastName)));
        
        if ($this->city) {
            $this->city = trim(ucwords(strtolower($this->city)));
        }
    }
    
    /**
     * Convertir a modelo User
     */
    public function toUser() {
        require_once __DIR__ . '/User.php';
        require_once __DIR__ . '/../services/AuthService.php';
        
        $user = new User();
        $user->email = $this->email;
        $user->firstName = $this->firstName;
        $user->lastName = $this->lastName;
        $user->island = $this->island;
        $user->city = $this->city;
        $user->userType = $this->userType;
        $user->emailVerified = false; // Por defecto no verificado
        $user->passwordHash = AuthService::hashPassword($this->password);
        $user->createdAt = date('Y-m-d H:i:s');
        $user->updatedAt = date('Y-m-d H:i:s');
        
        return $user;
    }
    
    /**
     * Convertir a array (sin contraseñas)
     */
    public function toArray() {
        return [
            'email' => $this->email,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'island' => $this->island,
            'city' => $this->city,
            'userType' => $this->userType,
            'acceptTerms' => $this->acceptTerms
        ];
    }
}
?>
