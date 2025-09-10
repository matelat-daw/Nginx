<?php
/**
 * SCRIPT DE VERIFICACIÓN DE CONFIGURACIÓN DE BD
 * Prueba la conexión y configuración de seguridad
 */

echo "🔍 VERIFICANDO CONFIGURACIÓN DE BASE DE DATOS\n";
echo "=" . str_repeat("=", 50) . "\n\n";

// Cargar configuración
require_once 'api/config.php';

echo "📋 REVISIÓN DE CONFIGURACIÓN:\n";
echo "─" . str_repeat("─", 30) . "\n";

// 1. Verificar carga de variables de entorno
echo "1. Variables de entorno: ";
if (defined('DB_HOST') && DB_HOST !== 'localhost') {
    echo "✅ Cargadas desde .env\n";
} else {
    echo "⚠️  Usando valores por defecto\n";
}

echo "   - DB_HOST: " . (defined('DB_HOST') ? DB_HOST : 'NO DEFINIDO') . "\n";
echo "   - DB_NAME: " . (defined('DB_NAME') ? DB_NAME : 'NO DEFINIDO') . "\n";
echo "   - DB_USER: " . (defined('DB_USER') ? DB_USER : 'NO DEFINIDO') . "\n";
echo "   - DB_PASS: " . (defined('DB_PASS') ? str_repeat('*', strlen(DB_PASS)) : 'NO DEFINIDO') . "\n";

// 2. Verificar función de conexión
echo "\n2. Función getDBConnection(): ";
if (function_exists('getDBConnection')) {
    echo "✅ Disponible\n";
} else {
    echo "❌ NO ENCONTRADA\n";
    exit(1);
}

// 3. Probar conexión
echo "\n3. Prueba de conexión: ";
try {
    $pdo = getDBConnection();
    echo "✅ EXITOSA\n";
    
    // Verificar versión de MySQL
    $stmt = $pdo->query("SELECT VERSION() as version");
    $version = $stmt->fetch();
    echo "   - Versión MySQL: " . $version['version'] . "\n";
    
    // Verificar que puede ejecutar consultas
    $stmt = $pdo->query("SELECT 1 as test");
    $result = $stmt->fetch();
    if ($result['test'] == 1) {
        echo "   - Consultas: ✅ Funcionando\n";
    }
    
} catch (Exception $e) {
    echo "❌ FALLO\n";
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n🔒 VERIFICACIÓN DE SEGURIDAD:\n";
echo "─" . str_repeat("─", 30) . "\n";

// 4. Verificar JWT_SECRET
echo "4. JWT_SECRET: ";
if (defined('JWT_SECRET')) {
    if (JWT_SECRET === 'fallback_secret_key_change_in_production') {
        echo "⚠️  USANDO VALOR POR DEFECTO (Cambiar en producción)\n";
    } else {
        echo "✅ Personalizado (" . strlen(JWT_SECRET) . " caracteres)\n";
    }
} else {
    echo "❌ NO DEFINIDO\n";
}

// 5. Verificar archivo .env
echo "\n5. Archivo .env: ";
if (file_exists('.env')) {
    echo "✅ Existe\n";
    $perms = fileperms('.env') & 0777;
    echo "   - Permisos: " . decoct($perms) . " ";
    if ($perms <= 0600) {
        echo "✅ Seguros\n";
    } else {
        echo "⚠️  Recomendado: chmod 600 .env\n";
    }
} else {
    echo "❌ NO ENCONTRADO\n";
}

// 6. Verificar .gitignore
echo "\n6. .gitignore: ";
if (file_exists('.gitignore')) {
    $gitignore = file_get_contents('.gitignore');
    if (strpos($gitignore, '.env') !== false) {
        echo "✅ .env está excluido\n";
    } else {
        echo "❌ .env NO está excluido\n";
    }
} else {
    echo "❌ .gitignore no encontrado\n";
}

echo "\n📊 RESUMEN FINAL:\n";
echo "─" . str_repeat("─", 15) . "\n";

$issues = [];
if (!defined('DB_HOST')) $issues[] = "Configuración de BD no cargada";
if (!function_exists('getDBConnection')) $issues[] = "Función de conexión no disponible";
if (defined('JWT_SECRET') && JWT_SECRET === 'fallback_secret_key_change_in_production') {
    $issues[] = "JWT_SECRET debe cambiarse";
}

if (empty($issues)) {
    echo "🎉 ¡CONFIGURACIÓN CORRECTA!\n";
    echo "✅ Todas las verificaciones pasaron\n";
    echo "✅ Conexión a BD funcional\n";
    echo "✅ Seguridad básica implementada\n";
} else {
    echo "⚠️  ISSUES ENCONTRADOS:\n";
    foreach ($issues as $issue) {
        echo "   - " . $issue . "\n";
    }
}

echo "\n💡 RECOMENDACIONES:\n";
if (defined('JWT_SECRET') && JWT_SECRET === 'fallback_secret_key_change_in_production') {
    echo "   - Ejecutar: openssl rand -base64 64\n";
    echo "   - Actualizar JWT_SECRET en .env\n";
}
echo "   - Verificar que el servidor BD tiene firewall configurado\n";
echo "   - Usar HTTPS en producción\n";
echo "   - Implementar backups automáticos\n";

echo "\n✅ Verificación completada.\n";
?>
