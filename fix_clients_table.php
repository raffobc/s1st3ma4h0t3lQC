<?php
/**
 * CORREGIR TABLA DE CLIENTES
 * Archivo: C:\xampp\htdocs\hotel-system\fix_clients_table.php
 * Ejecuta este archivo para agregar la columna fecha_nacimiento
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
        
        // Verificar si la columna existe
        $stmt = $hotelDb->query("SHOW COLUMNS FROM clientes LIKE 'fecha_nacimiento'");
        $columnExists = $stmt->fetch();
        
        if (!$columnExists) {
            // Agregar columna fecha_nacimiento
            $hotelDb->exec("ALTER TABLE clientes ADD COLUMN fecha_nacimiento DATE NULL AFTER telefono");
            $fixed[] = $hotel['nombre'] . " - Columna fecha_nacimiento agregada";
        } else {
            $fixed[] = $hotel['nombre'] . " - Columna ya existe";
        }
        
        // Verificar si la columna direccion existe
        $stmt = $hotelDb->query("SHOW COLUMNS FROM clientes LIKE 'direccion'");
        $columnExists = $stmt->fetch();
        
        if (!$columnExists) {
            // Agregar columna direccion
            $hotelDb->exec("ALTER TABLE clientes ADD COLUMN direccion TEXT NULL AFTER fecha_nacimiento");
            $fixed[] = $hotel['nombre'] . " - Columna direccion agregada";
        } else {
            $fixed[] = $hotel['nombre'] . " - Columna direccion ya existe";
        }
        
    } catch (Exception $e) {
        $errors[] = $hotel['nombre'] . " - Error: " . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>✅ Tabla de Clientes Corregida</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            padding: 40px;
            margin: 0;
        }
        .container {
            max-width: 800px;
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
            margin-bottom: 20px;
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
        .error {
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
        }
        .item {
            padding: 12px;
            background: white;
            margin: 8px 0;
            border-radius: 6px;
            border-left: 3px solid #10b981;
        }
        .error-item {
            padding: 12px;
            background: white;
            margin: 8px 0;
            border-radius: 6px;
            border-left: 3px solid #ef4444;
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
        }
        .btn:hover {
            background: #5568d3;
        }
        .text-center {
            text-align: center;
            margin-top: 30px;
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
        }
        .info-box ul {
            color: #1e40af;
            margin: 10px 0 0 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">✅</div>
        <h1>Tabla de Clientes Actualizada</h1>
        
        <div class="success">
            <strong>🎉 ¡Proceso completado!</strong><br>
            Se han actualizado las tablas de clientes en todos los hoteles.
        </div>
        
        <?php if (!empty($fixed)): ?>
            <h3>✅ Actualizaciones Realizadas:</h3>
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
            <h3>📋 Columnas Agregadas:</h3>
            <ul>
                <li><strong>fecha_nacimiento</strong> - Fecha de nacimiento del cliente (DATE)</li>
                <li><strong>direccion</strong> - Dirección completa del cliente (TEXT)</li>
            </ul>
        </div>
        
        <div class="success" style="background: #dcfce7; border-color: #10b981;">
            <strong>✨ ¡Ahora puedes registrar clientes!</strong><br>
            La tabla de clientes ha sido actualizada con todos los campos necesarios.
        </div>
        
        <div class="text-center">
            <a href="/hotel/clientes/create" class="btn">
                👤 Registrar Cliente
            </a>
            <a href="/hotel/clientes" class="btn">
                👥 Ver Clientes
            </a>
            <a href="/hotel/dashboard" class="btn">
                📊 Ir al Dashboard
            </a>
        </div>
    </div>
</body>
</html>
