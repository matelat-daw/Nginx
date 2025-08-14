<?php
/**
 * UserRepository - Capa de acceso a datos para usuarios
 * Implementa el patrón Repository para abstracción de base de datos
 */

require_once __DIR__ . '/../models/User.php';

class UserRepository {
    private $conn;
    private $table_name = "ecc_users";
    
    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }
    
    /**
     * Buscar usuario por email
     */
    public function findByEmail($email, $fullProfile = false) {
        try {
            if ($fullProfile) {
                // Consulta completa para perfil
                $query = "SELECT id, email, first_name, last_name, password_hash, 
                                 island, city, user_type, email_verified, phone_number,
                                 profile_image, about, account_locked, failed_login_attempts,
                                 last_successful_login, last_failed_login, created_at, updated_at
                          FROM {$this->table_name} WHERE email = :email";
            } else {
                // Consulta básica para login
                $query = "SELECT id, email, first_name, last_name, password_hash, 
                                 island, city, user_type, email_verified, account_locked,
                                 failed_login_attempts
                          FROM {$this->table_name} WHERE email = :email";
            }
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":email", $email, PDO::PARAM_STR);
            $stmt->execute();
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($row) {
                return new User($row);
            }
            
            return null;
        } catch (PDOException $e) {
            error_log("Error finding user by email: " . $e->getMessage());
            throw new Exception("Error buscando usuario por email");
        }
    }
    
    /**
     * Buscar usuario por ID
     */
    public function findById($id) {
        try {
            $query = "SELECT id, email, first_name, last_name, password_hash, 
                             island, city, user_type, email_verified, phone_number,
                             profile_image, about, account_locked, failed_login_attempts,
                             last_successful_login, last_failed_login, created_at, updated_at
                      FROM {$this->table_name} WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($row) {
                return new User($row);
            }
            
            return null;
        } catch (PDOException $e) {
            error_log("Error finding user by ID: " . $e->getMessage());
            throw new Exception("Error buscando usuario por ID");
        }
    }
    
    /**
     * Verificar si el email ya existe
     */
    public function emailExists($email) {
        try {
            $query = "SELECT COUNT(*) as count FROM {$this->table_name} WHERE email = :email";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":email", $email, PDO::PARAM_STR);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 0;
        } catch (PDOException $e) {
            error_log("Error checking email exists: " . $e->getMessage());
            throw new Exception("Error verificando email");
        }
    }
    
    /**
     * Crear nuevo usuario
     */
    public function create(User $user) {
        try {
            // Verificar que el email no existe
            if ($this->emailExists($user->email)) {
                throw new Exception("El email ya está registrado");
            }
            
            $query = "INSERT INTO {$this->table_name} 
                      (email, first_name, last_name, password_hash, island, city, 
                       user_type, email_verified, phone_number, profile_image, about, 
                       created_at, updated_at) 
                      VALUES 
                      (:email, :first_name, :last_name, :password_hash, :island, :city,
                       :user_type, :email_verified, :phone_number, :profile_image, :about,
                       :created_at, :updated_at)";
            
            $stmt = $this->conn->prepare($query);
            
            // Bind parameters
            $stmt->bindParam(":email", $user->email, PDO::PARAM_STR);
            $stmt->bindParam(":first_name", $user->firstName, PDO::PARAM_STR);
            $stmt->bindParam(":last_name", $user->lastName, PDO::PARAM_STR);
            $stmt->bindParam(":password_hash", $user->passwordHash, PDO::PARAM_STR);
            $stmt->bindParam(":island", $user->island, PDO::PARAM_STR);
            $stmt->bindParam(":city", $user->city, PDO::PARAM_STR);
            $stmt->bindParam(":user_type", $user->userType, PDO::PARAM_STR);
            
            $emailVerified = $user->emailVerified ? 1 : 0;
            $stmt->bindParam(":email_verified", $emailVerified, PDO::PARAM_INT);
            $stmt->bindParam(":phone_number", $user->phoneNumber, PDO::PARAM_STR);
            $stmt->bindParam(":profile_image", $user->profileImage, PDO::PARAM_STR);
            $stmt->bindParam(":about", $user->about, PDO::PARAM_STR);
            
            $createdAt = date('Y-m-d H:i:s');
            $stmt->bindParam(":created_at", $createdAt, PDO::PARAM_STR);
            $stmt->bindParam(":updated_at", $createdAt, PDO::PARAM_STR);
            
            if ($stmt->execute()) {
                $user->id = $this->conn->lastInsertId();
                $user->createdAt = $createdAt;
                $user->updatedAt = $createdAt;
                return $user;
            } else {
                throw new Exception("Error insertando usuario en la base de datos");
            }
        } catch (PDOException $e) {
            error_log("Error creating user: " . $e->getMessage());
            
            // Manejar errores específicos
            if ($e->getCode() == 23000) { // Duplicate entry
                throw new Exception("El email ya está registrado");
            }
            
            throw new Exception("Error creando usuario");
        }
    }
    
    /**
     * Actualizar usuario
     */
    public function update(User $user) {
        try {
            $query = "UPDATE {$this->table_name} SET 
                      first_name = :first_name,
                      last_name = :last_name,
                      island = :island,
                      city = :city,
                      user_type = :user_type,
                      email_verified = :email_verified,
                      phone_number = :phone_number,
                      profile_image = :profile_image,
                      about = :about,
                      account_locked = :account_locked,
                      failed_login_attempts = :failed_login_attempts,
                      last_successful_login = :last_successful_login,
                      last_failed_login = :last_failed_login,
                      updated_at = :updated_at
                      WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            
            // Bind parameters
            $stmt->bindParam(":first_name", $user->firstName, PDO::PARAM_STR);
            $stmt->bindParam(":last_name", $user->lastName, PDO::PARAM_STR);
            $stmt->bindParam(":island", $user->island, PDO::PARAM_STR);
            $stmt->bindParam(":city", $user->city, PDO::PARAM_STR);
            $stmt->bindParam(":user_type", $user->userType, PDO::PARAM_STR);
            
            $emailVerified = $user->emailVerified ? 1 : 0;
            $accountLocked = $user->accountLocked ? 1 : 0;
            
            $stmt->bindParam(":email_verified", $emailVerified, PDO::PARAM_INT);
            $stmt->bindParam(":phone_number", $user->phoneNumber, PDO::PARAM_STR);
            $stmt->bindParam(":profile_image", $user->profileImage, PDO::PARAM_STR);
            $stmt->bindParam(":about", $user->about, PDO::PARAM_STR);
            $stmt->bindParam(":account_locked", $accountLocked, PDO::PARAM_INT);
            $stmt->bindParam(":failed_login_attempts", $user->failedLoginAttempts, PDO::PARAM_INT);
            $stmt->bindParam(":last_successful_login", $user->lastSuccessfulLogin, PDO::PARAM_STR);
            $stmt->bindParam(":last_failed_login", $user->lastFailedLogin, PDO::PARAM_STR);
            
            $updatedAt = date('Y-m-d H:i:s');
            $stmt->bindParam(":updated_at", $updatedAt, PDO::PARAM_STR);
            $stmt->bindParam(":id", $user->id, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                $user->updatedAt = $updatedAt;
                return true;
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Error updating user: " . $e->getMessage());
            throw new Exception("Error actualizando usuario");
        }
    }
    
    /**
     * Actualizar contraseña de usuario
     */
    public function updatePassword($userId, $newPasswordHash) {
        try {
            $query = "UPDATE {$this->table_name} SET 
                      password_hash = :password_hash, 
                      updated_at = :updated_at 
                      WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":password_hash", $newPasswordHash, PDO::PARAM_STR);
            $stmt->bindParam(":id", $userId, PDO::PARAM_INT);
            
            $updatedAt = date('Y-m-d H:i:s');
            $stmt->bindParam(":updated_at", $updatedAt, PDO::PARAM_STR);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error updating password: " . $e->getMessage());
            throw new Exception("Error actualizando contraseña");
        }
    }
    
    /**
     * Marcar email como verificado
     */
    public function markEmailAsVerified($userId) {
        try {
            $query = "UPDATE {$this->table_name} SET 
                      email_verified = 1, 
                      updated_at = :updated_at 
                      WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $userId, PDO::PARAM_INT);
            
            $updatedAt = date('Y-m-d H:i:s');
            $stmt->bindParam(":updated_at", $updatedAt, PDO::PARAM_STR);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error marking email as verified: " . $e->getMessage());
            throw new Exception("Error verificando email");
        }
    }
    
    /**
     * Incrementar intentos fallidos de login
     */
    public function recordFailedLogin($userId) {
        try {
            $query = "UPDATE {$this->table_name} SET 
                      failed_login_attempts = COALESCE(failed_login_attempts, 0) + 1,
                      last_failed_login = :last_failed_login,
                      account_locked = CASE 
                          WHEN COALESCE(failed_login_attempts, 0) + 1 >= 5 THEN 1 
                          ELSE account_locked 
                      END,
                      updated_at = :updated_at
                      WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $userId, PDO::PARAM_INT);
            
            $now = date('Y-m-d H:i:s');
            $stmt->bindParam(":last_failed_login", $now, PDO::PARAM_STR);
            $stmt->bindParam(":updated_at", $now, PDO::PARAM_STR);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error recording failed login: " . $e->getMessage());
            throw new Exception("Error registrando intento fallido");
        }
    }
    
    /**
     * Limpiar intentos fallidos después de login exitoso
     */
    public function clearFailedLogins($userId) {
        try {
            $query = "UPDATE {$this->table_name} SET 
                      failed_login_attempts = 0,
                      account_locked = 0,
                      last_successful_login = :last_successful_login,
                      updated_at = :updated_at
                      WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $userId, PDO::PARAM_INT);
            
            $now = date('Y-m-d H:i:s');
            $stmt->bindParam(":last_successful_login", $now, PDO::PARAM_STR);
            $stmt->bindParam(":updated_at", $now, PDO::PARAM_STR);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error clearing failed logins: " . $e->getMessage());
            throw new Exception("Error limpiando intentos fallidos");
        }
    }
    
    /**
     * Obtener estadísticas de usuarios
     */
    public function getStats() {
        try {
            $query = "SELECT 
                      COUNT(*) as total_users,
                      SUM(CASE WHEN email_verified = 1 THEN 1 ELSE 0 END) as verified_users,
                      SUM(CASE WHEN account_locked = 1 THEN 1 ELSE 0 END) as locked_users,
                      COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_users_30d
                      FROM {$this->table_name}";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting user stats: " . $e->getMessage());
            throw new Exception("Error obteniendo estadísticas");
        }
    }
    
    /**
     * Buscar usuarios (para admin)
     */
    public function search($criteria, $limit = 50, $offset = 0) {
        try {
            $whereClause = "";
            $params = [];
            
            if (!empty($criteria['email'])) {
                $whereClause .= " AND email LIKE :email";
                $params[':email'] = '%' . $criteria['email'] . '%';
            }
            
            if (!empty($criteria['name'])) {
                $whereClause .= " AND (first_name LIKE :name OR last_name LIKE :name)";
                $params[':name'] = '%' . $criteria['name'] . '%';
            }
            
            if (!empty($criteria['island'])) {
                $whereClause .= " AND island = :island";
                $params[':island'] = $criteria['island'];
            }
            
            if (!empty($criteria['user_type'])) {
                $whereClause .= " AND user_type = :user_type";
                $params[':user_type'] = $criteria['user_type'];
            }
            
            $query = "SELECT id, email, first_name, last_name, island, city, 
                             user_type, email_verified, account_locked, created_at
                      FROM {$this->table_name} 
                      WHERE 1=1 {$whereClause}
                      ORDER BY created_at DESC
                      LIMIT :limit OFFSET :offset";
            
            $stmt = $this->conn->prepare($query);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, PDO::PARAM_STR);
            }
            
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $users = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $users[] = new User($row);
            }
            
            return $users;
        } catch (PDOException $e) {
            error_log("Error searching users: " . $e->getMessage());
            throw new Exception("Error buscando usuarios");
        }
    }
}
?>
