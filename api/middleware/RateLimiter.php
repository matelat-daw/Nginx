<?php
/**
 * RateLimiter Simple - Rate limiting básico
 * Solo las funciones esenciales que podrían usarse
 */

class RateLimiterSimple {
    
    /**
     * Verificar límite básico por IP
     */
    public static function checkLimit($requests = 100, $window = 3600) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $cacheFile = sys_get_temp_dir() . '/rate_limit_' . md5($ip) . '.tmp';
        
        $now = time();
        $requests_data = [];
        
        // Leer requests anteriores
        if (file_exists($cacheFile)) {
            $data = file_get_contents($cacheFile);
            $requests_data = json_decode($data, true) ?: [];
        }
        
        // Filtrar requests dentro de la ventana de tiempo
        $requests_data = array_filter($requests_data, function($timestamp) use ($now, $window) {
            return ($now - $timestamp) < $window;
        });
        
        // Verificar límite
        if (count($requests_data) >= $requests) {
            return false;
        }
        
        // Agregar request actual
        $requests_data[] = $now;
        file_put_contents($cacheFile, json_encode($requests_data), LOCK_EX);
        
        return true;
    }
    
    /**
     * Aplicar rate limiting básico
     */
    public static function apply($requests = 100, $window = 3600) {
        if (!self::checkLimit($requests, $window)) {
            http_response_code(429);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Demasiadas solicitudes. Intenta de nuevo más tarde.'
            ]);
            exit;
        }
        return true;
    }
}
?>
?>
