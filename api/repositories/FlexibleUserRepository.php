<?php
/**
 * FlexibleUserRepository - Repositorio adaptable a múltiples configuraciones
 * Se adapta dinámicamente a diferentes esquemas de base de datos
 */

require_once __DIR__ . '/../models/FlexibleUser.php';
require_once __DIR__ . '/../config/api-config.php';

class FlexibleUserRepository {
    private $conn;
    private $tableName;
    
    // Tablas disponibles para búsqueda automática
    private $availableTables = ['users', 'ecc_users', 'user'];
    
    public function __construct($dbConnection, $tableName = null) {
        $this->conn = $dbConnection;
        $this->tableName = $tableName ?? $this->detectUserTable();
    }
    
    /**
     * Detectar automáticamente la tabla de usuarios disponible
     */
    private function detectUserTable() {
        foreach ($this->availableTables as $table) {
            try {
                $stmt = $this->conn->prepare("SHOW TABLES LIKE ?");
                $stmt->execute([$table]);
                if ($stmt->rowCount() > 0) {
                    return $table;
                }
            } catch (PDOException $e) {
                // Continuar con la siguiente tabla
                continue;
            }
        }
        
        // Por defecto, usar users
        return 'users';
    }
    
    /**
     * Establecer tabla específica
     */
    public function setTable($tableName) {
        $this->tableName = $tableName;
    }
    
    /**
     * Obtener la tabla actual
     */
    public function getTable() {
        return $this->tableName;
    }
    
    /**
     * Buscar usuario por email en múltiples tablas si es necesario
     */
    public function findByEmail($email) {
        // Primero intentar en la tabla configurada
        $user = $this->findByEmailInTable($email, $this->tableName);
        
        // Si no se encuentra y estamos usando users, intentar en tabla legacy
        if (!$user && $this->tableName === 'users') {
            $user = $this->findByEmailInTable($email, 'user');
        }
        
        return $user;
    }
    
    /**
     * Buscar usuario por email en una tabla específica
     */
    private function findByEmailInTable($email, $tableName) {
        try {
            // Construir query dinámicamente según los campos disponibles
            $fields = $this->getAvailableFields($tableName);
            $selectFields = implode(', ', $fields);
            
            $query = "SELECT {$selectFields} FROM {$tableName} WHERE email = ? LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$email]);
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? new FlexibleUser($row) : null;
        } catch (PDOException $e) {
            error_log("Error finding user by email in {$tableName}: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Buscar usuario por ID
     */
    public function findById($id) {
        try {
            $fields = $this->getAvailableFields($this->tableName);
            $selectFields = implode(', ', $fields);
            
            $query = "SELECT {$selectFields} FROM {$this->tableName} WHERE id = ? LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? new FlexibleUser($row) : null;
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
            $query = "SELECT COUNT(*) as count FROM {$this->tableName} WHERE email = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$email]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 0;
        } catch (PDOException $e) {
            error_log("Error checking email exists: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Crear nuevo usuario adaptándose a la tabla disponible
     */
    public function create(FlexibleUser $user) {
        try {
            if ($this->emailExists($user->email)) {
                throw new Exception("El email ya está registrado");
            }
            
            $user->applyDefaults();
            $data = $user->toDatabaseArray();
            
            // Filtrar campos según lo que esté disponible en la tabla
            $availableFields = $this->getAvailableFields($this->tableName);
            $insertData = [];
            
            foreach ($data as $key => $value) {
                if (in_array($key, $availableFields)) {
                    $insertData[$key] = $value;
                }
            }
            
            if (empty($insertData)) {
                throw new Exception("No hay campos válidos para insertar");
            }
            
            $fields = array_keys($insertData);
            $placeholders = array_fill(0, count($fields), '?');
            
            $query = "INSERT INTO {$this->tableName} (" . 
                     implode(', ', $fields) . 
                     ") VALUES (" . 
                     implode(', ', $placeholders) . ")";
            
            $stmt = $this->conn->prepare($query);
            $success = $stmt->execute(array_values($insertData));
            
            if ($success) {
                $user->id = $this->conn->lastInsertId();
                return $user;
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Error creating user: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Actualizar usuario existente
     */
    public function update(FlexibleUser $user) {
        try {
            if (!$user->id) {
                throw new Exception("ID de usuario requerido para actualización");
            }
            
            $user->updatedAt = date('Y-m-d H:i:s');
            $data = $user->toDatabaseArray();
            
            // Filtrar campos según lo que esté disponible en la tabla
            $availableFields = $this->getAvailableFields($this->tableName);
            $updateData = [];
            
            foreach ($data as $key => $value) {
                if (in_array($key, $availableFields) && $key !== 'id') {
                    $updateData[$key] = $value;
                }
            }
            
            if (empty($updateData)) {
                throw new Exception("No hay campos válidos para actualizar");
            }
            
            $setClause = implode(' = ?, ', array_keys($updateData)) . ' = ?';
            $query = "UPDATE {$this->tableName} SET {$setClause} WHERE id = ?";
            
            $values = array_values($updateData);
            $values[] = $user->id;
            
            $stmt = $this->conn->prepare($query);
            return $stmt->execute($values);
        } catch (Exception $e) {
            error_log("Error updating user: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Eliminar usuario
     */
    public function delete($id) {
        try {
            $query = "DELETE FROM {$this->tableName} WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error deleting user: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Obtener campos disponibles en la tabla
     */
    private function getAvailableFields($tableName) {
        static $fieldsCache = [];
        
        if (isset($fieldsCache[$tableName])) {
            return $fieldsCache[$tableName];
        }
        
        try {
            $query = "DESCRIBE {$tableName}";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $fields = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $fields[] = $row['Field'];
            }
            
            $fieldsCache[$tableName] = $fields;
            return $fields;
        } catch (PDOException $e) {
            error_log("Error getting table fields for {$tableName}: " . $e->getMessage());
            
            // Retornar campos básicos como fallback
            return ['id', 'email', 'password_hash', 'created_at', 'updated_at'];
        }
    }
    
    /**
     * Obtener información sobre la estructura de la tabla
     */
    public function getTableInfo() {
        try {
            $fields = $this->getAvailableFields($this->tableName);
            
            return [
                'table' => $this->tableName,
                'fields' => $fields,
                'total_fields' => count($fields),
                'has_names' => in_array('first_name', $fields) || in_array('firstName', $fields),
                'has_profile' => in_array('profile_image', $fields) || in_array('profileImage', $fields),
                'has_location' => in_array('island', $fields) && in_array('city', $fields),
                'has_verification' => in_array('email_verified', $fields) || in_array('emailVerified', $fields)
            ];
        } catch (Exception $e) {
            error_log("Error getting table info: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Verificar si la tabla tiene un campo específico
     */
    public function hasField($fieldName) {
        $fields = $this->getAvailableFields($this->tableName);
        return in_array($fieldName, $fields);
    }
    
    /**
     * Listar usuarios con paginación (opcional)
     */
    public function findAll($limit = null, $offset = 0) {
        try {
            $fields = $this->getAvailableFields($this->tableName);
            $selectFields = implode(', ', $fields);
            
            $query = "SELECT {$selectFields} FROM {$this->tableName}";
            
            if ($limit) {
                $query .= " LIMIT {$limit} OFFSET {$offset}";
            }
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $users = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $users[] = new FlexibleUser($row);
            }
            
            return $users;
        } catch (PDOException $e) {
            error_log("Error finding all users: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Contar total de usuarios
     */
    public function count() {
        try {
            $query = "SELECT COUNT(*) as total FROM {$this->tableName}";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['total'];
        } catch (PDOException $e) {
            error_log("Error counting users: " . $e->getMessage());
            return 0;
        }
    }
}

?>
