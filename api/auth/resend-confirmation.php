<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar requests de OPTIONS para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Solo permitir método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(null, 405, 'Método no permitido');
}

try {
    // Obtener datos del request
    $input = json_decode(file_get_contents('php://input'), true);
    $email = $input['email'] ?? '';
    
    // Validar email
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(null, 400, 'Email válido requerido');
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
    
    // Buscar usuario
    $stmt = $pdo->prepare("
        SELECT id, email, first_name, email_verified, created_at 
        FROM users 
        WHERE email = ? 
        LIMIT 1
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        jsonResponse(null, 404, 'Usuario no encontrado');
    }
    
    // Verificar si el email ya está confirmado
    if ($user['email_verified']) {
        jsonResponse(null, 400, 'Tu email ya está confirmado');
    }
    
    // Verificar límite de tiempo para reenvío (1 minuto)
    $stmt = $pdo->prepare("
        SELECT updated_at 
        FROM users 
        WHERE id = ? 
        AND updated_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)
    ");
    $stmt->execute([$user['id']]);
    
    if ($stmt->rowCount() > 0) {
        jsonResponse(null, 429, 'Debes esperar al menos 1 minuto antes de solicitar otro email de confirmación');
    }
    
    // Generar nuevo token de confirmación
    $emailConfirmationToken = generateEmailConfirmationToken();
    
    // Actualizar token en la base de datos
    $stmt = $pdo->prepare("
        UPDATE users 
        SET email_confirmation_token = ?, 
            updated_at = ? 
        WHERE id = ?
    ");
    $stmt->execute([$emailConfirmationToken, date('Y-m-d H:i:s'), $user['id']]);
    
    // Enviar email de confirmación
    $emailSent = sendWelcomeEmail($user['email'], $user['first_name'], $emailConfirmationToken);
    
    if (!$emailSent) {
        logMessage('WARNING', "Failed to resend confirmation email to: {$user['email']} (User ID: {$user['id']})");
        jsonResponse(null, 500, 'Error al enviar el email de confirmación. Por favor, inténtalo más tarde.');
    }
    
    // Log del reenvío exitoso
    logMessage('INFO', "Confirmation email resent successfully to: {$user['email']} (User ID: {$user['id']})");
    
    // Respuesta exitosa
    jsonResponse([
        'emailSent' => true
    ], 200, 'Email de confirmación reenviado exitosamente. Por favor, revisa tu bandeja de entrada.');
    
} catch (PDOException $e) {
    logMessage('ERROR', "Database error in resend-confirmation: " . $e->getMessage());
    jsonResponse(null, 500, 'Error de base de datos');
} catch (Exception $e) {
    logMessage('ERROR', "Error in resend-confirmation: " . $e->getMessage());
    jsonResponse(null, 500, 'Error interno del servidor');
}
?>
