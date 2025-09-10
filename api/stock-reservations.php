<?php
/**
 * =====================================================
 * API ENDPOINT - GESTIÓN DE RESERVAS DE STOCK
 * Maneja todas las operaciones de reserva de inventario
 * =====================================================
 */

require_once 'config.php';
require_once 'jwt.php';
require_once 'services/StockReservationService.php';

// Configurar CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

// Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

/**
 * Función principal para manejar todas las operaciones de reservas
 */
function handleStockReservations() {
    global $pdo;
    
    try {
        // Verificar autenticación JWT
        $authResult = verifyJWT();
        if (!$authResult['valid']) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => 'Token inválido o expirado',
                'code' => 'TOKEN_INVALID'
            ]);
            return;
        }
        
        $userId = $authResult['user_id'];
        $sessionToken = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        // Obtener datos de la solicitud
        $method = $_SERVER['REQUEST_METHOD'];
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        
        // Validar JSON
        if (json_last_error() !== JSON_ERROR_NONE && $method !== 'GET') {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'JSON inválido en la solicitud'
            ]);
            return;
        }
        
        // Crear instancia del servicio
        $stockService = new StockReservationService($pdo);
        $result = [];
        
        // Manejar diferentes métodos HTTP y acciones
        switch ($method) {
            case 'POST':
                $result = handlePostRequest($stockService, $userId, $sessionToken, $input);
                break;
                
            case 'GET':
                $result = handleGetRequest($stockService, $userId, $_GET);
                break;
                
            case 'PUT':
                $result = handlePutRequest($stockService, $userId, $input);
                break;
                
            case 'DELETE':
                $result = handleDeleteRequest($stockService, $userId, $sessionToken, $input);
                break;
                
            default:
                http_response_code(405);
                echo json_encode([
                    'success' => false,
                    'error' => 'Método HTTP no permitido'
                ]);
                return;
        }
        
        // Establecer código de respuesta HTTP
        if (!$result['success']) {
            http_response_code(400);
        }
        
        echo json_encode($result);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error interno del servidor',
            'message' => $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
        // Log del error
        error_log("Error en stock-reservations.php: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    }
}

/**
 * Manejar solicitudes POST (crear reservas, confirmar compras)
 */
function handlePostRequest($stockService, $userId, $sessionToken, $input) {
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'reserve':
            // Reservar stock al agregar al carrito
            if (!isset($input['product_id'], $input['quantity'])) {
                return [
                    'success' => false,
                    'error' => 'ID de producto y cantidad son requeridos'
                ];
            }
            
            // Validar cantidad
            if (!is_numeric($input['quantity']) || $input['quantity'] <= 0) {
                return [
                    'success' => false,
                    'error' => 'La cantidad debe ser un número positivo'
                ];
            }
            
            return $stockService->reserveStock(
                $userId,
                (int)$input['product_id'],
                (int)$input['quantity'],
                $sessionToken
            );
            
        case 'confirm_purchase':
            // Confirmar compra individual
            if (!isset($input['product_id'], $input['quantity'])) {
                return [
                    'success' => false,
                    'error' => 'ID de producto y cantidad son requeridos'
                ];
            }
            
            return $stockService->confirmPurchase(
                $userId,
                (int)$input['product_id'],
                (int)$input['quantity']
            );
            
        case 'confirm_cart':
            // Confirmar todo el carrito de compras
            if (!isset($input['cart_items']) || !is_array($input['cart_items'])) {
                return [
                    'success' => false,
                    'error' => 'Items del carrito requeridos'
                ];
            }
            
            return $stockService->processCartReservations(
                $userId,
                $input['cart_items'],
                'confirm'
            );
            
        case 'release_cart':
            // Liberar todo el carrito
            if (!isset($input['cart_items']) || !is_array($input['cart_items'])) {
                return [
                    'success' => false,
                    'error' => 'Items del carrito requeridos'
                ];
            }
            
            return $stockService->processCartReservations(
                $userId,
                $input['cart_items'],
                'release'
            );
            
        default:
            return [
                'success' => false,
                'error' => 'Acción no válida: ' . $action
            ];
    }
}

/**
 * Manejar solicitudes GET (consultar reservas, stock disponible)
 */
function handleGetRequest($stockService, $userId, $params) {
    $action = $params['action'] ?? 'get_reservations';
    
    switch ($action) {
        case 'get_reservations':
            // Obtener reservas activas del usuario
            return $stockService->getUserReservations($userId);
            
        case 'check_stock':
            // Verificar stock disponible de un producto
            if (!isset($params['product_id'])) {
                return [
                    'success' => false,
                    'error' => 'ID de producto requerido'
                ];
            }
            
            return $stockService->checkAvailableStock((int)$params['product_id']);
            
        case 'cleanup':
            // Limpiar reservas expiradas (solo para administradores)
            return $stockService->cleanupExpiredReservations();
            
        default:
            return [
                'success' => false,
                'error' => 'Acción de consulta no válida: ' . $action
            ];
    }
}

/**
 * Manejar solicitudes PUT (extender reservas, actualizar)
 */
function handlePutRequest($stockService, $userId, $input) {
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'extend':
            // Extender tiempo de reserva
            if (!isset($input['product_id'])) {
                return [
                    'success' => false,
                    'error' => 'ID de producto requerido'
                ];
            }
            
            $additionalMinutes = $input['minutes'] ?? 30;
            
            return $stockService->extendReservation(
                $userId,
                (int)$input['product_id'],
                (int)$additionalMinutes
            );
            
        case 'extend_all':
            // Extender todas las reservas del usuario
            $reservations = $stockService->getUserReservations($userId);
            if (!$reservations['success']) {
                return $reservations;
            }
            
            $results = [];
            foreach ($reservations['reservations'] as $reservation) {
                $result = $stockService->extendReservation(
                    $userId,
                    $reservation['product_id'],
                    30
                );
                $results[] = [
                    'product_id' => $reservation['product_id'],
                    'result' => $result
                ];
            }
            
            return [
                'success' => true,
                'message' => 'Reservas extendidas',
                'results' => $results
            ];
            
        default:
            return [
                'success' => false,
                'error' => 'Acción de actualización no válida: ' . $action
            ];
    }
}

/**
 * Manejar solicitudes DELETE (liberar reservas)
 */
function handleDeleteRequest($stockService, $userId, $sessionToken, $input) {
    $action = $input['action'] ?? 'release_all';
    
    switch ($action) {
        case 'release_all':
            // Liberar todas las reservas del usuario
            return $stockService->releaseUserReservations($userId, $sessionToken);
            
        case 'release_product':
            // Liberar reserva de un producto específico
            if (!isset($input['product_id'])) {
                return [
                    'success' => false,
                    'error' => 'ID de producto requerido'
                ];
            }
            
            try {
                global $pdo;
                $stmt = $pdo->prepare("
                    UPDATE stock_reservations 
                    SET status = 'cancelled'
                    WHERE user_id = ? AND product_id = ? AND status = 'active'
                ");
                $stmt->execute([$userId, (int)$input['product_id']]);
                
                if ($stmt->rowCount() > 0) {
                    return [
                        'success' => true,
                        'message' => 'Reserva del producto liberada correctamente'
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'No se encontró reserva activa para este producto'
                    ];
                }
            } catch (Exception $e) {
                return [
                    'success' => false,
                    'error' => 'Error al liberar reserva: ' . $e->getMessage()
                ];
            }
            
        default:
            return [
                'success' => false,
                'error' => 'Acción de eliminación no válida: ' . $action
            ];
    }
}

// Ejecutar el manejador principal
handleStockReservations();

?>
