<?php
/**
 * Script de prueba para registrar un usuario vendedor
 */

require_once 'api/config.php';

try {
    // Crear conexión PDO
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    // Datos del vendedor de prueba
    $vendorData = [
        'first_name' => 'María',
        'last_name' => 'Vendedora',
        'email' => 'vendedora@test.com',
        'password' => 'password123',
        'phone' => '622123456',
        'island' => 'Gran Canaria',
        'city' => 'Las Palmas',
        'user_type' => 'business', // Este será nuestro vendedor
        'accept_terms' => true,
        'accept_newsletter' => false
    ];
    
    // Verificar si el usuario ya existe
    $stmt = $pdo->prepare("SELECT id FROM ecc_users WHERE email = ?");
    $stmt->execute([$vendorData['email']]);
    $existingUser = $stmt->fetch();
    
    if ($existingUser) {
        echo "✅ El usuario vendedor ya existe con ID: " . $existingUser['id'] . "\n";
        exit;
    }
    
    // Hash de la contraseña
    $passwordHash = password_hash($vendorData['password'], PASSWORD_DEFAULT);
    
    // Insertar el usuario
    $stmt = $pdo->prepare("
        INSERT INTO ecc_users (
            first_name, last_name, email, password_hash, phone, 
            island, city, user_type, accept_terms, accept_newsletter,
            email_verified
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
    ");
    
    $result = $stmt->execute([
        $vendorData['first_name'],
        $vendorData['last_name'],
        $vendorData['email'],
        $passwordHash,
        $vendorData['phone'],
        $vendorData['island'],
        $vendorData['city'],
        $vendorData['user_type'],
        $vendorData['accept_terms'] ? 1 : 0,
        $vendorData['accept_newsletter'] ? 1 : 0
    ]);
    
    if ($result) {
        $userId = $pdo->lastInsertId();
        echo "✅ Usuario vendedor creado exitosamente!\n";
        echo "📧 Email: " . $vendorData['email'] . "\n";
        echo "🔑 Contraseña: " . $vendorData['password'] . "\n";
        echo "🆔 ID de usuario: $userId\n";
        echo "🏪 Tipo: " . $vendorData['user_type'] . "\n";
        echo "🏝️ Isla: " . $vendorData['island'] . "\n";
        
        // También crear algunas categorías de ejemplo si no existen
        $categories = [
            ['name' => 'Electrónicos', 'description' => 'Dispositivos electrónicos reciclados'],
            ['name' => 'Ropa', 'description' => 'Ropa de segunda mano'],
            ['name' => 'Muebles', 'description' => 'Muebles y decoración'],
            ['name' => 'Libros', 'description' => 'Libros usados'],
            ['name' => 'Deportes', 'description' => 'Artículos deportivos']
        ];
        
        foreach ($categories as $category) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO product_categories (name, description) VALUES (?, ?)");
            $stmt->execute([$category['name'], $category['description']]);
        }
        
        echo "📦 Categorías de productos creadas\n";
        
    } else {
        echo "❌ Error al crear el usuario vendedor\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
