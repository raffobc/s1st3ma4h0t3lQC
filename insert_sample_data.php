<?php
try {
    $dbConnection = new PDO(
        'mysql:host=localhost;dbname=hotel_master;charset=utf8mb4',
        'root',
        '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Insertar Habitaciones
    $habitaciones = [
        ['numero' => '101', 'tipo' => 'Habitación Simple', 'capacidad' => 1, 'precio' => 80, 'descripcion' => 'Confortable habitación con cama individual, baño privado y aire acondicionado.', 'estado' => 'disponible'],
        ['numero' => '102', 'tipo' => 'Habitación Doble', 'capacidad' => 2, 'precio' => 120, 'descripcion' => 'Espaciosa habitación con cama doble, TV, minibar y balcón con vista a la ciudad.', 'estado' => 'disponible'],
        ['numero' => '103', 'tipo' => 'Suite Familiar', 'capacidad' => 4, 'precio' => 180, 'descripcion' => 'Lujosa suite con sala de estar, dormitorio principal, baño jacuzzi y acceso a la terraza.', 'estado' => 'ocupada'],
        ['numero' => '104', 'tipo' => 'Habitación Deluxe', 'capacidad' => 2, 'precio' => 150, 'descripcion' => 'Elegante habitación con decoración premium, servicio de concierge y minibar completo.', 'estado' => 'disponible'],
        ['numero' => '105', 'tipo' => 'Habitación Estándar', 'capacidad' => 2, 'precio' => 100, 'descripcion' => 'Habitación moderna con todo lo necesario para una estancia cómoda.', 'estado' => 'limpieza'],
    ];

    $stmt = $dbConnection->prepare(
        'INSERT INTO habitaciones (numero_habitacion, tipo, capacidad, precio_noche, descripcion, estado, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
        ON DUPLICATE KEY UPDATE tipo = VALUES(tipo), capacidad = VALUES(capacidad), precio_noche = VALUES(precio_noche)'
    );

    foreach ($habitaciones as $h) {
        $stmt->execute([$h['numero'], $h['tipo'], $h['capacidad'], $h['precio'], $h['descripcion'], $h['estado']]);
    }

    echo "✓ 5 Habitaciones insertadas\n";

    // Insertar Clientes
    $clientes = [
        ['nombre' => 'Juan Pérez García', 'documento' => '12345678', 'email' => 'juan.perez@email.com', 'telefono' => '987654321', 'ciudad' => 'Lima', 'pais' => 'Perú'],
        ['nombre' => 'María Rodríguez López', 'documento' => '87654321', 'email' => 'maria.rodriguez@email.com', 'telefono' => '987654322', 'ciudad' => 'Arequipa', 'pais' => 'Perú'],
        ['nombre' => 'Carlos González Martínez', 'documento' => '11223344', 'email' => 'carlos.gonzalez@email.com', 'telefono' => '987654323', 'ciudad' => 'Cusco', 'pais' => 'Perú'],
        ['nombre' => 'Ana de Silva Flores', 'documento' => '55667788', 'email' => 'ana.silva@email.com', 'telefono' => '987654324', 'ciudad' => 'Trujillo', 'pais' => 'Perú'],
        ['nombre' => 'Roberto Sánchez Díaz', 'documento' => '99001122', 'email' => 'roberto.sanchez@email.com', 'telefono' => '987654325', 'ciudad' => 'Chiclayo', 'pais' => 'Perú'],
    ];

    $stmt = $dbConnection->prepare(
        'INSERT INTO clientes (nombre, documento, email, telefono, ciudad, pais, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
        ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), email = VALUES(email), telefono = VALUES(telefono)'
    );

    foreach ($clientes as $c) {
        $stmt->execute([$c['nombre'], $c['documento'], $c['email'], $c['telefono'], $c['ciudad'], $c['pais']]);
    }

    echo "✓ 5 Clientes insertados\n";

    // Obtener IDs
    $clientesRes = $dbConnection->query('SELECT id FROM clientes LIMIT 5');
    $clienteIds = $clientesRes->fetchAll(PDO::FETCH_COLUMN);

    $habitacionesRes = $dbConnection->query("SELECT id FROM habitaciones WHERE numero_habitacion IN ('101', '102', '103', '104')");
    $habitacionIds = $habitacionesRes->fetchAll(PDO::FETCH_COLUMN);

    if (count($clienteIds) > 0 && count($habitacionIds) > 0) {
        $stmt = $dbConnection->prepare(
            'INSERT INTO reservas (cliente_id, habitacion_id, fecha_entrada, fecha_salida, total, estado, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())'
        );

        $stmt->execute([$clienteIds[0], $habitacionIds[0], date('Y-m-d', strtotime('+1 day')), date('Y-m-d', strtotime('+4 days')), 240, 'reservada']);
        $stmt->execute([$clienteIds[1], $habitacionIds[1], date('Y-m-d'), date('Y-m-d', strtotime('+2 days')), 240, 'ocupada']);
        $stmt->execute([$clienteIds[2], $habitacionIds[2], date('Y-m-d', strtotime('-3 days')), date('Y-m-d', strtotime('-1 day')), 540, 'finalizada']);
        $stmt->execute([$clienteIds[3], $habitacionIds[3], date('Y-m-d', strtotime('+5 days')), date('Y-m-d', strtotime('+7 days')), 300, 'reservada']);

        echo "✓ 4 Reservas insertadas\n";
    }

    echo "\n✅ ¡Datos de prueba insertados exitosamente!\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
