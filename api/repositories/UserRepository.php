<?php
/**
 * UserRepository Simple - Funciones básicas de acceso a datos
 * Solo las operaciones esenciales para usuarios
 */

require_once __DIR__ . '/../models/User.php';

class UserRepository {
    private $conn;
    private $table_name = "users";
    
    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }
    
    /**
     * Buscar usuario por email
     */
    public function findByEmail($email) {
        try {
            $query = "SELECT id, email, first_name, last_name, password_hash, 
                             island, city, user_type, email_verified
                      FROM {$this->table_name} WHERE email = :email";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":email", $email, PDO::PARAM_STR);
            $stmt->execute();
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? new User($row) : null;
        } catch (PDOException $e) {
            error_log("Error finding user by email: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Buscar usuario por ID
     */
    public function findById($id) {
        try {
            $query = "SELECT id, email, first_name, last_name, password_hash, 
                             island, city, user_type, email_verified, created_at
                      FROM {$this->table_name} WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? new User($row) : null;
        } catch (PDOException $e) {
            error_log("Error finding user by ID: " . $e->getMessage());
            return null;
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
            return false;
        }
    }
    
    /**
     * Crear nuevo usuario
     */
    public function create(User $user) {
        try {
            if ($this->emailExists($user->email)) {
                throw new Exception("El email ya está registrado");
            }
            
            $query = "INSERT INTO {$this->table_name} 
                      (email, first_name, last_name, password_hash, island, city, 
                       user_type, email_verified, created_at, updated_at) 
                      VALUES 
                      (:email, :first_name, :last_name, :password_hash, :island, :city,
                       :user_type, :email_verified, :created_at, :updated_at)";
            
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(":email", $user->email, PDO::PARAM_STR);
            $stmt->bindParam(":first_name", $user->firstName, PDO::PARAM_STR);
            $stmt->bindParam(":last_name", $user->lastName, PDO::PARAM_STR);
            $stmt->bindParam(":password_hash", $user->passwordHash, PDO::PARAM_STR);
            $stmt->bindParam(":island", $user->island, PDO::PARAM_STR);
            $stmt->bindParam(":city", $user->city, PDO::PARAM_STR);
            $stmt->bindParam(":user_type", $user->userType, PDO::PARAM_STR);
            
            $emailVerified = $user->emailVerified ? 1 : 0;
            $stmt->bindParam(":email_verified", $emailVerified, PDO::PARAM_INT);
            
            $createdAt = date('Y-m-d H:i:s');
            $stmt->bindParam(":created_at", $createdAt, PDO::PARAM_STR);
            $stmt->bindParam(":updated_at", $createdAt, PDO::PARAM_STR);
            
            if ($stmt->execute()) {
                $user->id = $this->conn->lastInsertId();
                $user->createdAt = $createdAt;
                $user->updatedAt = $createdAt;
                return $user;
            }
            
            throw new Exception("Error insertando usuario");
        } catch (PDOException $e) {
            error_log("Error creating user: " . $e->getMessage());
            throw new Exception("Error creando usuario");
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
            $stmt->bindParam(":updated_at", date('Y-m-d H:i:s'), PDO::PARAM_STR);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error marking email as verified: " . $e->getMessage());
            return false;
        }
    }
}
?>
