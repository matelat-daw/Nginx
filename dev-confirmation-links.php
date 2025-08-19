<?php
/**
 * Visor de Enlaces de Confirmación - Solo para Desarrollo
 */

$tempFile = __DIR__ . '/temp_confirmation_links.txt';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enlaces de Confirmación - Desarrollo</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .dev-warning {
            background: #fef3c7;
            border: 2px solid #f59e0b;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            text-align: center;
        }
        .link-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
        }
        .link-url {
            font-family: monospace;
            background: #1e293b;
            color: #10b981;
            padding: 10px;
            border-radius: 4px;
            word-break: break-all;
            margin: 8px 0;
        }
        .btn {
            background: #3b82f6;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            margin: 5px;
        }
        .btn:hover {
            background: #2563eb;
        }
        .btn-clear {
            background: #ef4444;
        }
        .btn-clear:hover {
            background: #dc2626;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="dev-warning">
            <h2>🛠️ MODO DESARROLLO</h2>
            <p>Esta página muestra los enlaces de confirmación generados para desarrollo local.</p>
            <p><strong>⚠️ Solo usar en desarrollo - No exponer en producción</strong></p>
        </div>

        <h1>📧 Enlaces de Confirmación Generados</h1>
        
        <?php if (file_exists($tempFile)): ?>
            <div style="margin: 20px 0;">
                <a href="?clear=1" class="btn btn-clear">🗑️ Limpiar Enlaces</a>
                <a href="?" class="btn">🔄 Actualizar</a>
            </div>

            <?php
            // Limpiar archivo si se solicita
            if (isset($_GET['clear'])) {
                file_put_contents($tempFile, '');
                echo "<p style='color: green;'>✅ Enlaces eliminados. <a href='?'>Actualizar página</a></p>";
            } else {
                $content = file_get_contents($tempFile);
                if (trim($content)) {
                    $lines = array_filter(explode("\n", trim($content)));
                    $lines = array_reverse($lines); // Mostrar más recientes primero
                    
                    echo "<p><strong>Total de enlaces:</strong> " . count($lines) . "</p>";
                    
                    foreach ($lines as $line) {
                        if (preg_match('/\[(.*?)\] Usuario: (.*?) \| Enlace: (.*)/', $line, $matches)) {
                            $timestamp = $matches[1];
                            $email = $matches[2];
                            $url = $matches[3];
                            
                            echo "<div class='link-item'>";
                            echo "<strong>📅 Fecha:</strong> $timestamp<br>";
                            echo "<strong>👤 Usuario:</strong> $email<br>";
                            echo "<strong>🔗 Enlace:</strong>";
                            echo "<div class='link-url'>$url</div>";
                            echo "<a href='$url' target='_blank' class='btn'>✅ Confirmar Email</a>";
                            echo "</div>";
                        }
                    }
                } else {
                    echo "<p>📭 No hay enlaces de confirmación pendientes.</p>";
                    echo "<p>💡 Registra un nuevo usuario para generar un enlace.</p>";
                }
            }
            ?>
        <?php else: ?>
            <p>📁 Archivo de enlaces temporales no encontrado.</p>
            <p>💡 Se creará automáticamente cuando registres un usuario.</p>
        <?php endif; ?>

        <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
            <h3>🧪 Acciones de Desarrollo</h3>
            <a href="/register" class="btn">📝 Ir al Registro</a>
            <a href="/test-email.php" class="btn">🧪 Probar Email</a>
            <a href="/login" class="btn">🔐 Ir al Login</a>
        </div>
    </div>
</body>
</html>
