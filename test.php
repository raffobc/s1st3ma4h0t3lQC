<?php
echo "<h1>Prueba de Conexión MySQL</h1>";

try {
    $pdo = new PDO("mysql:host=localhost", "root", "");
    echo "✅ <strong>Conexión exitosa a MySQL</strong><br><br>";
    echo "Versión PHP: " . PHP_VERSION . "<br>";
    echo "PDO MySQL: " . (extension_loaded('pdo_mysql') ? '✅ OK' : '❌ FALTA') . "<br>";
    
    // Intentar crear la base de datos
    $pdo->exec("CREATE DATABASE IF NOT EXISTS hotel_master CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Base de datos 'hotel_master': ✅ Creada/Existe<br>";
    
} catch (PDOException $e) {
    echo "❌ <strong>Error:</strong> " . $e->getMessage() . "<br><br>";
    echo "<strong>Verifica:</strong><br>";
    echo "1. MySQL está corriendo en XAMPP<br>";
    echo "2. Ve a http://localhost/phpmyadmin para confirmar<br>";
}
?>
