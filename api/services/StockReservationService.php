<?php
/**
 * =====================================================
 * SERVICIO DE GESTIÓN DE RESERVAS DE STOCK
 * Maneja reservas temporales, confirmaciones y liberaciones
 * =====================================================
 */

class StockReservationService {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Reserva stock temporalmente al agregar al carrito
     */
    public function reserveStock($userId, $productId, $quantity, $sessionToken) {
        try {
            $stmt = $this->pdo->prepare("CALL ReserveStock(?, ?, ?, ?, @success, @message)");
            $stmt->execute([$userId, $productId, $quantity, $sessionToken]);
            
            // Obtener el resultado del procedimiento
            $result = $this->pdo->query("SELECT @success as success, @message as message")->fetch(PDO::FETCH_ASSOC);
            
            return [
                'success' => (bool)$result['success'],
                'message' => $result['message']
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error al reservar stock: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Confirma la compra y convierte reserva temporal en venta definitiva
     */
    public function confirmPurchase($userId, $productId, $quantity) {
        try {
            $stmt = $this->pdo->prepare("CALL ConfirmPurchase(?, ?, ?, @success, @message)");
            $stmt->execute([$userId, $productId, $quantity]);
            
            $result = $this->pdo->query("SELECT @success as success, @message as message")->fetch(PDO::FETCH_ASSOC);
            
            return [
                'success' => (bool)$result['success'],
                'message' => $result['message']
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error al confirmar compra: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Libera todas las reservas de un usuario (logout, token expirado, etc.)
     */
    public function releaseUserReservations($userId, $sessionToken = null) {
        try {
            $stmt = $this->pdo->prepare("CALL ReleaseUserReservations(?, ?)");
            $stmt->execute([$userId, $sessionToken]);
            
            return [
                'success' => true,
                'message' => 'Reservas liberadas correctamente'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error al liberar reservas: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtiene las reservas activas de un usuario
     */
    public function getUserReservations($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    sr.id,
                    sr.product_id,
                    sr.quantity_reserved,
                    sr.expires_at,
                    p.title as product_title,
                    p.price,
                    p.stock_available,
                    TIMESTAMPDIFF(MINUTE, NOW(), sr.expires_at) as minutes_remaining
                FROM stock_reservations sr
                JOIN products p ON sr.product_id = p.id
                WHERE sr.user_id = ? 
                AND sr.status = 'active'
                AND sr.expires_at > NOW()
                ORDER BY sr.created_at DESC
            ");
            $stmt->execute([$userId]);
            
            return [
                'success' => true,
                'reservations' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error al obtener reservas: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Verifica si hay stock disponible (sin reservar)
     */
    public function checkAvailableStock($productId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    stock_quantity,
                    stock_available,
                    (stock_quantity - stock_available) as reserved_stock
                FROM products 
                WHERE id = ?
            ");
            $stmt->execute([$productId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                return [
                    'success' => false,
                    'message' => 'Producto no encontrado'
                ];
            }
            
            return [
                'success' => true,
                'stock_data' => $result
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error al verificar stock: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Extiende el tiempo de una reserva (renovar carrito)
     */
    public function extendReservation($userId, $productId, $additionalMinutes = 30) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE stock_reservations 
                SET expires_at = DATE_ADD(NOW(), INTERVAL ? MINUTE)
                WHERE user_id = ? 
                AND product_id = ? 
                AND status = 'active'
            ");
            $stmt->execute([$additionalMinutes, $userId, $productId]);
            
            if ($stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'Reserva extendida correctamente'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'No se encontró reserva activa para extender'
                ];
            }
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error al extender reserva: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Limpia manualmente las reservas expiradas
     */
    public function cleanupExpiredReservations() {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE stock_reservations 
                SET status = 'expired' 
                WHERE status = 'active' 
                AND expires_at < NOW()
            ");
            $stmt->execute();
            
            $expiredCount = $stmt->rowCount();
            
            return [
                'success' => true,
                'message' => "Se han marcado {$expiredCount} reservas como expiradas"
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error al limpiar reservas: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Procesa múltiples productos del carrito (para checkout completo)
     */
    public function processCartReservations($userId, $cartItems, $action = 'confirm') {
        $results = [];
        $this->pdo->beginTransaction();
        
        try {
            foreach ($cartItems as $item) {
                $productId = $item['product_id'];
                $quantity = $item['quantity'];
                
                if ($action === 'confirm') {
                    $result = $this->confirmPurchase($userId, $productId, $quantity);
                } elseif ($action === 'release') {
                    // Para liberar, solo necesitamos cambiar el status
                    $stmt = $this->pdo->prepare("
                        UPDATE stock_reservations 
                        SET status = 'cancelled'
                        WHERE user_id = ? AND product_id = ? AND status = 'active'
                    ");
                    $stmt->execute([$userId, $productId]);
                    $result = ['success' => true, 'message' => 'Reserva liberada'];
                }
                
                $results[] = [
                    'product_id' => $productId,
                    'result' => $result
                ];
                
                if (!$result['success']) {
                    $this->pdo->rollBack();
                    return [
                        'success' => false,
                        'message' => "Error en producto {$productId}: " . $result['message'],
                        'results' => $results
                    ];
                }
            }
            
            $this->pdo->commit();
            return [
                'success' => true,
                'message' => 'Todas las reservas procesadas correctamente',
                'results' => $results
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Error al procesar reservas del carrito: ' . $e->getMessage()
            ];
        }
    }
}

/**
 * =====================================================
 * FUNCIONES DE UTILIDAD PARA INTEGRACIÓN
 * =====================================================
 */

/**
 * Función para manejar reservas en tiempo real via AJAX
 */
function handleStockReservation() {
    header('Content-Type: application/json');
    
    // Verificar autenticación
    $authResult = verifyJWT();
    if (!$authResult['valid']) {
        http_response_code(401);
        echo json_encode(['error' => 'Token inválido o expirado']);
        return;
    }
    
    $userId = $authResult['user_id'];
    $sessionToken = $authResult['token'];
    
    // Validar datos de entrada
    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['action'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Acción no especificada']);
        return;
    }
    
    try {
        $pdo = getDBConnection();
        $stockService = new StockReservationService($pdo);
        $result = [];
        
        switch ($input['action']) {
            case 'reserve':
                if (!isset($input['product_id'], $input['quantity'])) {
                    throw new Exception('Datos incompletos para reservar');
                }
                $result = $stockService->reserveStock(
                    $userId, 
                    $input['product_id'], 
                    $input['quantity'], 
                    $sessionToken
                );
                break;
                
            case 'release_all':
                $result = $stockService->releaseUserReservations($userId, $sessionToken);
                break;
                
            case 'get_reservations':
                $result = $stockService->getUserReservations($userId);
                break;
                
            case 'check_stock':
                if (!isset($input['product_id'])) {
                    throw new Exception('ID de producto requerido');
                }
                $result = $stockService->checkAvailableStock($input['product_id']);
                break;
                
            case 'extend':
                if (!isset($input['product_id'])) {
                    throw new Exception('ID de producto requerido');
                }
                $result = $stockService->extendReservation($userId, $input['product_id']);
                break;
                
            default:
                throw new Exception('Acción no válida');
        }
        
        echo json_encode($result);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error del servidor: ' . $e->getMessage()
        ]);
    }
}

// Ejemplo de uso para auto-limpieza al verificar token
function checkTokenAndCleanReservations($token) {
    $authResult = verifyJWT($token);
    
    if (!$authResult['valid']) {
        // Token expirado, liberar todas las reservas asociadas
        try {
            $pdo = getDBConnection();
            $stockService = new StockReservationService($pdo);
            $stockService->releaseUserReservations(null, $token);
        } catch (Exception $e) {
            error_log("Error al liberar reservas de token expirado: " . $e->getMessage());
        }
    }
    
    return $authResult;
}

?>
