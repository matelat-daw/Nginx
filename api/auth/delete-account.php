<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
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

// Validar contraseña de confirmación
if (empty($input['password'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Contraseña de confirmación requerida']);
    exit;
}

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Verificar contraseña del usuario
    $stmt = $pdo->prepare("SELECT password_hash, email FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($input['password'], $user['password_hash'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Contraseña incorrecta']);
        exit;
    }

    // Iniciar transacción para eliminación segura
    $pdo->beginTransaction();

    try {
        // Eliminar usuario
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);

        if ($stmt->rowCount() > 0) {
            $pdo->commit();
            
            // Limpiar cookie de autenticación
            setcookie(COOKIE_NAME, '', [
                'expires' => time() - 3600,
                'path' => '/',
                'domain' => '',
                'secure' => false,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Cuenta eliminada correctamente'
            ]);
        } else {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No se pudo eliminar la cuenta']);
        }

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error del servidor']);
    error_log("Error deleting account: " . $e->getMessage());
}
?>
