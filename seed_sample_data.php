<?php
/**
 * Script para insertar datos de prueba en la base de datos del hotel
 * Ejecutar desde: http://localhost:8000/seed_sample_data.php
 */

try {
    $dbConnection = new PDO(
        "mysql:host=localhost;dbname=hotel_master;charset=utf8mb4",
        "root",
        "",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "<h1>🏨 Insertando Datos de Prueba...</h1>";

    // Limpiar tablas (opcional)
    // $dbConnection->exec("DELETE FROM reservas");
    // $dbConnection->exec("DELETE FROM clientes");
    // $dbConnection->exec("DELETE FROM habitaciones");

    // 1. Insertar Habitaciones
    echo "<h2>Habitaciones</h2>";
    $habitaciones = [
        ['numero' => '101', 'tipo' => 'Habitación Simple', 'capacidad' => 1, 'precio' => 80, 'descripcion' => 'Confortable habitación con cama individual, baño privado y aire acondicionado.', 'estado' => 'disponible'],
        ['numero' => '102', 'tipo' => 'Habitación Doble', 'capacidad' => 2, 'precio' => 120, 'descripcion' => 'Espaciosa habitación con cama doble, TV, minibar y balcón con vista a la ciudad.', 'estado' => 'disponible'],
        ['numero' => '103', 'tipo' => 'Suite Familiar', 'capacidad' => 4, 'precio' => 180, 'descripcion' => 'Lujosa suite con sala de estar, dormitorio principal, baño jacuzzi y acceso a la terraza.', 'estado' => 'ocupada'],
        ['numero' => '104', 'tipo' => 'Habitación Deluxe', 'capacidad' => 2, 'precio' => 150, 'descripcion' => 'Elegante habitación con decoración premium, servicio de concierge y minibar completo.', 'estado' => 'disponible'],
        ['numero' => '105', 'tipo' => 'Habitación Estándar', 'capacidad' => 2, 'precio' => 100, 'descripcion' => 'Habitación moderna con todo lo necesario para una estancia cómoda.', 'estado' => 'limpieza'],
    ];

    $stmt = $dbConnection->prepare("
        INSERT INTO habitaciones (numero_habitacion, tipo, capacidad, precio_noche, descripcion, estado, creado_em, actualizado_em)
        VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
        ON DUPLICATE KEY UPDATE
        tipo = VALUES(tipo),
        capacidad = VALUES(capacidad),
        precio_noche = VALUES(precio_noche),
        descripcion = VALUES(descripcion),
        estado = VALUES(estado)
    ");

    foreach ($habitaciones as $h) {
        $stmt->execute([$h['numero'], $h['tipo'], $h['capacidad'], $h['precio'], $h['descripcion'], $h['estado']]);
        echo "✓ Habitación {$h['numero']} ({$h['tipo']})<br>";
    }

    // 2. Insertar Clientes
    echo "<h2>Clientes</h2>";
    $clientes = [
        ['nombre' => 'Juan Pérez García', 'documento' => '12345678', 'email' => 'juan.perez@email.com', 'telefono' => '987654321', 'ciudad' => 'Lima', 'pais' => 'Perú'],
        ['nombre' => 'María Rodríguez López', 'documento' => '87654321', 'email' => 'maria.rodriguez@email.com', 'telefono' => '987654322', 'ciudad' => 'Arequipa', 'pais' => 'Perú'],
        ['nombre' => 'Carlos González Martínez', 'documento' => '11223344', 'email' => 'carlos.gonzalez@email.com', 'telefono' => '987654323', 'ciudad' => 'Cusco', 'pais' => 'Perú'],
        ['nombre' => 'Ana de Silva Flores', 'documento' => '55667788', 'email' => 'ana.silva@email.com', 'telefono' => '987654324', 'ciudad' => 'Trujillo', 'pais' => 'Perú'],
        ['nombre' => 'Roberto Sánchez Díaz', 'documento' => '99001122', 'email' => 'roberto.sanchez@email.com', 'telefono' => '987654325', 'ciudad' => 'Chiclayo', 'pais' => 'Perú'],
    ];

    $stmt = $dbConnection->prepare("
        INSERT INTO clientes (nombre, documento, email, telefono, ciudad, pais, creado_em, actualizado_em)
        VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
        ON DUPLICATE KEY UPDATE
        nombre = VALUES(nombre),
        email = VALUES(email),
        telefono = VALUES(telefono),
        ciudad = VALUES(ciudad),
        pais = VALUES(pais)
    ");

    foreach ($clientes as $c) {
        $stmt->execute([$c['nombre'], $c['documento'], $c['email'], $c['telefono'], $c['ciudad'], $c['pais']]);
        echo "✓ Cliente: {$c['nombre']}<br>";
    }

    // 3. Insertar Reservas
    echo "<h2>Reservas</h2>";

    // Obtener IDs de clientes y habitaciones
    $clientesRes = $dbConnection->query("SELECT id FROM clientes LIMIT 5");
    $clienteIds = $clientesRes->fetchAll(PDO::FETCH_COLUMN);

    $habitacionesRes = $dbConnection->query("SELECT id FROM habitaciones WHERE numero_habitacion IN ('101', '102', '103', '104') LIMIT 4");
    $habitacionIds = $habitacionesRes->fetchAll(PDO::FETCH_COLUMN);

    if (count($clienteIds) > 0 && count($habitacionIds) > 0) {
        $reservas = [
            [
                'cliente_id' => $clienteIds[0],
                'habitacion_id' => $habitacionIds[0],
                'fecha_entrada' => date('Y-m-d', strtotime('+1 day')),
                'fecha_salida' => date('Y-m-d', strtotime('+4 days')),
                'numero_huespedes' => 2,
                'precio_total' => 240,
                'estado' => 'reservada'
            ],
            [
                'cliente_id' => $clienteIds[1],
                'habitacion_id' => $habitacionIds[1],
                'fecha_entrada' => date('Y-m-d'),
                'fecha_salida' => date('Y-m-d', strtotime('+2 days')),
                'numero_huespedes' => 2,
                'precio_total' => 240,
                'estado' => 'ocupada'
            ],
            [
                'cliente_id' => $clienteIds[2],
                'habitacion_id' => $habitacionIds[2],
                'fecha_entrada' => date('Y-m-d', strtotime('-3 days')),
                'fecha_salida' => date('Y-m-d', strtotime('-1 day')),
                'numero_huespedes' => 4,
                'precio_total' => 540,
                'estado' => 'finalizada'
            ],
            [
                'cliente_id' => $clienteIds[3],
                'habitacion_id' => $habitacionIds[3],
                'fecha_entrada' => date('Y-m-d', strtotime('+5 days')),
                'fecha_salida' => date('Y-m-d', strtotime('+7 days')),
                'numero_huespedes' => 2,
                'precio_total' => 300,
                'estado' => 'reservada'
            ],
            [
                'cliente_id' => $clienteIds[4],
                'habitacion_id' => $habitacionIds[0],
                'fecha_entrada' => date('Y-m-d', strtotime('+10 days')),
                'fecha_salida' => date('Y-m-d', strtotime('+12 days')),
                'numero_huespedes' => 1,
                'precio_total' => 160,
                'estado' => 'cancelada'
            ],
        ];

        $stmt = $dbConnection->prepare("
            INSERT INTO reservas (cliente_id, habitacion_id, fecha_entrada, fecha_salida, numero_huespedes, precio_total, estado, creado_em, actualizado_em)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");

        $count = 1;
        foreach ($reservas as $r) {
            $stmt->execute([
                $r['cliente_id'],
                $r['habitacion_id'],
                $r['fecha_entrada'],
                $r['fecha_salida'],
                $r['numero_huespedes'],
                $r['precio_total'],
                $r['estado']
            ]);
            echo "✓ Reserva #{$count} creada ({$r['estado']})<br>";
            $count++;
        }
    }

    echo "<hr><h2>✅ Datos de prueba insertados exitosamente</h2>";
    echo "<p><a href='/hotel/login'>Ir al login del hotel</a></p>";
    echo "<p><strong>Usuario:</strong> admin@hotel.com | <strong>Contraseña:</strong> admin123</p>";

} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</h2>";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seed - Datos de Prueba</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }
        h1, h2 {
            color: #1f2937;
        }
        a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        a:hover {
            text-decoration: underline;
        }
        hr {
            border: none;
            border-top: 2px solid #e5e7eb;
            margin: 30px 0;
        }
    </style>
</head>
<body>
</body>
</html>
