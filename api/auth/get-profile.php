<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

require_once '../config.php';

// Verificar JWT
$headers = getallheaders();
$jwt = $_COOKIE[COOKIE_NAME] ?? null;

// Si no hay cookie, buscar en Authorization header
if (!$jwt && isset($headers['Authorization'])) {
    $authHeader = $headers['Authorization'];
    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $jwt = $matches[1];
    }
}

if (!$jwt) {
    error_log("No JWT token found in cookies or headers");
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Token no encontrado']);
    exit;
}

error_log("JWT token found: " . substr($jwt, 0, 20) . "...");

try {
    error_log("Attempting to decode JWT...");
    $decoded = JWT::decode($jwt, JWT_SECRET);
    error_log("JWT decoded successfully. User ID: " . $decoded->user_id);
    $userId = $decoded->user_id;
} catch (Exception $e) {
    error_log("JWT decode error: " . $e->getMessage());
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Token inválido: ' . $e->getMessage()]);
    exit;
}

try {
    // Log de debug para conexión DB
    error_log("Attempting to connect to database: " . DB_HOST . "/" . DB_NAME);
    
    // Usar la función centralizada para obtener conexión DB
    $pdo = getDBConnection();
    
    error_log("Database connection successful");

    // Obtener datos del usuario
    error_log("Attempting to fetch user data for user_id: " . $userId);
    
    $stmt = $pdo->prepare("
        SELECT id, first_name, last_name, email, phone, island, city, user_type, email_verified, profile_image, created_at, updated_at
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    error_log("Query executed. User found: " . ($user ? 'Yes' : 'No'));

    if ($user) {
        // Convertir email_verified a boolean y renombrar campos para compatibilidad frontend
        $user['user_id'] = $user['id']; // Agregar user_id para compatibilidad
        $user['is_email_verified'] = (bool) $user['email_verified'];
        
        echo json_encode([
            'success' => true,
            'user' => $user
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
    }

} catch (PDOException $e) {
    error_log("Database error in get-profile.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
}
?>
