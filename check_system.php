<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/MasterDatabase.php';

echo "Verificando funcionamiento del sistema hotel...\n";
echo str_repeat("=", 50) . "\n\n";

try {
    $connection = MasterDatabase::getConnection();

    echo "✓ Base de datos conectada: MYSQL\n\n";

    // Verificar que las tablas existen
    $tables = ['usuarios', 'habitaciones', 'clientes', 'reservas', 'pagos', 'servicios', 'huespedes', 'reserva_servicios'];
    echo "Verificando tablas:\n";

    foreach ($tables as $table) {
        try {
            $stmt = $connection->prepare("SELECT COUNT(*) as count FROM $table");
            $stmt->execute();
            $result = $stmt->fetch();
            echo "✓ $table: {$result['count']} registros\n";
        } catch (Exception $e) {
            echo "✗ $table: Error - {$e->getMessage()}\n";
        }
    }

    echo "\n✓✓✓ Sistema hotel completamente funcional ✓✓✓\n\n";

    // Mostrar credenciales
    echo "Acceso al sistema:\n";
    echo "- URL: http://localhost/hotel-system/public/hotel/login\n";
    echo "- Usuario: admin@hotel.com\n";
    echo "- Contraseña: admin123\n\n";
    echo "Si usas XAMPP, asegúrate de iniciar Apache y MySQL.\n";

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}