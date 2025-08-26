<?php
/**
 * Controlador de Logout - Economía Circular Canarias
 * 
 * Logout optimizado con configuración centralizada
 */

// Incluir configuración
require_once __DIR__ . '/../config.php';

// Headers CORS
setCorsHeaders();

// Manejar preflight requests
handlePreflight();

// Solo permitir método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(null, 405, 'Método no permitido');
}

try {
    // Limpiar cookie de autenticación
    setcookie(COOKIE_NAME, '', [
        'expires' => time() - 3600, // Expirar en el pasado
        'path' => '/',
        'domain' => '',
        'secure' => COOKIE_SECURE,
        'httponly' => COOKIE_HTTP_ONLY,
        'samesite' => COOKIE_SAME_SITE
    ]);
    
    // Log del logout (si hay información de usuario disponible)
    if (isset($_COOKIE[COOKIE_NAME])) {
        logMessage('INFO', "User logged out - session terminated");
    }
    
    jsonResponse(['success' => true], 200, 'Sesión cerrada correctamente');
    
} catch (Exception $e) {
    logMessage('ERROR', "Error in logout: " . $e->getMessage());
    jsonResponse(null, 500, 'Error interno del servidor');
}
?>