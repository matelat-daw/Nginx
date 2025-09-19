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
if (empty($input['currentPassword']) || empty($input['newPassword'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Todos los campos son requeridos']);
    exit;
}

// Validar longitud de la nueva contraseña
if (strlen($input['newPassword']) < 8) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'La nueva contraseña debe tener al menos 8 caracteres']);
    exit;
}

// Validar que la nueva contraseña contenga al menos una mayúscula, una minúscula y un número
if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', $input['newPassword'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'La nueva contraseña debe contener al menos una mayúscula, una minúscula y un número']);
    exit;
}

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Verificar contraseña actual
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($input['currentPassword'], $user['password_hash'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Contraseña actual incorrecta']);
        exit;
    }

    // Verificar que la nueva contraseña sea diferente a la actual
    if (password_verify($input['newPassword'], $user['password_hash'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'La nueva contraseña debe ser diferente a la actual']);
        exit;
    }

    // Actualizar contraseña
    $newPasswordHash = password_hash($input['newPassword'], PASSWORD_ARGON2ID, [
        'memory_cost' => 65536,
        'time_cost' => 4,
        'threads' => 3
    ]);

    $stmt = $pdo->prepare("
        UPDATE users 
        SET password_hash = ?, updated_at = NOW()
        WHERE id = ?
    ");
    
    $stmt->execute([$newPasswordHash, $userId]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Contraseña actualizada correctamente'
        ]);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No se pudo actualizar la contraseña']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error del servidor']);
    error_log("Error changing password: " . $e->getMessage());
}
?>
