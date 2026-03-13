<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/MasterDatabase.php';

echo "Inicializando base de datos del sistema hotel...\n";
echo str_repeat("=", 50) . "\n\n";

try {
    require __DIR__ . '/create_db_mysql.php';
    $connection = MasterDatabase::getConnection();

    echo "✓ Base de datos inicializada exitosamente\n";
    echo "✓ Tipo de base de datos: MYSQL\n";
    echo "✓ Sistema listo para usar\n\n";

    // Mostrar estadísticas
    echo "Estadísticas:\n";
    echo str_repeat("-", 50) . "\n";

    $stmt = $connection->prepare("SELECT COUNT(*) as count FROM usuarios");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "- Usuarios registrados: " . $result['count'] . "\n";

    $stmt = $connection->prepare("SELECT COUNT(*) as count FROM habitaciones");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "- Habitaciones: " . $result['count'] . "\n";

    $stmt = $connection->prepare("SELECT COUNT(*) as count FROM clientes");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "- Clientes: " . $result['count'] . "\n";

    $stmt = $connection->prepare("SELECT COUNT(*) as count FROM servicios");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "- Servicios disponibles: " . $result['count'] . "\n";

    echo "\n✓ Credenciales de prueba:\n";
    echo "  Email: admin@hotel.com\n";
    echo "  Contraseña: admin123\n";
    echo "\n✓ URL:\n";
    echo "  http://localhost/hotel-system/public/hotel/login\n";

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}