<?php
/**
 * Controlador de Registro - Economía Circular Canarias
 * Versión 2.0 - Optimizado con nuevas funciones helper
 */

require_once __DIR__ . '/../config.php';

// Headers CORS y seguridad
setCorsHeaders();
handlePreflight();
applySecurityMiddleware(true); // Con rate limiting

// Solo permitir método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(null, 405, 'Método no permitido');
}

try {
    // Obtener input JSON
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        jsonResponse(null, 400, 'Datos de entrada requeridos');
    }
    
    // Validar campos requeridos
    $requiredFields = ['firstName', 'lastName', 'email', 'password'];
    $missingFields = [];
    
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            $missingFields[] = $field;
        }
    }
    
    if (!empty($missingFields)) {
        jsonResponse(null, 400, 'Campos requeridos faltantes: ' . implode(', ', $missingFields));
    }
    
    // Sanitizar y validar datos
    $firstName = trim($data['firstName']);
    $lastName = trim($data['lastName']);
    $email = filter_var(trim($data['email']), FILTER_SANITIZE_EMAIL);
    $password = $data['password'];
    $island = isset($data['island']) ? trim($data['island']) : '';
    $city = isset($data['city']) ? trim($data['city']) : '';
    $userType = isset($data['userType']) ? trim($data['userType']) : 'particular';
    
    // Validar email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(null, 400, 'Formato de email inválido');
    }
    
    // Validar contraseña
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        jsonResponse(null, 400, "La contraseña debe tener al menos " . PASSWORD_MIN_LENGTH . " caracteres");
    }
    
    // Validar tipo de usuario
    $validUserTypes = ['particular', 'empresa', 'organizacion', 'cooperativa'];
    if (!empty($userType) && !in_array($userType, $validUserTypes)) {
        jsonResponse(null, 400, 'Tipo de usuario no válido');
    }
    
    // Usar la función centralizada para obtener conexión DB
    $pdo = getDBConnection();
    
    // Verificar si el email ya existe
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() > 0) {
        jsonResponse(null, 400, 'Este email ya está registrado');
    }
    
    // Hash de la contraseña
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    // Insertar nuevo usuario
    $stmt = $pdo->prepare("
        INSERT INTO users (
            first_name, 
            last_name, 
            email, 
            password_hash, 
            island, 
            city, 
            user_type, 
            email_verified, 
            created_at, 
            updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $now = date('Y-m-d H:i:s');
    $stmt->execute([
        $firstName,
        $lastName,
        $email,
        $passwordHash,
        $island,
        $city,
        $userType,
        0, // email_verified = false
        $now,
        $now
    ]);
    
    $userId = $pdo->lastInsertId();
    
    // Generar token de confirmación de email
    $emailConfirmationToken = generateEmailConfirmationToken();
    
    // Guardar token de confirmación en la base de datos
    $stmt = $pdo->prepare("UPDATE users SET email_confirmation_token = ? WHERE id = ?");
    $stmt->execute([$emailConfirmationToken, $userId]);
    
    // Enviar email de bienvenida con confirmación
    $emailResult = sendWelcomeEmail($email, $firstName, $userId, $emailConfirmationToken);
    
    // Manejar resultado del email (puede ser boolean o array en desarrollo)
    $emailSent = false;
    $confirmationUrl = null;
    $isDevelopment = false;
    
    if (is_array($emailResult)) {
        // Respuesta de desarrollo con información adicional
        $emailSent = $emailResult['sent'] ?? false;
        $confirmationUrl = $emailResult['confirmationUrl'] ?? null;
        $isDevelopment = $emailResult['development'] ?? false;
    } else {
        // Respuesta booleana tradicional
        $emailSent = (bool)$emailResult;
    }
    
    if (!$emailSent) {
        logMessage('WARNING', "Failed to send welcome email to: {$email} (User ID: {$userId})");
    } else {
        logMessage('INFO', "Welcome email sent successfully to: {$email} (User ID: {$userId})");
    }
    
    // Generar token JWT usando función optimizada
    $jwt = JWT::generateToken($userId, $email);
    
    // Establecer cookie
    setcookie(COOKIE_NAME, $jwt, [
        'expires' => time() + COOKIE_EXPIRATION,
        'path' => '/',
        'domain' => '',
        'secure' => COOKIE_SECURE,
        'httponly' => COOKIE_HTTP_ONLY,
        'samesite' => COOKIE_SAME_SITE
    ]);
    
    // Log del registro exitoso
    logMessage('INFO', "New user registered: {$email} (ID: {$userId})");
    
    // Preparar respuesta
    $userData = [
        'id' => (int)$userId,
        'firstName' => $firstName,
        'lastName' => $lastName,
        'email' => $email,
        'island' => $island,
        'city' => $city,
        'userType' => $userType,
        'emailVerified' => false
    ];
    
    $responseData = [
        'user' => $userData,
        'token' => $jwt,
        'emailSent' => $emailSent
    ];
    
    // Agregar información de desarrollo si aplica
    if ($isDevelopment && $confirmationUrl) {
        $responseData['development'] = [
            'confirmationUrl' => $confirmationUrl,
            'message' => 'Email no enviado - Enlace de confirmación disponible'
        ];
    }
    
    $message = "¡Bienvenido/a {$firstName}! Tu cuenta ha sido creada exitosamente. ";
    
    if ($emailSent) {
        $message .= "Hemos enviado un email de confirmación a tu dirección de correo.";
    } else if ($isDevelopment && $confirmationUrl) {
        $message .= "En modo desarrollo - usa el enlace de confirmación proporcionado.";
    } else {
        $message .= "Por favor, contacta al administrador para activar tu cuenta.";
    }
    
    jsonResponse($responseData, 201, $message);
    
} catch (PDOException $e) {
    logMessage('ERROR', "Database error in register: " . $e->getMessage());
    
    // En modo desarrollo, mostrar más detalles del error
    if (DEBUG_MODE) {
        jsonResponse(null, 500, 'Error de base de datos: ' . $e->getMessage());
    } else {
        jsonResponse(null, 500, 'Error de base de datos');
    }
} catch (Exception $e) {
    logMessage('ERROR', "Error in register: " . $e->getMessage());
    
    // En modo desarrollo, mostrar más detalles del error
    if (DEBUG_MODE) {
        jsonResponse(null, 500, 'Error interno: ' . $e->getMessage());
    } else {
        jsonResponse(null, 500, 'Error interno del servidor');
    }
}
?>
