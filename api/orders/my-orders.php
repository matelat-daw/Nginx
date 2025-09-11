<?php
/**
 * API Endpoint: Obtener Pedidos del Usuario
 * GET /api/orders/my-orders.php
 */

// Incluir configuración
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../jwt.php';

// Headers CORS
setCorsHeaders();

// Manejar preflight requests
handlePreflight();

// Solo permitir método GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(null, 405, 'Método no permitido');
}

try {
    // Usar la función centralizada para obtener conexión DB
    $pdo = getDBConnection();
    
    // Verificar autenticación usando JWT en cookie
    $jwt = $_COOKIE[COOKIE_NAME] ?? null;
    
    if (!$jwt) {
        jsonResponse(null, 401, 'Usuario no autenticado');
    }
    
    // Verificar JWT usando la función correcta
    $userData = JWTSimple::validateToken($jwt);
    if (!$userData) {
        jsonResponse(null, 401, 'Token inválido o expirado');
    }
    
    $userId = $userData['user_id'];
    
    // Parámetros de consulta
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;
    $status = $_GET['status'] ?? null;
    
    // Construir query para obtener pedidos del usuario
    $sql = "SELECT * FROM orders WHERE user_id = :user_id";
    $params = ['user_id' => $userId];
    
    if ($status) {
        $sql .= " AND status = :status";
        $params['status'] = $status;
    }
    
    $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($sql);
    
    // Bind parámetros
    foreach ($params as $key => $value) {
        $stmt->bindValue(':' . $key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $orders = $stmt->fetchAll();
    
    // Si no hay pedidos, crear tabla de ejemplo
    if (empty($orders)) {
        // Verificar si la tabla existe
        $checkTable = $pdo->query("SHOW TABLES LIKE 'orders'");
        if ($checkTable->rowCount() === 0) {
            // Crear tabla de pedidos si no existe
            $createTable = "
                CREATE TABLE orders (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    status ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
                    payment_method VARCHAR(50),
                    shipping_address TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id)
                )
            ";
            $pdo->exec($createTable);
            
            // Crear algunos pedidos de ejemplo para el usuario
            $insertSample = "
                INSERT INTO orders (user_id, total, status, payment_method, shipping_address) VALUES
                (:user_id, 45.99, 'delivered', 'bizum', 'C/ Ejemplo 123, Las Palmas de Gran Canaria'),
                (:user_id, 28.50, 'shipped', 'transferencia', 'C/ Ejemplo 123, Las Palmas de Gran Canaria'),
                (:user_id, 67.25, 'pending', 'tarjeta', 'C/ Ejemplo 123, Las Palmas de Gran Canaria')
            ";
            $stmt = $pdo->prepare($insertSample);
            $stmt->execute(['user_id' => $userId]);
            
            // Volver a cargar los pedidos
            $stmt = $pdo->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue(':' . $key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $orders = $stmt->fetchAll();
        }
    }
    
    // Preparar respuesta
    jsonResponse([
        'orders' => $orders,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total_items' => count($orders),
            'has_more' => count($orders) === $limit
        ]
    ], 200, 'Pedidos cargados correctamente');
    
} catch (PDOException $e) {
    logMessage('ERROR', "Database error in my-orders: " . $e->getMessage());
    
    if (DEBUG_MODE) {
        jsonResponse(null, 500, 'Error de base de datos: ' . $e->getMessage());
    } else {
        jsonResponse(null, 500, 'Error de base de datos');
    }
} catch (Exception $e) {
    logMessage('ERROR', "Error in my-orders: " . $e->getMessage());
    
    if (DEBUG_MODE) {
        jsonResponse(null, 500, 'Error interno: ' . $e->getMessage());
    } else {
        jsonResponse(null, 500, 'Error interno del servidor');
    }
}
