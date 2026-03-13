<?php
/**
 * FILTRAR HABITACIONES POR CAPACIDAD
 * Archivo: C:\xampp\htdocs\hotel-system\add_capacity_filter.php
 */

$baseDir = __DIR__;
$created = [];

// Actualizar el método walkin en el controlador
$controllerPath = $baseDir . "/controllers/HotelReservationsController.php";
$controllerContent = file_get_contents($controllerPath);

// Buscar y reemplazar el método walkin
$newWalkinMethod = <<<'EOD'
    public function walkin() {
        // Vista de check-in directo
        
        // Obtener clientes para selector rápido
        $clientes = $this->hotelDb->query("
            SELECT * FROM clientes 
            ORDER BY created_at DESC 
            LIMIT 50
        ")->fetchAll();
        
        // Obtener habitaciones disponibles solo si hay fecha de salida
        $habitaciones = [];
        $fechaSalida = $_GET['fecha_salida'] ?? null;
        $numHuespedes = $_GET['numero_huespedes'] ?? 1;
        
        if ($fechaSalida) {
            $fechaEntrada = date('Y-m-d'); // HOY
            
            // Buscar habitaciones que NO tienen reservas activas en ese rango
            // Y que tengan capacidad suficiente para los huéspedes
            $sql = "
                SELECT h.* 
                FROM habitaciones h
                WHERE h.capacidad >= ?
                AND h.id NOT IN (
                    SELECT r.habitacion_id 
                    FROM reservas r 
                    WHERE r.estado IN ('reservada', 'ocupada')
                    AND (
                        (r.fecha_entrada <= ? AND r.fecha_salida >= ?)
                        OR (r.fecha_entrada <= ? AND r.fecha_salida >= ?)
                        OR (r.fecha_entrada >= ? AND r.fecha_salida <= ?)
                    )
                )
                AND h.estado NOT IN ('mantenimiento')
                ORDER BY h.numero_habitacion
            ";
            
            $stmt = $this->hotelDb->prepare($sql);
            $stmt->execute([
                $numHuespedes,
                $fechaEntrada, $fechaEntrada,
                $fechaSalida, $fechaSalida,
                $fechaEntrada, $fechaSalida
            ]);
            $habitaciones = $stmt->fetchAll();
        }
        
        $config = [
            'hora_checkin_estandar' => $this->horaCheckInEstandar,
            'hora_checkout_estandar' => $this->horaCheckOutEstandar,
            'hora_early_checkin' => $this->horaEarlyCheckIn,
            'hora_late_checkout' => $this->horaLateCheckOut,
            'cargo_early_checkin' => $this->cargoEarlyCheckIn,
            'cargo_late_checkout' => $this->cargoLateCheckOut
        ];
        
        require_once BASE_PATH . "/views/hotel/walkin_form.php";
    }
EOD;

// Reemplazar el método completo
$pattern = '/public function walkin\(\) \{[\s\S]*?require_once BASE_PATH[^;]+;[\s\S]*?\n    \}/';
$controllerContent = preg_replace($pattern, $newWalkinMethod, $controllerContent);

file_put_contents($controllerPath, $controllerContent);
$created[] = "HotelReservationsController.php - Filtro por capacidad agregado";

// También actualizar el método create para reservas normales
$newCreateMethod = <<<'EOD'
    public function create() {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $clienteId = $_POST["cliente_id"];
            $habitacionId = $_POST["habitacion_id"];
            $fechaEntrada = $_POST["fecha_entrada"];
            $fechaSalida = $_POST["fecha_salida"];
            $numHuespedes = $_POST["numero_huespedes"];
            $observaciones = $_POST["observaciones"] ?? "";
            
            $earlyCheckIn = isset($_POST["early_checkin"]) ? 1 : 0;
            $lateCheckOut = isset($_POST["late_checkout"]) ? 1 : 0;
            
            $horaEntrada = $earlyCheckIn ? $this->horaEarlyCheckIn : $this->horaCheckInEstandar;
            $horaSalida = $lateCheckOut ? $this->horaLateCheckOut : $this->horaCheckOutEstandar;
            
            $entrada = new DateTime($fechaEntrada);
            $salida = new DateTime($fechaSalida);
            $dias = $entrada->diff($salida)->days;
            
            $stmt = $this->hotelDb->prepare("SELECT precio_noche FROM habitaciones WHERE id = ?");
            $stmt->execute([$habitacionId]);
            $precioNoche = $stmt->fetch()["precio_noche"];
            
            $cargoExtra = 0;
            if ($earlyCheckIn) $cargoExtra += $this->cargoEarlyCheckIn;
            if ($lateCheckOut) $cargoExtra += $this->cargoLateCheckOut;
            
            $total = ($dias * $precioNoche) + $cargoExtra;
            
            $stmt = $this->hotelDb->prepare("
                INSERT INTO reservas (
                    cliente_id, habitacion_id, fecha_entrada, hora_entrada, 
                    fecha_salida, hora_salida, numero_huespedes, precio_total, 
                    observaciones, early_checkin, late_checkout, cargo_extra, estado
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'reservada')
            ");
            
            $stmt->execute([
                $clienteId, $habitacionId, $fechaEntrada, $horaEntrada,
                $fechaSalida, $horaSalida, $numHuespedes, $total,
                $observaciones, $earlyCheckIn, $lateCheckOut, $cargoExtra
            ]);
            
            $stmt = $this->hotelDb->prepare("UPDATE habitaciones SET estado = 'reservada' WHERE id = ?");
            $stmt->execute([$habitacionId]);
            
            header("Location: /hotel/reservas");
            exit;
        }
        
        $clientes = $this->hotelDb->query("SELECT * FROM clientes ORDER BY nombre")->fetchAll();
        $habitaciones = [];
        $fechaEntrada = $_GET["fecha_entrada"] ?? null;
        $fechaSalida = $_GET["fecha_salida"] ?? null;
        $numHuespedes = $_GET["numero_huespedes"] ?? 1;
        
        if ($fechaEntrada && $fechaSalida) {
            $sql = "
                SELECT h.* 
                FROM habitaciones h
                WHERE h.capacidad >= ?
                AND h.id NOT IN (
                    SELECT r.habitacion_id 
                    FROM reservas r 
                    WHERE r.estado IN ('reservada', 'ocupada')
                    AND (
                        (r.fecha_entrada <= ? AND r.fecha_salida >= ?)
                        OR (r.fecha_entrada <= ? AND r.fecha_salida >= ?)
                        OR (r.fecha_entrada >= ? AND r.fecha_salida <= ?)
                    )
                )
                AND h.estado NOT IN ('mantenimiento')
                ORDER BY h.numero_habitacion
            ";
            
            $stmt = $this->hotelDb->prepare($sql);
            $stmt->execute([
                $numHuespedes,
                $fechaEntrada, $fechaEntrada,
                $fechaSalida, $fechaSalida,
                $fechaEntrada, $fechaSalida
            ]);
            $habitaciones = $stmt->fetchAll();
        }
        
        $config = [
            'hora_checkin_estandar' => $this->horaCheckInEstandar,
            'hora_checkout_estandar' => $this->horaCheckOutEstandar,
            'hora_early_checkin' => $this->horaEarlyCheckIn,
            'hora_late_checkout' => $this->horaLateCheckOut,
            'cargo_early_checkin' => $this->cargoEarlyCheckIn,
            'cargo_late_checkout' => $this->cargoLateCheckOut
        ];
        
        require_once BASE_PATH . "/views/hotel/reservation_form.php";
    }
EOD;

$pattern = '/public function create\(\) \{[\s\S]*?require_once BASE_PATH[^;]+reservation_form[^;]+;[\s\S]*?\n    \}/';
$controllerContent = preg_replace($pattern, $newCreateMethod, $controllerContent);

file_put_contents($controllerPath, $controllerContent);
$created[] = "HotelReservationsController.php - Filtro por capacidad en reservas normales";

// Actualizar formulario de reserva normal para incluir número de huéspedes en la búsqueda
$reservationFormPath = $baseDir . "/views/hotel/reservation_form.php";
$reservationFormContent = file_get_contents($reservationFormPath);

// Agregar campo oculto de número de huéspedes en el formulario de búsqueda de fechas
$reservationFormContent = str_replace(
    '<form method="GET" action="">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Fecha de Entrada *</label>',
    '<form method="GET" action="" id="searchDatesForm">
                <input type="hidden" name="numero_huespedes" value="1" id="numHuespedesHidden">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Fecha de Entrada *</label>',
    $reservationFormContent
);

// Agregar script para sincronizar número de huéspedes
if (strpos($reservationFormContent, 'syncNumHuespedes') === false) {
    $reservationFormContent = str_replace(
        '<script>
document.getElementById(\'fechaEntrada\')',
        '<script>
// Sincronizar número de huéspedes con el campo oculto
function syncNumHuespedes() {
    const numHuespedesInput = document.querySelector(\'input[name="numero_huespedes"]\');
    const hiddenInput = document.getElementById(\'numHuespedesHidden\');
    if (numHuespedesInput && hiddenInput) {
        hiddenInput.value = numHuespedesInput.value;
    }
}

// Agregar listener al formulario de búsqueda
const searchForm = document.getElementById(\'searchDatesForm\');
if (searchForm) {
    searchForm.addEventListener(\'submit\', function(e) {
        syncNumHuespedes();
    });
}

document.getElementById(\'fechaEntrada\')',
        $reservationFormContent
    );
}

file_put_contents($reservationFormPath, $reservationFormContent);
$created[] = "reservation_form.php - Sincronización de número de huéspedes";

// Actualizar vista de walk-in para mostrar advertencia de capacidad
$walkinFormPath = $baseDir . "/views/hotel/walkin_form.php";
$walkinFormContent = file_get_contents($walkinFormPath);

// Actualizar mensaje de información
$walkinFormContent = str_replace(
    '<div class="info-box">
                        ℹ️ Mostrando habitaciones disponibles del <strong><?= date(\'d/m/Y\') ?></strong> al',
    '<div class="info-box">
                        ℹ️ Mostrando habitaciones para <strong><?= htmlspecialchars($_GET[\'numero_huespedes\']) ?> huésped<?= $_GET[\'numero_huespedes\'] > 1 ? \'es\' : \'\' ?></strong>, 
                        disponibles del <strong><?= date(\'d/m/Y\') ?></strong> al',
    $walkinFormContent
);

// Agregar advertencia si no hay habitaciones disponibles
$walkinFormContent = str_replace(
    '<div class="alert alert-warning">
                        ⚠️ No hay habitaciones disponibles para las fechas seleccionadas.
                        <a href="/hotel/reservas/walkin">Intenta con otras fechas</a>
                    </div>',
    '<div class="alert alert-warning">
                        ⚠️ No hay habitaciones disponibles para <strong><?= htmlspecialchars($_GET[\'numero_huespedes\']) ?> huésped<?= $_GET[\'numero_huespedes\'] > 1 ? \'es\' : \'\' ?></strong> 
                        en las fechas seleccionadas.
                        <br><br>
                        <strong>Sugerencias:</strong>
                        <ul style="margin: 10px 0 0 20px;">
                            <li>Intenta con menos huéspedes</li>
                            <li>Intenta con otras fechas</li>
                            <li>Considera reservar múltiples habitaciones</li>
                        </ul>
                        <a href="/hotel/reservas/walkin" style="display: inline-block; margin-top: 10px; padding: 8px 16px; background: #f59e0b; color: white; border-radius: 6px; text-decoration: none; font-weight: 600;">
                            Buscar de Nuevo
                        </a>
                    </div>',
    $walkinFormContent
);

file_put_contents($walkinFormPath, $walkinFormContent);
$created[] = "walkin_form.php - Mensajes mejorados de capacidad";

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>✅ Filtro por Capacidad Implementado</title>
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
        .info-box {
            background: #dbeafe;
            border-left: 4px solid #3b82f6;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .info-box h3 {
            color: #1e40af;
            margin-bottom: 15px;
        }
        .example {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border: 2px solid #e5e7eb;
        }
        .example h4 {
            color: #1f2937;
            margin-bottom: 15px;
        }
        .example-item {
            padding: 12px;
            background: white;
            margin: 10px 0;
            border-radius: 6px;
            border-left: 4px solid #10b981;
        }
        .feature-list {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .feature-item {
            padding: 12px;
            background: white;
            margin: 8px 0;
            border-radius: 6px;
            border-left: 3px solid #10b981;
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
        .text-center {
            text-align: center;
            margin-top: 30px;
        }
        ul {
            margin: 10px 0 0 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">👥</div>
        <h1>¡Filtro por Capacidad Implementado!</h1>
        <p class="subtitle">Ahora muestra solo habitaciones con capacidad suficiente</p>
        
        <div class="success">
            <strong>✅ Actualización exitosa</strong><br>
            Se han actualizado <?= count($created) ?> archivos del sistema.
        </div>
        
        <div class="info-box">
            <h3>🎯 ¿Cómo Funciona?</h3>
            <p style="color: #1e40af; margin: 0;">
                El sistema ahora filtra automáticamente las habitaciones según:
            </p>
            <ul style="color: #1e40af;">
                <li><strong>Capacidad:</strong> Solo muestra habitaciones que aceptan el número de huéspedes indicado</li>
                <li><strong>Disponibilidad:</strong> Verifica que no haya reservas en esas fechas</li>
                <li><strong>Estado:</strong> Excluye habitaciones en mantenimiento</li>
            </ul>
        </div>
        
        <div class="example">
            <h4>📊 Ejemplos de Filtrado:</h4>
            
            <div class="example-item">
                <strong>Cliente solicita: 2 huéspedes</strong>
                <ul style="margin: 5px 0 0 20px; color: #6b7280;">
                    <li>✅ Muestra: Habitaciones con capacidad 2, 3, 4...</li>
                    <li>❌ Oculta: Habitaciones con capacidad 1 (Simple individual)</li>
                </ul>
            </div>
            
            <div class="example-item">
                <strong>Cliente solicita: 4 huéspedes</strong>
                <ul style="margin: 5px 0 0 20px; color: #6b7280;">
                    <li>✅ Muestra: Habitaciones con capacidad 4, 5, 6...</li>
                    <li>❌ Oculta: Habitaciones con capacidad 1, 2, 3</li>
                </ul>
            </div>
            
            <div class="example-item">
                <strong>Cliente solicita: 1 huésped</strong>
                <ul style="margin: 5px 0 0 20px; color: #6b7280;">
                    <li>✅ Muestra: TODAS las habitaciones disponibles (capacidad 1+)</li>
                </ul>
            </div>
        </div>
        
        <h3>🌟 Mejoras Implementadas:</h3>
        <div class="feature-list">
            <div class="feature-item">
                <strong>Walk-in:</strong> Filtra por capacidad desde el primer paso
            </div>
            <div class="feature-item">
                <strong>Reservas Normales:</strong> También aplica el filtro por capacidad
            </div>
            <div class="feature-item">
                <strong>Mensajes Claros:</strong> Indica cuántos huéspedes está buscando
            </div>
            <div class="feature-item">
                <strong>Sugerencias:</strong> Si no hay habitaciones, ofrece alternativas
            </div>
            <div class="feature-item">
                <strong>SQL Optimizado:</strong> Consulta eficiente con múltiples condiciones
            </div>
        </div>
        
        <div class="info-box" style="background: #dcfce7; border-color: #10b981;">
            <h3 style="color: #166534;">💡 Ventajas:</h3>
            <ul style="color: #166534; margin: 10px 0 0 20px;">
                <li>Cliente solo ve habitaciones que realmente le sirven</li>
                <li>Evita errores de capacidad insuficiente</li>
                <li>Proceso más rápido y eficiente</li>
                <li>Mejor experiencia de usuario</li>
                <li>Reduce confusión en la selección</li>
            </ul>
        </div>
        
        <div class="text-center">
            <a href="/hotel/reservas/walkin" class="btn">
                🚶 Probar Walk-in
            </a>
            <a href="/hotel/reservas/create" class="btn">
                📅 Nueva Reserva
            </a>
        </div>
    </div>
</body>
</html>
