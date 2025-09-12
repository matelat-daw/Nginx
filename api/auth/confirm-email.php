<?php
require_once __DIR__ . '/../config.php';

// Headers CORS
setCorsHeaders();

// Manejar preflight requests
handlePreflight();

// Solo permitir métodos GET y POST
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'])) {
    jsonResponse(null, 405, 'Método no permitido');
}

try {
    // Obtener token de confirmación
    $token = null;
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $token = $_GET['token'] ?? null;
    } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $token = $input['token'] ?? null;
    }
    
    // Validar que se proporcione el token
    if (empty($token)) {
        jsonResponse(null, 400, 'Token de confirmación requerido');
    }
    
    // Validar formato del token
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        jsonResponse(null, 400, 'Token de confirmación inválido');
    }
    
    // Conectar a la base de datos
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    
    // Buscar usuario con el token de confirmación
    $stmt = $pdo->prepare("
        SELECT id, email, first_name, email_verified, created_at 
        FROM users 
        WHERE email_confirmation_token = ? 
        AND email_verified = 0
        LIMIT 1
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if (!$user) {
        jsonResponse(null, 400, 'Token de confirmación inválido o ya utilizado');
    }
    
    // Verificar que el token no haya expirado (72 horas)
    $createdAt = new DateTime($user['created_at']);
    $now = new DateTime();
    $diffHours = $now->diff($createdAt)->days * 24 + $now->diff($createdAt)->h;
    
    if ($diffHours > 72) {
        jsonResponse(null, 400, 'Token de confirmación expirado. Por favor, solicita un nuevo email de confirmación.');
    }
    
    // Confirmar email del usuario
    $stmt = $pdo->prepare("
        UPDATE users 
        SET email_verified = 1, 
            email_confirmation_token = NULL, 
            updated_at = ? 
        WHERE id = ?
    ");
    $stmt->execute([date('Y-m-d H:i:s'), $user['id']]);
    
    // Log de confirmación exitosa
    logMessage('INFO', "Email confirmed successfully for user: {$user['email']} (ID: {$user['id']})");
    
    // Respuesta exitosa
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Para requests GET, redirigir a la página principal con el hash de login
        $redirectUrl = SITE_URL . '/#login?email-confirmed=1&message=' . urlencode('¡Tu email ha sido confirmado exitosamente! Ya puedes iniciar sesión.');
        header('Location: ' . $redirectUrl);
        exit();
    } else {
        // Para requests POST, devolver JSON
        jsonResponse([
            'confirmed' => true,
            'user' => [
                'id' => (int)$user['id'],
                'email' => $user['email'],
                'firstName' => $user['first_name'],
                'emailVerified' => true
            ]
        ], 200, '¡Email confirmado exitosamente! Ya puedes usar todas las funciones de la plataforma.');
    }
    
} catch (PDOException $e) {
    logMessage('ERROR', "Database error in confirm-email: " . $e->getMessage());
    jsonResponse(null, 500, 'Error de base de datos');
} catch (Exception $e) {
    logMessage('ERROR', "Error in confirm-email: " . $e->getMessage());
    jsonResponse(null, 500, 'Error interno del servidor');
}
?>
