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

// Verificar que se subió un archivo
if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No se subió ningún archivo o hubo un error']);
    exit;
}

$file = $_FILES['profile_image'];

// Validaciones del archivo
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
$maxSize = 5 * 1024 * 1024; // 5MB

if (!in_array($file['type'], $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Formato no permitido. Solo se permiten imágenes en formato: JPG, PNG, WebP o GIF']);
    exit;
}

if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'El archivo es demasiado grande. Máximo 5MB']);
    exit;
}

// Verificar que el archivo es realmente una imagen
$imageInfo = getimagesize($file['tmp_name']);
if ($imageInfo === false) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'El archivo no es una imagen válida']);
    exit;
}

try {
    // Crear directorio del usuario si no existe
    $userDir = __DIR__ . "/../../users/profiles/" . $userId;
    if (!is_dir($userDir)) {
        if (!mkdir($userDir, 0755, true)) {
            throw new Exception('No se pudo crear el directorio del usuario');
        }
    }

    // Usar nombre simple 'profile' con la extensión del archivo
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $fileName = 'profile.' . $extension;
    $filePath = $userDir . '/' . $fileName;
    $relativePath = 'users/profiles/' . $userId . '/' . $fileName;

    // Actualizar base de datos
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Obtener imagen anterior si existe (antes de subir la nueva)
    $stmt = $pdo->prepare("SELECT profile_image FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
    $oldImagePath = null;
    
    // Si hay imagen anterior y es diferente a la nueva, guardar ruta para eliminarla después
    if ($currentUser && $currentUser['profile_image'] && $currentUser['profile_image'] !== $relativePath) {
        $oldImagePath = __DIR__ . "/../../" . $currentUser['profile_image'];
    }

    // Mover archivo subido (esto puede sobrescribir si existe)
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        throw new Exception('Error al mover el archivo subido');
    }

    // Guardar nueva ruta en base de datos
    $stmt = $pdo->prepare("UPDATE users SET profile_image = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$relativePath, $userId]);

    // Ahora sí eliminar la imagen anterior si era diferente
    if ($oldImagePath && file_exists($oldImagePath)) {
        unlink($oldImagePath);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Imagen de perfil actualizada correctamente',
        'profile_image' => $relativePath
    ]);

} catch (PDOException $e) {
    // Si hubo error, eliminar el archivo subido
    if (isset($filePath) && file_exists($filePath)) {
        unlink($filePath);
    }
    
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error del servidor']);
    error_log("Error uploading profile image: " . $e->getMessage());
    
} catch (Exception $e) {
    // Si hubo error, eliminar el archivo subido
    if (isset($filePath) && file_exists($filePath)) {
        unlink($filePath);
    }
    
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    error_log("Error uploading profile image: " . $e->getMessage());
}
?>
