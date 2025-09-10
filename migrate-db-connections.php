<?php
/**
 * ========================================
 * MIGRADOR DE CONEXIONES DE BASE DE DATOS
 * Centraliza todas las conexiones PDO
 * ========================================
 */

echo "🔧 Iniciando migración de conexiones de BD...\n";

// Archivos que necesitan actualización
$filesToUpdate = [
    'api/orders/create-simple.php',
    'api/products/create.php',
    'api/products/list.php',
    'api/products/get.php',
    'api/orders/create.php',
    'api/auth/login.php',
    'api/auth/register.php',
    'api/stock-reservations.php'
];

// Patrón de conexión antigua a reemplazar
$oldPattern = '/\$pdo = new PDO\s*\(\s*"mysql:host="\s*\.\s*DB_HOST[\s\S]*?\);/';

// Nueva conexión usando función centralizada
$newConnection = '$pdo = getDBConnection();';

$updatedFiles = 0;
$errors = [];

foreach ($filesToUpdate as $file) {
    $filePath = __DIR__ . '/' . $file;
    
    if (!file_exists($filePath)) {
        $errors[] = "❌ Archivo no encontrado: $file";
        continue;
    }
    
    $content = file_get_contents($filePath);
    if ($content === false) {
        $errors[] = "❌ Error al leer: $file";
        continue;
    }
    
    // Buscar y reemplazar conexiones PDO
    $newContent = preg_replace($oldPattern, $newConnection, $content);
    
    if ($newContent !== $content) {
        // Hacer backup
        $backupFile = $filePath . '.backup.' . date('Y-m-d-H-i-s');
        file_put_contents($backupFile, $content);
        
        // Escribir nueva versión
        if (file_put_contents($filePath, $newContent) !== false) {
            echo "✅ Actualizado: $file\n";
            $updatedFiles++;
        } else {
            $errors[] = "❌ Error al escribir: $file";
        }
    } else {
        echo "ℹ️  Sin cambios necesarios: $file\n";
    }
}

echo "\n🎯 Resumen de migración:\n";
echo "✅ Archivos actualizados: $updatedFiles\n";
echo "❌ Errores: " . count($errors) . "\n";

if (!empty($errors)) {
    echo "\n⚠️  Errores encontrados:\n";
    foreach ($errors as $error) {
        echo "$error\n";
    }
}

echo "\n📋 Próximos pasos:\n";
echo "1. Revisar los archivos actualizados\n";
echo "2. Probar las conexiones\n";
echo "3. Eliminar archivos .backup si todo funciona\n";
echo "4. Actualizar .env con credenciales seguras\n";

echo "\n🔒 Verificación de seguridad:\n";

// Verificar que .env está en .gitignore
$gitignore = file_get_contents(__DIR__ . '/.gitignore');
if (strpos($gitignore, '.env') !== false) {
    echo "✅ .env está en .gitignore\n";
} else {
    echo "❌ CRÍTICO: Agregar .env a .gitignore\n";
}

// Verificar permisos del archivo .env
if (file_exists(__DIR__ . '/.env')) {
    $perms = fileperms(__DIR__ . '/.env');
    if (($perms & 0777) <= 0600) {
        echo "✅ Permisos de .env son seguros\n";
    } else {
        echo "⚠️  RECOMENDACIÓN: chmod 600 .env\n";
    }
} else {
    echo "❌ Archivo .env no encontrado\n";
}

// Verificar JWT_SECRET
if (defined('JWT_SECRET') && JWT_SECRET !== 'fallback_secret_key_change_in_production') {
    echo "✅ JWT_SECRET personalizado configurado\n";
} else {
    echo "❌ CRÍTICO: Cambiar JWT_SECRET en .env\n";
}

echo "\n🎉 Migración completada!\n";
?>
