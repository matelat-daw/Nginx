<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

require_once '../config.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

// Verificar JWT
$headers = getallheaders();
$jwt = $_COOKIE[COOKIE_NAME] ?? $headers['Authorization'] ?? null;

if (!$jwt) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Token no encontrado']);
    exit;
}

try {
    $decoded = JWT::decode($jwt, JWT_SECRET);
    $userId = $decoded->user_id;
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Token inválido']);
    exit;
}

// Validar campos requeridos
$requiredFields = ['firstName', 'lastName', 'phone', 'island', 'city', 'userType'];
foreach ($requiredFields as $field) {
    if (empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "El campo $field es requerido"]);
        exit;
    }
}

// Validar formato de teléfono
if (!preg_match('/^[\+]?[\d\s\-\(\)]+$/', $input['phone'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Formato de teléfono inválido']);
    exit;
}

// Validar isla
$validIslands = ['Gran Canaria', 'Tenerife', 'Lanzarote', 'Fuerteventura', 'La Palma', 'La Gomera', 'El Hierro'];
if (!in_array($input['island'], $validIslands)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Isla no válida']);
    exit;
}

// Normalizar userType a minúsculas
$input['userType'] = strtolower(trim($input['userType']));

// Mapeo de valores por si la BD usa ENUM con valores en inglés
$userTypeMap = [
    'particular' => 'particular',
    'empresa' => 'empresa',
    'organizacion' => 'organizacion',
    'cooperativa' => 'cooperativa',
    // Mapeo inverso por compatibilidad
    'individual' => 'particular',
    'business' => 'empresa',
    'organization' => 'organizacion'
];

// Si el valor existe en el mapeo, usarlo
if (isset($userTypeMap[$input['userType']])) {
    $input['userType'] = $userTypeMap[$input['userType']];
}

// Validar tipo de usuario
$validUserTypes = ['particular', 'empresa', 'organizacion', 'cooperativa'];
if (!in_array($input['userType'], $validUserTypes)) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => 'Tipo de usuario no válido. Recibido: ' . $input['userType']
    ]);
    exit;
}

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Log de depuración
    error_log("UPDATE PROFILE - User ID: " . $userId);
    error_log("UPDATE PROFILE - Data: " . json_encode([
        'firstName' => $input['firstName'],
        'lastName' => $input['lastName'],
        'phone' => $input['phone'],
        'island' => $input['island'],
        'city' => $input['city'],
        'userType' => $input['userType']
    ]));

    // Actualizar perfil del usuario
    $stmt = $pdo->prepare("
        UPDATE users 
        SET first_name = ?, last_name = ?, phone = ?, island = ?, city = ?, user_type = ?, updated_at = NOW()
        WHERE id = ?
    ");
    
    $result = $stmt->execute([
        $input['firstName'],
        $input['lastName'],
        $input['phone'],
        $input['island'],
        $input['city'],
        $input['userType'],
        $userId
    ]);
    
    $rowCount = $stmt->rowCount();
    error_log("UPDATE PROFILE - Rows affected: " . $rowCount);
    error_log("UPDATE PROFILE - Execute result: " . ($result ? 'true' : 'false'));

    // Obtener datos actualizados del usuario (siempre, independientemente de si hubo cambios)
    $stmt = $pdo->prepare("
        SELECT id, first_name, last_name, email, phone, island, city, user_type, email_verified, profile_image, created_at, updated_at
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo json_encode([
            'success' => true,
            'message' => 'Perfil actualizado correctamente',
            'user' => $user
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error del servidor']);
    error_log("Error updating profile: " . $e->getMessage());
}
?>
