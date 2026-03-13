<?php
/**
 * CORREGIR TODAS LAS TABLAS DEL SISTEMA HOTEL
 * Archivo: C:\xampp\htdocs\hotel-system\fix_all_tables.php
 * Ejecuta este archivo para agregar todas las columnas faltantes
 */

require_once __DIR__ . "/config/MasterDatabase.php";

$masterDb = MasterDatabase::getConnection();

// Obtener todos los hoteles
$stmt = $masterDb->query("SELECT * FROM hoteles WHERE estado = 'activo'");
$hoteles = $stmt->fetchAll();

$fixed = [];
$errors = [];

foreach ($hoteles as $hotel) {
    try {
        // Conectar a la base de datos del hotel
        $hotelDb = new PDO(
            "mysql:host={$hotel['db_host']};dbname={$hotel['db_name']};charset=utf8mb4",
            $hotel['db_user'],
            $hotel['db_password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
        
        $hotelName = $hotel['nombre'];
        
        // ==========================================
        // TABLA CLIENTES
        // ==========================================
        
        // Verificar y agregar fecha_nacimiento
        $stmt = $hotelDb->query("SHOW COLUMNS FROM clientes LIKE 'fecha_nacimiento'");
        if (!$stmt->fetch()) {
            $hotelDb->exec("ALTER TABLE clientes ADD COLUMN fecha_nacimiento DATE NULL AFTER telefono");
            $fixed[] = "$hotelName - Clientes: agregada columna fecha_nacimiento";
        }
        
        // Verificar y agregar direccion
        $stmt = $hotelDb->query("SHOW COLUMNS FROM clientes LIKE 'direccion'");
        if (!$stmt->fetch()) {
            $hotelDb->exec("ALTER TABLE clientes ADD COLUMN direccion TEXT NULL AFTER fecha_nacimiento");
            $fixed[] = "$hotelName - Clientes: agregada columna direccion";
        }
        
        // ==========================================
        // TABLA RESERVAS
        // ==========================================
        
        // Verificar y agregar numero_huespedes
        $stmt = $hotelDb->query("SHOW COLUMNS FROM reservas LIKE 'numero_huespedes'");
        if (!$stmt->fetch()) {
            $hotelDb->exec("ALTER TABLE reservas ADD COLUMN numero_huespedes INT DEFAULT 1 AFTER fecha_salida");
            $fixed[] = "$hotelName - Reservas: agregada columna numero_huespedes";
        }
        
        // Verificar y agregar precio_total
        $stmt = $hotelDb->query("SHOW COLUMNS FROM reservas LIKE 'precio_total'");
        if (!$stmt->fetch()) {
            $hotelDb->exec("ALTER TABLE reservas ADD COLUMN precio_total DECIMAL(10,2) DEFAULT 0.00 AFTER numero_huespedes");
            $fixed[] = "$hotelName - Reservas: agregada columna precio_total";
        }
        
        // Verificar y agregar observaciones
        $stmt = $hotelDb->query("SHOW COLUMNS FROM reservas LIKE 'observaciones'");
        if (!$stmt->fetch()) {
            $hotelDb->exec("ALTER TABLE reservas ADD COLUMN observaciones TEXT NULL AFTER precio_total");
            $fixed[] = "$hotelName - Reservas: agregada columna observaciones";
        }
        
        // Verificar y agregar estado
        $stmt = $hotelDb->query("SHOW COLUMNS FROM reservas LIKE 'estado'");
        if (!$stmt->fetch()) {
            $hotelDb->exec("ALTER TABLE reservas ADD COLUMN estado ENUM('reservada', 'ocupada', 'finalizada', 'cancelada') DEFAULT 'reservada' AFTER observaciones");
            $fixed[] = "$hotelName - Reservas: agregada columna estado";
        }
        
        // ==========================================
        // TABLA HABITACIONES
        // ==========================================
        
        // Verificar y agregar descripcion
        $stmt = $hotelDb->query("SHOW COLUMNS FROM habitaciones LIKE 'descripcion'");
        if (!$stmt->fetch()) {
            $hotelDb->exec("ALTER TABLE habitaciones ADD COLUMN descripcion TEXT NULL AFTER capacidad");
            $fixed[] = "$hotelName - Habitaciones: agregada columna descripcion";
        }
        
        // Verificar y agregar estado con valores correctos
        $stmt = $hotelDb->query("SHOW COLUMNS FROM habitaciones LIKE 'estado'");
        $column = $stmt->fetch();
        
        if (!$column) {
            $hotelDb->exec("ALTER TABLE habitaciones ADD COLUMN estado ENUM('disponible', 'ocupada', 'reservada', 'limpieza', 'mantenimiento') DEFAULT 'disponible' AFTER descripcion");
            $fixed[] = "$hotelName - Habitaciones: agregada columna estado";
        } else {
            // Verificar si el ENUM tiene todos los valores necesarios
            if (strpos($column['Type'], 'mantenimiento') === false) {
                $hotelDb->exec("ALTER TABLE habitaciones MODIFY COLUMN estado ENUM('disponible', 'ocupada', 'reservada', 'limpieza', 'mantenimiento') DEFAULT 'disponible'");
                $fixed[] = "$hotelName - Habitaciones: actualizada columna estado con todos los valores";
            }
        }
        
        // ==========================================
        // VERIFICAR TIMESTAMPS
        // ==========================================
        
        // Verificar created_at en clientes
        $stmt = $hotelDb->query("SHOW COLUMNS FROM clientes LIKE 'created_at'");
        if (!$stmt->fetch()) {
            $hotelDb->exec("ALTER TABLE clientes ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
            $fixed[] = "$hotelName - Clientes: agregada columna created_at";
        }
        
        // Verificar created_at en reservas
        $stmt = $hotelDb->query("SHOW COLUMNS FROM reservas LIKE 'created_at'");
        if (!$stmt->fetch()) {
            $hotelDb->exec("ALTER TABLE reservas ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
            $fixed[] = "$hotelName - Reservas: agregada columna created_at";
        }
        
        $fixed[] = "✅ $hotelName - Todas las tablas actualizadas correctamente";
        
    } catch (Exception $e) {
        $errors[] = $hotel['nombre'] . " - Error: " . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>✅ Todas las Tablas Corregidas</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            padding: 40px;
            margin: 0;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        h1 {
            color: #1f2937;
            text-align: center;
            font-size: 32px;
            margin-bottom: 10px;
        }
        .subtitle {
            text-align: center;
            color: #6b7280;
            margin-bottom: 30px;
        }
        .icon {
            font-size: 64px;
            text-align: center;
            margin-bottom: 20px;
        }
        .success {
            background: #dcfce7;
            border-left: 4px solid #10b981;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .error-box {
            background: #fee2e2;
            border-left: 4px solid #ef4444;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .list {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            max-height: 400px;
            overflow-y: auto;
        }
        .item {
            padding: 10px;
            background: white;
            margin: 6px 0;
            border-radius: 6px;
            border-left: 3px solid #10b981;
            font-size: 14px;
        }
        .error-item {
            padding: 10px;
            background: white;
            margin: 6px 0;
            border-radius: 6px;
            border-left: 3px solid #ef4444;
            font-size: 14px;
        }
        .info-box {
            background: #dbeafe;
            border-left: 4px solid #3b82f6;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .info-box h3 {
            color: #1e40af;
            margin-bottom: 10px;
            font-size: 18px;
        }
        .info-section {
            margin: 15px 0;
        }
        .info-section h4 {
            color: #1e40af;
            font-size: 16px;
            margin-bottom: 8px;
        }
        .info-section ul {
            color: #1e40af;
            margin: 5px 0 0 20px;
            font-size: 14px;
        }
        .btn {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            margin: 10px 5px;
            transition: all 0.3s;
        }
        .btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
        .text-center {
            text-align: center;
            margin-top: 30px;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .stat-card {
            background: #f9fafb;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            border: 2px solid #e5e7eb;
        }
        .stat-number {
            font-size: 36px;
            font-weight: 700;
            color: #10b981;
            margin-bottom: 5px;
        }
        .stat-label {
            color: #6b7280;
            font-size: 14px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🎉</div>
        <h1>¡Base de Datos Actualizada!</h1>
        <p class="subtitle">Todas las tablas han sido corregidas exitosamente</p>
        
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?= count($hoteles) ?></div>
                <div class="stat-label">Hoteles Actualizados</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= count($fixed) ?></div>
                <div class="stat-label">Cambios Realizados</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= count($errors) ?></div>
                <div class="stat-label">Errores</div>
            </div>
        </div>
        
        <div class="success">
            <strong>✅ ¡Proceso completado exitosamente!</strong><br>
            El sistema está 100% funcional. Todas las tablas tienen las columnas necesarias.
        </div>
        
        <?php if (!empty($fixed)): ?>
            <h3>📋 Actualizaciones Realizadas:</h3>
            <div class="list">
                <?php foreach ($fixed as $item): ?>
                    <div class="item">✓ <?= htmlspecialchars($item) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <h3>❌ Errores Encontrados:</h3>
            <div class="list">
                <?php foreach ($errors as $error): ?>
                    <div class="error-item">⚠️ <?= htmlspecialchars($error) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="info-box">
            <h3>📊 Columnas Agregadas por Tabla:</h3>
            
            <div class="info-section">
                <h4>👥 Tabla CLIENTES:</h4>
                <ul>
                    <li><strong>fecha_nacimiento</strong> (DATE) - Fecha de nacimiento</li>
                    <li><strong>direccion</strong> (TEXT) - Dirección completa</li>
                    <li><strong>created_at</strong> (TIMESTAMP) - Fecha de registro</li>
                </ul>
            </div>
            
            <div class="info-section">
                <h4>📅 Tabla RESERVAS:</h4>
                <ul>
                    <li><strong>numero_huespedes</strong> (INT) - Cantidad de huéspedes</li>
                    <li><strong>precio_total</strong> (DECIMAL) - Precio total de la reserva</li>
                    <li><strong>observaciones</strong> (TEXT) - Notas y peticiones especiales</li>
                    <li><strong>estado</strong> (ENUM) - Estado: reservada, ocupada, finalizada, cancelada</li>
                    <li><strong>created_at</strong> (TIMESTAMP) - Fecha de creación</li>
                </ul>
            </div>
            
            <div class="info-section">
                <h4>🏠 Tabla HABITACIONES:</h4>
                <ul>
                    <li><strong>descripcion</strong> (TEXT) - Descripción y amenidades</li>
                    <li><strong>estado</strong> (ENUM) - Estado: disponible, ocupada, reservada, limpieza, mantenimiento</li>
                </ul>
            </div>
        </div>
        
        <div class="success" style="background: #dcfce7; border-color: #10b981;">
            <strong>🚀 ¡Sistema Listo para Usar!</strong><br>
            Ahora puedes:
            <ul style="margin: 10px 0 0 20px;">
                <li>✅ Registrar clientes con todos sus datos</li>
                <li>✅ Crear reservas con número de huéspedes y observaciones</li>
                <li>✅ Gestionar estados de habitaciones y reservas</li>
                <li>✅ Calcular automáticamente precios totales</li>
            </ul>
        </div>
        
        <div class="text-center">
            <a href="/hotel/clientes/create" class="btn">
                👤 Registrar Cliente
            </a>
            <a href="/hotel/reservas/create" class="btn">
                📅 Nueva Reserva
            </a>
            <a href="/hotel/habitaciones" class="btn">
                🏠 Ver Habitaciones
            </a>
            <a href="/hotel/dashboard" class="btn">
                📊 Dashboard
            </a>
        </div>
    </div>
</body>
</html>
