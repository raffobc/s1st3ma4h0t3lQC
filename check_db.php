<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=hotel_master;charset=utf8mb4', 'root', '');
    echo "Conexión exitosa a MySQL y base de datos hotel_master\n";

    $stmt = $pdo->query('SHOW TABLES');
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tablas en hotel_master: " . implode(', ', $tables) . "\n";

    if (in_array('super_usuarios', $tables)) {
        $stmt = $pdo->query('SELECT COUNT(*) as count FROM super_usuarios');
        $count = $stmt->fetch()['count'];
        echo "Usuarios super admin: " . $count . "\n";

        if ($count == 0) {
            echo "No hay usuarios super admin. Creando usuario por defecto...\n";
            $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO super_usuarios (nombre, email, password, activo) VALUES (?, ?, ?, 1)");
            $stmt->execute(['Super Admin', 'admin@hotel.com', $hashedPassword]);
            echo "Usuario creado: admin@hotel.com / admin123\n";
        }
    }

} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage() . "\n";

    // Intentar crear la base de datos si no existe
    if (strpos($e->getMessage(), 'Unknown database') !== false) {
        echo "La base de datos no existe. Intentando crearla...\n";
        try {
            $pdo = new PDO('mysql:host=localhost;charset=utf8mb4', 'root', '');
            $pdo->exec("CREATE DATABASE IF NOT EXISTS hotel_master CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            echo "Base de datos hotel_master creada exitosamente.\n";
            echo "Ejecuta el script de instalación: http://localhost:8000/install.php\n";
        } catch (PDOException $e2) {
            echo "Error creando base de datos: " . $e2->getMessage() . "\n";
        }
    }
}
?>