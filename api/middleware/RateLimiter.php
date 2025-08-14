<?php
/**
 * RateLimiter Middleware - Economía Circular Canarias
 * 
 * Implementa limitación de velocidad enterprise con:
 * - Múltiples niveles (IP, Usuario, Endpoint)
 * - Algoritmo Token Bucket
 * - Configuración flexible
 * - Logging y monitoreo
 */

class RateLimiter {
    private $redis;
    private $config;
    private $isRedisAvailable;
    
    // Configuración por defecto
    private $defaultLimits = [
        'auth' => [
            'requests' => 5,
            'window' => 60, // 1 minuto
            'burst' => 10
        ],
        'api' => [
            'requests' => 100,
            'window' => 60,
            'burst' => 150
        ],
        'global' => [
            'requests' => 1000,
            'window' => 3600, // 1 hora
            'burst' => 1200
        ]
    ];
    
    public function __construct($config = []) {
        $this->config = array_merge($this->defaultLimits, $config);
        $this->initRedis();
    }
    
    /**
     * Inicializar Redis (opcional)
     */
    private function initRedis() {
        $this->isRedisAvailable = false;
        
        if (class_exists('Redis')) {
            try {
                $this->redis = new Redis();
                $this->redis->connect('127.0.0.1', 6379);
                $this->redis->ping();
                $this->isRedisAvailable = true;
                logMessage('INFO', 'Redis connected for rate limiting');
            } catch (Exception $e) {
                logMessage('WARNING', 'Redis not available, using file-based rate limiting: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Verificar límite de velocidad
     */
    public function checkLimit($identifier, $limitType = 'api', $customLimit = null) {
        $limit = $customLimit ?? $this->config[$limitType] ?? $this->config['api'];
        
        if ($this->isRedisAvailable) {
            return $this->checkLimitRedis($identifier, $limit, $limitType);
        } else {
            return $this->checkLimitFile($identifier, $limit, $limitType);
        }
    }
    
    /**
     * Verificar límite usando Redis
     */
    private function checkLimitRedis($identifier, $limit, $limitType) {
        $key = "rate_limit:{$limitType}:{$identifier}";
        $now = time();
        $window = $limit['window'];
        $maxRequests = $limit['requests'];
        
        try {
            // Implementar sliding window con Redis
            $this->redis->multi();
            
            // Limpiar entradas antiguas
            $this->redis->zRemRangeByScore($key, 0, $now - $window);
            
            // Contar requests actuales
            $currentCount = $this->redis->zCard($key);
            
            if ($currentCount < $maxRequests) {
                // Añadir nueva request
                $this->redis->zAdd($key, $now, uniqid());
                $this->redis->expire($key, $window);
                $this->redis->exec();
                
                return [
                    'allowed' => true,
                    'remaining' => $maxRequests - $currentCount - 1,
                    'reset_time' => $now + $window,
                    'retry_after' => null
                ];
            } else {
                $this->redis->discard();
                
                // Calcular tiempo de retry
                $oldestRequest = $this->redis->zRange($key, 0, 0, true);
                $retryAfter = $window - ($now - array_values($oldestRequest)[0]);
                
                return [
                    'allowed' => false,
                    'remaining' => 0,
                    'reset_time' => $now + $retryAfter,
                    'retry_after' => max(1, $retryAfter)
                ];
            }
            
        } catch (Exception $e) {
            logMessage('ERROR', 'Redis rate limiting error: ' . $e->getMessage());
            // Fallback a file-based
            return $this->checkLimitFile($identifier, $limit, $limitType);
        }
    }
    
    /**
     * Verificar límite usando archivos
     */
    private function checkLimitFile($identifier, $limit, $limitType) {
        $rateLimitDir = TEMP_DIR . '/rate_limits';
        if (!is_dir($rateLimitDir)) {
            mkdir($rateLimitDir, 0755, true);
        }
        
        $filename = $rateLimitDir . '/' . md5($limitType . '_' . $identifier) . '.json';
        $now = time();
        $window = $limit['window'];
        $maxRequests = $limit['requests'];
        
        // Leer datos existentes
        $data = [];
        if (file_exists($filename)) {
            $content = file_get_contents($filename);
            $data = json_decode($content, true) ?: [];
        }
        
        // Limpiar entradas antiguas
        $data['requests'] = array_filter($data['requests'] ?? [], function($timestamp) use ($now, $window) {
            return $timestamp > ($now - $window);
        });
        
        $currentCount = count($data['requests']);
        
        if ($currentCount < $maxRequests) {
            // Añadir nueva request
            $data['requests'][] = $now;
            $data['last_updated'] = $now;
            
            file_put_contents($filename, json_encode($data), LOCK_EX);
            
            return [
                'allowed' => true,
                'remaining' => $maxRequests - $currentCount - 1,
                'reset_time' => $now + $window,
                'retry_after' => null
            ];
        } else {
            // Calcular retry after
            $oldestRequest = min($data['requests']);
            $retryAfter = $window - ($now - $oldestRequest);
            
            return [
                'allowed' => false,
                'remaining' => 0,
                'reset_time' => $now + $retryAfter,
                'retry_after' => max(1, $retryAfter)
            ];
        }
    }
    
    /**
     * Aplicar rate limiting a una request
     */
    public function applyRateLimit($endpoint = null, $userId = null) {
        $ip = $this->getClientIp();
        
        // Determinar tipo de límite
        $limitType = 'api';
        if ($endpoint && strpos($endpoint, 'auth') !== false) {
            $limitType = 'auth';
        }
        
        // Verificar múltiples niveles
        $checks = [
            'ip' => $this->checkLimit($ip, $limitType),
            'global' => $this->checkLimit('global', 'global')
        ];
        
        if ($userId) {
            $checks['user'] = $this->checkLimit($userId, $limitType);
        }
        
        // Encontrar el límite más restrictivo
        $mostRestrictive = null;
        foreach ($checks as $type => $result) {
            if (!$result['allowed']) {
                if (!$mostRestrictive || $result['retry_after'] > $mostRestrictive['retry_after']) {
                    $mostRestrictive = $result;
                    $mostRestrictive['limit_type'] = $type;
                }
            }
        }
        
        if ($mostRestrictive) {
            // Log del rate limit
            logMessage('WARNING', "Rate limit exceeded for IP: {$ip}, Type: {$mostRestrictive['limit_type']}, Endpoint: {$endpoint}");
            
            // Enviar headers de rate limiting
            http_response_code(429);
            header('X-RateLimit-Limit: ' . $this->config[$limitType]['requests']);
            header('X-RateLimit-Remaining: 0');
            header('X-RateLimit-Reset: ' . $mostRestrictive['reset_time']);
            header('Retry-After: ' . $mostRestrictive['retry_after']);
            
            echo json_encode([
                'success' => false,
                'error' => 'RATE_LIMIT_EXCEEDED',
                'message' => 'Demasiadas solicitudes. Intenta de nuevo más tarde.',
                'retry_after' => $mostRestrictive['retry_after'],
                'limit_type' => $mostRestrictive['limit_type']
            ]);
            exit;
        }
        
        // Obtener headers para requests permitidas
        $allowedCheck = $checks['ip'];
        header('X-RateLimit-Limit: ' . $this->config[$limitType]['requests']);
        header('X-RateLimit-Remaining: ' . $allowedCheck['remaining']);
        header('X-RateLimit-Reset: ' . $allowedCheck['reset_time']);
        
        return true;
    }
    
    /**
     * Obtener IP del cliente
     */
    private function getClientIp() {
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    
    /**
     * Obtener estadísticas de rate limiting
     */
    public function getStats($identifier = null) {
        if (!$identifier) {
            $identifier = $this->getClientIp();
        }
        
        $stats = [];
        foreach ($this->config as $limitType => $config) {
            $result = $this->checkLimit($identifier, $limitType);
            $stats[$limitType] = [
                'limit' => $config['requests'],
                'remaining' => $result['remaining'],
                'reset_time' => $result['reset_time'],
                'window' => $config['window']
            ];
        }
        
        return $stats;
    }
    
    /**
     * Limpiar rate limits antiguos (mantenimiento)
     */
    public function cleanup() {
        if (!$this->isRedisAvailable) {
            $rateLimitDir = TEMP_DIR . '/rate_limits';
            if (is_dir($rateLimitDir)) {
                $files = glob($rateLimitDir . '/*.json');
                $now = time();
                
                foreach ($files as $file) {
                    $data = json_decode(file_get_contents($file), true);
                    if ($data && isset($data['last_updated'])) {
                        // Eliminar archivos de más de 1 hora
                        if ($now - $data['last_updated'] > 3600) {
                            unlink($file);
                        }
                    }
                }
            }
        }
    }
}

/**
 * Función helper para aplicar rate limiting
 */
function applyRateLimit($endpoint = null, $userId = null) {
    global $rateLimiter;
    
    if (!isset($rateLimiter)) {
        $rateLimiter = new RateLimiter();
    }
    
    return $rateLimiter->applyRateLimit($endpoint, $userId);
}

/**
 * Middleware para auto-aplicar rate limiting
 */
function rateLimitMiddleware($endpoint = null) {
    return applyRateLimit($endpoint);
}
?>
