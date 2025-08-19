<?php
/**
 * Confirmación de Email - Economía Circular Canarias
 * Endpoint para confirmar email de usuarios
 */

require_once __DIR__ . '/api/config.php';

// Headers CORS
setCorsHeaders();

// Solo permitir GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(null, 405, 'Método no permitido');
}

try {
    // Obtener token de la URL
    $token = $_GET['token'] ?? null;
    $userId = $_GET['id'] ?? null;
    
    // Validar que se proporcionen los parámetros
    if (empty($token)) {
        jsonResponse(null, 400, 'Token de confirmación requerido');
    }
    
    // Validar formato del token
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        jsonResponse(null, 400, 'Token de confirmación inválido');
    }
    
    // Conectar a la base de datos
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
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
        FROM ecc_users 
        WHERE email_confirmation_token = ? 
        AND email_verified = 0
        LIMIT 1
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if (!$user) {
        // Redirigir al login con mensaje de error
        header('Location: /#/login?message=' . urlencode('Token de confirmación inválido o ya utilizado'));
        exit();
    }
    
    // Verificar que el token no haya expirado (72 horas)
    $createdAt = new DateTime($user['created_at']);
    $now = new DateTime();
    $diffHours = $now->diff($createdAt)->days * 24 + $now->diff($createdAt)->h;
    
    if ($diffHours > 72) {
        header('Location: /#/login?message=' . urlencode('Token de confirmación expirado. Por favor, solicita un nuevo email de confirmación.'));
        exit();
    }
    
    // Confirmar email del usuario
    $stmt = $pdo->prepare("
        UPDATE ecc_users 
        SET email_verified = 1, 
            email_confirmation_token = NULL, 
            updated_at = ? 
        WHERE id = ?
    ");
    $stmt->execute([date('Y-m-d H:i:s'), $user['id']]);
    
    // Log de confirmación exitosa
    logMessage('INFO', "Email confirmed successfully for user: {$user['email']} (ID: {$user['id']})");
    
    // Redirigir al login con mensaje de éxito
    header('Location: /#/login?email-confirmed=1&message=' . urlencode('¡Tu email ha sido confirmado exitosamente! Ya puedes iniciar sesión.'));
    exit();
    
} catch (PDOException $e) {
    logMessage('ERROR', "Database error in confirm-email: " . $e->getMessage());
    header('Location: /#/login?message=' . urlencode('Error de base de datos al confirmar email'));
    exit();
} catch (Exception $e) {
    logMessage('ERROR', "Error in confirm-email: " . $e->getMessage());
    header('Location: /#/login?message=' . urlencode('Error interno del servidor'));
    exit();
}
?>
