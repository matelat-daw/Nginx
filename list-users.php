<?php
/**
 * Ver todos los usuarios registrados
 */

require_once 'api/config.php';

try {
    $pdo = getDBConnection();
    
    echo "<h1>👥 Usuarios registrados en ecc_users</h1>";
    
    $stmt = $pdo->query("SELECT id, first_name, last_name, email, email_verified, created_at FROM ecc_users ORDER BY created_at DESC");
    $users = $stmt->fetchAll();
    
    if ($users) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>Nombre</th><th>Apellido</th><th>Email</th><th>Verificado</th><th>Creado</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['first_name']}</td>";
            echo "<td>{$user['last_name']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>" . ($user['email_verified'] ? '✅' : '❌') . "</td>";
            echo "<td>{$user['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>❌ No hay usuarios registrados</p>";
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Error: " . $e->getMessage() . "</h2>";
}
?>
