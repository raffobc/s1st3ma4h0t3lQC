<?php
/**
 * ACTUALIZAR SISTEMA CON HORARIOS DE CHECK-IN/OUT
 * Archivo: C:\xampp\htdocs\hotel-system\update_checkin_times.php
 */

$baseDir = __DIR__;
$created = [];

// 1. Primero, agregar columnas a la tabla reservas
require_once __DIR__ . "/config/MasterDatabase.php";
$masterDb = MasterDatabase::getConnection();
$stmt = $masterDb->query("SELECT * FROM hoteles WHERE estado = 'activo'");
$hoteles = $stmt->fetchAll();

foreach ($hoteles as $hotel) {
    try {
        $hotelDb = new PDO(
            "mysql:host={$hotel['db_host']};dbname={$hotel['db_name']};charset=utf8mb4",
            $hotel['db_user'],
            $hotel['db_password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        // Agregar hora_entrada
        $stmt = $hotelDb->query("SHOW COLUMNS FROM reservas LIKE 'hora_entrada'");
        if (!$stmt->fetch()) {
            $hotelDb->exec("ALTER TABLE reservas ADD COLUMN hora_entrada TIME DEFAULT '15:00:00' AFTER fecha_entrada");
        }
        
        // Agregar hora_salida
        $stmt = $hotelDb->query("SHOW COLUMNS FROM reservas LIKE 'hora_salida'");
        if (!$stmt->fetch()) {
            $hotelDb->exec("ALTER TABLE reservas ADD COLUMN hora_salida TIME DEFAULT '12:00:00' AFTER fecha_salida");
        }
        
        // Agregar early_checkin
        $stmt = $hotelDb->query("SHOW COLUMNS FROM reservas LIKE 'early_checkin'");
        if (!$stmt->fetch()) {
            $hotelDb->exec("ALTER TABLE reservas ADD COLUMN early_checkin BOOLEAN DEFAULT 0 AFTER hora_salida");
        }
        
        // Agregar late_checkout
        $stmt = $hotelDb->query("SHOW COLUMNS FROM reservas LIKE 'late_checkout'");
        if (!$stmt->fetch()) {
            $hotelDb->exec("ALTER TABLE reservas ADD COLUMN late_checkout BOOLEAN DEFAULT 0 AFTER early_checkin");
        }
        
        // Agregar cargo_extra
        $stmt = $hotelDb->query("SHOW COLUMNS FROM reservas LIKE 'cargo_extra'");
        if (!$stmt->fetch()) {
            $hotelDb->exec("ALTER TABLE reservas ADD COLUMN cargo_extra DECIMAL(10,2) DEFAULT 0.00 AFTER late_checkout");
        }
        
        $created[] = $hotel['nombre'] . " - Columnas de horarios agregadas";
        
    } catch (Exception $e) {
        $created[] = $hotel['nombre'] . " - Error: " . $e->getMessage();
    }
}

// 2. Actualizar el controlador
$controllerContent = <<<'EOD'
<?php
class HotelReservationsController {
    private $hotelDb;
    
    // Configuración de horarios
    private $horaCheckInEstandar = '15:00:00';
    private $horaCheckOutEstandar = '12:00:00';
    private $horaEarlyCheckIn = '11:00:00';
    private $horaLateCheckOut = '18:00:00';
    private $cargoEarlyCheckIn = 50.00; // S/ 50
    private $cargoLateCheckOut = 50.00; // S/ 50
    
    public function __construct() {
        $this->checkAuth();
        $this->hotelDb = $this->getHotelConnection();
    }
    
    private function checkAuth() {
        if (!isset($_SESSION["hotel_user_id"])) {
            header("Location: /hotel/login");
            exit;
        }
    }
    
    private function getHotelConnection() {
        $dbName = $_SESSION["hotel_db_name"];
        $credentials = $_SESSION["hotel_db_credentials"];
        
        return new PDO(
            "mysql:host={$credentials['host']};dbname=$dbName;charset=utf8mb4",
            $credentials["user"],
            $credentials["password"],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
    }
    
    public function index() {
        $filter = $_GET["filter"] ?? "all";
        
        $sql = "
            SELECT r.*, 
                   h.numero_habitacion, h.tipo as tipo_habitacion,
                   c.nombre as cliente_nombre, c.documento as cliente_documento,
                   c.email as cliente_email, c.telefono as cliente_telefono
            FROM reservas r
            JOIN habitaciones h ON r.habitacion_id = h.id
            JOIN clientes c ON r.cliente_id = c.id
        ";
        
        if ($filter === "active") {
            $sql .= " WHERE r.estado IN ('reservada', 'ocupada')";
        } elseif ($filter === "finished") {
            $sql .= " WHERE r.estado = 'finalizada'";
        } elseif ($filter === "cancelled") {
            $sql .= " WHERE r.estado = 'cancelada'";
        }
        
        $sql .= " ORDER BY r.fecha_entrada DESC";
        
        $reservations = $this->hotelDb->query($sql)->fetchAll();
        require_once BASE_PATH . "/views/hotel/reservations.php";
    }
    
    public function create() {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $clienteId = $_POST["cliente_id"];
            $habitacionId = $_POST["habitacion_id"];
            $fechaEntrada = $_POST["fecha_entrada"];
            $fechaSalida = $_POST["fecha_salida"];
            $numHuespedes = $_POST["numero_huespedes"];
            $observaciones = $_POST["observaciones"] ?? "";
            
            // Horarios
            $earlyCheckIn = isset($_POST["early_checkin"]) ? 1 : 0;
            $lateCheckOut = isset($_POST["late_checkout"]) ? 1 : 0;
            
            $horaEntrada = $earlyCheckIn ? $this->horaEarlyCheckIn : $this->horaCheckInEstandar;
            $horaSalida = $lateCheckOut ? $this->horaLateCheckOut : $this->horaCheckOutEstandar;
            
            // Calcular días y total
            $entrada = new DateTime($fechaEntrada);
            $salida = new DateTime($fechaSalida);
            $dias = $entrada->diff($salida)->days;
            
            // Obtener precio de la habitación
            $stmt = $this->hotelDb->prepare("SELECT precio_noche FROM habitaciones WHERE id = ?");
            $stmt->execute([$habitacionId]);
            $precioNoche = $stmt->fetch()["precio_noche"];
            
            // Calcular cargos extras
            $cargoExtra = 0;
            if ($earlyCheckIn) $cargoExtra += $this->cargoEarlyCheckIn;
            if ($lateCheckOut) $cargoExtra += $this->cargoLateCheckOut;
            
            $total = ($dias * $precioNoche) + $cargoExtra;
            
            // Crear reserva
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
            
            // Actualizar estado de habitación
            $stmt = $this->hotelDb->prepare("UPDATE habitaciones SET estado = 'reservada' WHERE id = ?");
            $stmt->execute([$habitacionId]);
            
            header("Location: /hotel/reservas");
            exit;
        }
        
        // Obtener clientes
        $clientes = $this->hotelDb->query("SELECT * FROM clientes ORDER BY nombre")->fetchAll();
        
        // Obtener habitaciones disponibles
        $habitaciones = [];
        $fechaEntrada = $_GET["fecha_entrada"] ?? null;
        $fechaSalida = $_GET["fecha_salida"] ?? null;
        
        if ($fechaEntrada && $fechaSalida) {
            // Considerar horarios: una habitación está disponible si:
            // - Check-out del cliente anterior es antes del check-in del nuevo cliente
            $sql = "
                SELECT h.* 
                FROM habitaciones h
                WHERE h.id NOT IN (
                    SELECT r.habitacion_id 
                    FROM reservas r 
                    WHERE r.estado IN ('reservada', 'ocupada')
                    AND (
                        -- Caso 1: Reserva existente que cubre la fecha de entrada
                        (r.fecha_entrada < ? AND r.fecha_salida > ?)
                        -- Caso 2: Reserva existente que cubre la fecha de salida
                        OR (r.fecha_entrada < ? AND r.fecha_salida > ?)
                        -- Caso 3: Reserva existente dentro del rango
                        OR (r.fecha_entrada >= ? AND r.fecha_salida <= ?)
                        -- Caso 4: Mismo día - verificar horarios
                        OR (r.fecha_salida = ? AND r.hora_salida > '15:00:00')
                        OR (r.fecha_entrada = ? AND r.hora_entrada < '12:00:00')
                    )
                )
                AND h.estado NOT IN ('mantenimiento')
                ORDER BY h.numero_habitacion
            ";
            
            $stmt = $this->hotelDb->prepare($sql);
            $stmt->execute([
                $fechaEntrada, $fechaEntrada,
                $fechaSalida, $fechaSalida,
                $fechaEntrada, $fechaSalida,
                $fechaEntrada, $fechaSalida
            ]);
            $habitaciones = $stmt->fetchAll();
        }
        
        // Pasar configuración de horarios a la vista
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
    
    public function updateStatus() {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $id = $_POST["reservation_id"];
            $nuevoEstado = $_POST["nuevo_estado"];
            
            $stmt = $this->hotelDb->prepare("UPDATE reservas SET estado = ? WHERE id = ?");
            $stmt->execute([$nuevoEstado, $id]);
            
            // Actualizar estado de habitación
            $stmt = $this->hotelDb->prepare("SELECT habitacion_id FROM reservas WHERE id = ?");
            $stmt->execute([$id]);
            $habitacionId = $stmt->fetch()["habitacion_id"];
            
            $estadoHabitacion = "disponible";
            if ($nuevoEstado === "ocupada") {
                $estadoHabitacion = "ocupada";
            } elseif ($nuevoEstado === "reservada") {
                $estadoHabitacion = "reservada";
            } elseif ($nuevoEstado === "finalizada") {
                $estadoHabitacion = "limpieza";
            }
            
            $stmt = $this->hotelDb->prepare("UPDATE habitaciones SET estado = ? WHERE id = ?");
            $stmt->execute([$estadoHabitacion, $habitacionId]);
            
            header("Location: /hotel/reservas");
            exit;
        }
    }
}
EOD;

file_put_contents($baseDir . "/controllers/HotelReservationsController.php", $controllerContent);
$created[] = "HotelReservationsController.php actualizado";

file_put_contents($baseDir . "/views/hotel/reservation_form.php", $viewContent);
$created[] = "reservation_form.php actualizado";

// Actualizar también la vista de lista de reservas para mostrar horarios
$reservationsListView = <<<'EOD'
<?php include BASE_PATH . "/views/hotel/_header.php"; ?>

<div class="page-header">
    <div>
        <h1>📅 Reservas</h1>
        <p class="subtitle">Gestiona las reservas del hotel</p>
    </div>
    <a href="/hotel/reservas/create" class="btn-primary">+ Nueva Reserva</a>
</div>

<div class="card">
    <div class="filters-bar">
        <a href="?filter=all" class="filter-btn <?= ($filter ?? 'all') === 'all' ? 'active' : '' ?>">
            📋 Todas
        </a>
        <a href="?filter=active" class="filter-btn <?= ($filter ?? '') === 'active' ? 'active' : '' ?>">
            ✅ Activas
        </a>
        <a href="?filter=finished" class="filter-btn <?= ($filter ?? '') === 'finished' ? 'active' : '' ?>">
            ✔️ Finalizadas
        </a>
        <a href="?filter=cancelled" class="filter-btn <?= ($filter ?? '') === 'cancelled' ? 'active' : '' ?>">
            ❌ Canceladas
        </a>
    </div>
    
    <?php if (empty($reservations)): ?>
        <div class="empty-state">
            <div class="empty-icon">📅</div>
            <h3>No hay reservas</h3>
            <p>Comienza creando una nueva reserva</p>
            <a href="/hotel/reservas/create" class="btn-primary" style="margin-top: 20px;">
                + Nueva Reserva
            </a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Habitación</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Huéspedes</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservations as $reserva): ?>
                        <tr>
                            <td><strong>#<?= $reserva['id'] ?></strong></td>
                            <td>
                                <div><?= htmlspecialchars($reserva['cliente_nombre']) ?></div>
                                <small style="color: #6b7280;"><?= htmlspecialchars($reserva['cliente_documento']) ?></small>
                            </td>
                            <td><span class="room-badge">🏠 <?= htmlspecialchars($reserva['numero_habitacion']) ?></span></td>
                            <td>
                                <div><?= date('d/m/Y', strtotime($reserva['fecha_entrada'])) ?></div>
                                <small style="color: #6b7280;">
                                    ⏰ <?= isset($reserva['hora_entrada']) ? date('g:i A', strtotime($reserva['hora_entrada'])) : '3:00 PM' ?>
                                    <?php if (isset($reserva['early_checkin']) && $reserva['early_checkin']): ?>
                                        <span class="badge-extra">⚡ Early</span>
                                    <?php endif; ?>
                                </small>
                            </td>
                            <td>
                                <div><?= date('d/m/Y', strtotime($reserva['fecha_salida'])) ?></div>
                                <small style="color: #6b7280;">
                                    ⏰ <?= isset($reserva['hora_salida']) ? date('g:i A', strtotime($reserva['hora_salida'])) : '12:00 PM' ?>
                                    <?php if (isset($reserva['late_checkout']) && $reserva['late_checkout']): ?>
                                        <span class="badge-extra">⚡ Late</span>
                                    <?php endif; ?>
                                </small>
                            </td>
                            <td><?= $reserva['numero_huespedes'] ?> 👥</td>
                            <td>
                                <strong>S/ <?= number_format($reserva['precio_total'], 2) ?></strong>
                                <?php if (isset($reserva['cargo_extra']) && $reserva['cargo_extra'] > 0): ?>
                                    <br><small style="color: #059669;">+S/ <?= number_format($reserva['cargo_extra'], 2) ?> extras</small>
                                <?php endif; ?>
                            </td>
                            <td><span class="status-badge status-<?= $reserva['estado'] ?>"><?= ucfirst($reserva['estado']) ?></span></td>
                            <td>
                                <div class="action-buttons">
                                    <?php if ($reserva['estado'] === 'reservada'): ?>
                                        <button onclick="cambiarEstado(<?= $reserva['id'] ?>, 'ocupada')" 
                                                class="btn-action btn-success" title="Check-in">✓</button>
                                    <?php endif; ?>
                                    
                                    <?php if ($reserva['estado'] === 'ocupada'): ?>
                                        <button onclick="cambiarEstado(<?= $reserva['id'] ?>, 'finalizada')" 
                                                class="btn-action btn-primary" title="Check-out">🏁</button>
                                    <?php endif; ?>
                                    
                                    <?php if (in_array($reserva['estado'], ['reservada', 'ocupada'])): ?>
                                        <button onclick="cambiarEstado(<?= $reserva['id'] ?>, 'cancelada')" 
                                                class="btn-action btn-danger" title="Cancelar">❌</button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<form id="statusForm" method="POST" action="/hotel/reservas/update-status" style="display: none;">
    <input type="hidden" name="reservation_id" id="reservationId">
    <input type="hidden" name="nuevo_estado" id="nuevoEstado">
</form>

<script>
function cambiarEstado(id, estado) {
    const mensajes = {
        "ocupada": "¿Marcar como OCUPADA (Check-in)?",
        "finalizada": "¿FINALIZAR (Check-out)?",
        "cancelada": "¿CANCELAR esta reserva?"
    };
    
    if (confirm(mensajes[estado])) {
        document.getElementById("reservationId").value = id;
        document.getElementById("nuevoEstado").value = estado;
        document.getElementById("statusForm").submit();
    }
}
</script>

<style>
.filters-bar {
    display: flex;
    gap: 10px;
    padding: 20px;
    background: #f9fafb;
    border-bottom: 2px solid #e5e7eb;
}
.filter-btn {
    padding: 10px 20px;
    background: white;
    color: #6b7280;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.2s;
}
.filter-btn.active {
    background: #667eea;
    color: white;
}
.table-responsive { overflow-x: auto; padding: 20px; }
.action-buttons { display: flex; gap: 5px; }
.btn-action {
    padding: 6px 12px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
}
.btn-action.btn-success { background: #dcfce7; color: #166534; }
.btn-action.btn-primary { background: #dbeafe; color: #1e40af; }
.btn-action.btn-danger { background: #fee2e2; color: #991b1b; }
.badge-extra {
    background: #fef3c7;
    color: #92400e;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 700;
}
</style>

<?php include BASE_PATH . "/views/hotel/_footer.php"; ?>
EOD;

file_put_contents($baseDir . "/views/hotel/reservations.php", $reservationsListView);
$created[] = "reservations.php actualizado con horarios";

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>✅ Sistema de Horarios Implementado</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            font-size: 18px;
        }
        .schedule-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 15px;
        }
        .schedule-item {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border-left: 3px solid #3b82f6;
        }
        .schedule-item strong {
            color: #1e40af;
            display: block;
            margin-bottom: 5px;
        }
        .schedule-item span {
            color: #1e40af;
            font-size: 14px;
        }
        .features {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .feature {
            padding: 15px;
            background: white;
            margin: 10px 0;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        .feature h3 {
            margin: 0 0 8px 0;
            color: #1f2937;
            font-size: 16px;
        }
        .feature p {
            margin: 0;
            color: #6b7280;
            font-size: 14px;
        }
        .files-list {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .file-item {
            padding: 10px;
            background: white;
            margin: 6px 0;
            border-radius: 6px;
            border-left: 3px solid #10b981;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">⏰</div>
        <h1>¡Sistema de Horarios Implementado!</h1>
        <p class="subtitle">Check-in/out con horarios flexibles y cargos extras</p>
        
        <div class="success">
            <strong>✅ Actualización exitosa</strong><br>
            Se han agregado <?= count($created) ?> componentes al sistema de reservas.
        </div>
        
        <div class="info-box">
            <h3>⏰ Configuración de Horarios:</h3>
            <div class="schedule-grid">
                <div class="schedule-item">
                    <strong>✅ Check-in Estándar</strong>
                    <span>3:00 PM (15:00) - Incluido</span>
                </div>
                <div class="schedule-item">
                    <strong>✅ Check-out Estándar</strong>
                    <span>12:00 PM (12:00) - Incluido</span>
                </div>
                <div class="schedule-item">
                    <strong>⚡ Early Check-in</strong>
                    <span>11:00 AM (11:00) - +S/ 50.00</span>
                </div>
                <div class="schedule-item">
                    <strong>⚡ Late Check-out</strong>
                    <span>6:00 PM (18:00) - +S/ 50.00</span>
                </div>
            </div>
        </div>
        
        <h3>🌟 Nuevas Funcionalidades:</h3>
        <div class="features">
            <div class="feature">
                <h3>⏰ Horarios Definidos</h3>
                <p>Check-in estándar a las 3:00 PM y check-out a las 12:00 PM</p>
            </div>
            
            <div class="feature">
                <h3>⚡ Early Check-in</h3>
                <p>Opción de ingreso desde las 11:00 AM con cargo adicional de S/ 50</p>
            </div>
            
            <div class="feature">
                <h3>⚡ Late Check-out</h3>
                <p>Opción de salida hasta las 6:00 PM con cargo adicional de S/ 50</p>
            </div>
            
            <div class="feature">
                <h3>💰 Cálculo Automático</h3>
                <p>El sistema calcula automáticamente los cargos extras y el total</p>
            </div>
            
            <div class="feature">
                <h3>📊 Disponibilidad Inteligente</h3>
                <p>Verifica disponibilidad considerando horarios de entrada y salida</p>
            </div>
            
            <div class="feature">
                <h3>📋 Visualización en Lista</h3>
                <p>Las reservas muestran horarios y badges de early/late check</p>
            </div>
        </div>
        
        <h3>📁 Archivos Actualizados:</h3>
        <div class="files-list">
            <?php foreach ($created as $file): ?>
                <div class="file-item">✓ <?= htmlspecialchars($file) ?></div>
            <?php endforeach; ?>
        </div>
        
        <div class="info-box" style="background: #dcfce7; border-color: #10b981;">
            <h3 style="color: #166534;">✨ ¡Todo Listo!</h3>
            <p style="color: #166534; margin: 0;">
                El sistema ahora gestiona horarios de check-in/out de forma profesional:
            </p>
            <ul style="color: #166534; margin: 10px 0 0 20px;">
                <li>✅ Horarios estándar incluidos en el precio</li>
                <li>⚡ Opciones flexibles con cargos extras claros</li>
                <li>💰 Cálculo automático del total con extras</li>
                <li>📊 Disponibilidad considerando horarios</li>
                <li>🎯 Interfaz intuitiva con checkboxes visuales</li>
            </ul>
        </div>
        
        <div class="text-center">
            <a href="/hotel/reservas/create" class="btn">
                🚀 Probar Nueva Reserva
            </a>
            <a href="/hotel/reservas" class="btn">
                📋 Ver Reservas
            </a>
            <a href="/hotel/dashboard" class="btn">
                📊 Dashboard
            </a>
        </div>
    </div>
</body>
</html>

// 3. Crear nueva vista con horarios
$viewContent = <<<'EOD'
<?php include BASE_PATH . "/views/hotel/_header.php"; ?>

<div class="page-header">
    <div>
        <h1>📅 Nueva Reserva</h1>
        <p class="subtitle">Selecciona las fechas para ver habitaciones disponibles</p>
    </div>
    <a href="/hotel/reservas" class="btn-secondary">← Volver</a>
</div>

<div class="card">
    <div class="form-container">
        
        <!-- PASO 1: Seleccionar Fechas -->
        <div class="step-section <?= empty($habitaciones) && (!isset($_GET['fecha_entrada'])) ? 'active' : 'completed' ?>">
            <div class="step-header">
                <span class="step-number">1</span>
                <h3>Selecciona las Fechas</h3>
            </div>
            
            <form method="GET" action="">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Fecha de Entrada *</label>
                        <input type="date" name="fecha_entrada" id="fechaEntrada" class="form-control" 
                               required min="<?= date('Y-m-d') ?>" 
                               value="<?= htmlspecialchars($_GET['fecha_entrada'] ?? '') ?>">
                        <small class="form-help">
                            ⏰ Check-in estándar: <?= date('g:i A', strtotime($config['hora_checkin_estandar'])) ?>
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Fecha de Salida *</label>
                        <input type="date" name="fecha_salida" id="fechaSalida" class="form-control" 
                               required value="<?= htmlspecialchars($_GET['fecha_salida'] ?? '') ?>">
                        <small class="form-help">
                            ⏰ Check-out estándar: <?= date('g:i A', strtotime($config['hora_checkout_estandar'])) ?>
                        </small>
                    </div>
                </div>
                
                <button type="submit" class="btn-primary">
                    🔍 Buscar Habitaciones Disponibles
                </button>
            </form>
        </div>
        
        <!-- PASO 2: Habitaciones Disponibles y Formulario -->
        <?php if (!empty($habitaciones) || (isset($_GET['fecha_entrada']) && isset($_GET['fecha_salida']))): ?>
        <div class="step-section active" style="margin-top: 30px;">
            <div class="step-header">
                <span class="step-number">2</span>
                <h3>Habitaciones Disponibles</h3>
            </div>
            
            <?php if (empty($habitaciones)): ?>
                <div class="alert alert-warning">
                    ⚠️ No hay habitaciones disponibles para las fechas seleccionadas. 
                    <a href="/hotel/reservas/create">Intenta con otras fechas</a>
                </div>
            <?php else: ?>
                <div class="info-box">
                    <strong>📋 Información de Horarios:</strong>
                    <div style="margin-top: 10px; display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                        <div>
                            <strong>✅ Check-in Estándar:</strong> <?= date('g:i A', strtotime($config['hora_checkin_estandar'])) ?> (Incluido)
                        </div>
                        <div>
                            <strong>✅ Check-out Estándar:</strong> <?= date('g:i A', strtotime($config['hora_checkout_estandar'])) ?> (Incluido)
                        </div>
                        <div>
                            <strong>⚡ Early Check-in:</strong> <?= date('g:i A', strtotime($config['hora_early_checkin'])) ?> (+S/ <?= number_format($config['cargo_early_checkin'], 2) ?>)
                        </div>
                        <div>
                            <strong>⚡ Late Check-out:</strong> <?= date('g:i A', strtotime($config['hora_late_checkout'])) ?> (+S/ <?= number_format($config['cargo_late_checkout'], 2) ?>)
                        </div>
                    </div>
                </div>
                
                <div class="available-rooms">
                    <?php foreach ($habitaciones as $hab): ?>
                        <div class="room-option" data-room-id="<?= $hab['id'] ?>">
                            <div class="room-option-header">
                                <div class="room-option-number">#<?= $hab['numero_habitacion'] ?></div>
                                <div class="room-option-type"><?= htmlspecialchars($hab['tipo']) ?></div>
                            </div>
                            <div class="room-option-body">
                                <div class="room-option-info">
                                    <span>👥 <?= $hab['capacidad'] ?> personas</span>
                                    <span class="room-option-price">S/ <?= number_format($hab['precio_noche'], 2) ?>/noche</span>
                                </div>
                                <?php if ($hab['descripcion']): ?>
                                    <div class="room-option-desc"><?= htmlspecialchars($hab['descripcion']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="step-section active" style="margin-top: 30px;">
                    <div class="step-header">
                        <span class="step-number">3</span>
                        <h3>Completar Reserva</h3>
                    </div>
                    
                    <form method="POST" action="/hotel/reservas/create" id="reservaForm">
                        <input type="hidden" name="fecha_entrada" value="<?= htmlspecialchars($_GET['fecha_entrada']) ?>">
                        <input type="hidden" name="fecha_salida" value="<?= htmlspecialchars($_GET['fecha_salida']) ?>">
                        
                        <div class="form-group">
                            <label class="form-label">Cliente *</label>
                            <select name="cliente_id" class="form-control" required>
                                <option value="">Seleccionar cliente</option>
                                <?php foreach ($clientes as $cliente): ?>
                                    <option value="<?= $cliente['id'] ?>">
                                        <?= htmlspecialchars($cliente['nombre']) ?> - <?= htmlspecialchars($cliente['documento']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-help">
                                ¿Cliente nuevo? <a href="/hotel/clientes/create" target="_blank">Registrar aquí</a>
                            </small>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Habitación *</label>
                                <select name="habitacion_id" class="form-control" id="habitacionSelect" required>
                                    <option value="">Seleccionar habitación</option>
                                    <?php foreach ($habitaciones as $hab): ?>
                                        <option value="<?= $hab['id'] ?>" 
                                                data-precio="<?= $hab['precio_noche'] ?>">
                                            #<?= $hab['numero_habitacion'] ?> - <?= $hab['tipo'] ?> 
                                            (S/ <?= number_format($hab['precio_noche'], 2) ?>/noche)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Número de Huéspedes *</label>
                                <input type="number" name="numero_huespedes" class="form-control" 
                                       min="1" value="1" required>
                            </div>
                        </div>
                        
                        <!-- Opciones de Horario -->
                        <div class="horarios-section">
                            <h4>⏰ Opciones de Horario</h4>
                            <div class="checkbox-grid">
                                <label class="checkbox-card">
                                    <input type="checkbox" name="early_checkin" id="earlyCheckin" value="1">
                                    <div class="checkbox-content">
                                        <div class="checkbox-title">⚡ Early Check-in</div>
                                        <div class="checkbox-desc">Ingreso desde <?= date('g:i A', strtotime($config['hora_early_checkin'])) ?></div>
                                        <div class="checkbox-price">+S/ <?= number_format($config['cargo_early_checkin'], 2) ?></div>
                                    </div>
                                </label>
                                
                                <label class="checkbox-card">
                                    <input type="checkbox" name="late_checkout" id="lateCheckout" value="1">
                                    <div class="checkbox-content">
                                        <div class="checkbox-title">⚡ Late Check-out</div>
                                        <div class="checkbox-desc">Salida hasta <?= date('g:i A', strtotime($config['hora_late_checkout'])) ?></div>
                                        <div class="checkbox-price">+S/ <?= number_format($config['cargo_late_checkout'], 2) ?></div>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Resumen de Precios -->
                        <div class="price-summary">
                            <div class="price-row">
                                <span>Precio por noche:</span>
                                <strong id="precioNoche">S/ 0.00</strong>
                            </div>
                            <div class="price-row">
                                <span>Número de noches:</span>
                                <strong id="numeroNoches">
                                    <?php 
                                    if (isset($_GET['fecha_entrada']) && isset($_GET['fecha_salida'])) {
                                        $entrada = new DateTime($_GET['fecha_entrada']);
                                        $salida = new DateTime($_GET['fecha_salida']);
                                        echo $entrada->diff($salida)->days;
                                    } else {
                                        echo "0";
                                    }
                                    ?>
                                </strong>
                            </div>
                            <div class="price-row">
                                <span>Subtotal alojamiento:</span>
                                <strong id="subtotal">S/ 0.00</strong>
                            </div>
                            <div class="price-row" id="cargoExtraRow" style="display: none;">
                                <span>Cargos extras:</span>
                                <strong id="cargoExtra">S/ 0.00</strong>
                            </div>
                            <div class="price-row price-total">
                                <span>Total a pagar:</span>
                                <strong id="precioTotal">S/ 0.00</strong>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Observaciones</label>
                            <textarea name="observaciones" class="form-control" rows="3" 
                                      placeholder="Peticiones especiales, alergias, hora estimada de llegada, etc."></textarea>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn-primary">✨ Confirmar Reserva</button>
                            <a href="/hotel/reservas" class="btn-secondary">Cancelar</a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.form-container { padding: 30px; }
.step-section {
    padding: 25px;
    background: #f9fafb;
    border-radius: 12px;
    border: 2px solid #e5e7eb;
    opacity: 0.6;
    transition: all 0.3s;
}
.step-section.active {
    opacity: 1;
    border-color: #667eea;
    background: white;
}
.step-section.completed {
    opacity: 1;
    background: #f0fdf4;
    border-color: #10b981;
}
.step-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
}
.step-number {
    width: 40px;
    height: 40px;
    background: #667eea;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 18px;
}
.step-section.completed .step-number { background: #10b981; }
.step-header h3 { margin: 0; color: #1f2937; font-size: 20px; }
.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}
.form-group { margin-bottom: 15px; }
.form-label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #374151;
    font-size: 14px;
}
.form-control {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 15px;
}
.form-control:focus { outline: none; border-color: #667eea; }
.form-help {
    display: block;
    margin-top: 5px;
    font-size: 12px;
    color: #6b7280;
}
.form-help a { color: #667eea; text-decoration: none; font-weight: 600; }

.info-box {
    background: #dbeafe;
    border-left: 4px solid #3b82f6;
    padding: 20px;
    border-radius: 8px;
    margin: 20px 0;
    color: #1e40af;
    font-size: 14px;
}

.available-rooms {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 15px;
    margin: 20px 0;
}
.room-option {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.3s;
}
.room-option:hover {
    border-color: #667eea;
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.2);
}
.room-option-header {
    background: #f9fafb;
    padding: 15px;
    border-bottom: 2px solid #e5e7eb;
}
.room-option-number {
    font-size: 20px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 5px;
}
.room-option-type {
    font-size: 16px;
    color: #6b7280;
    font-weight: 600;
}
.room-option-body { padding: 15px; }
.room-option-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
    font-size: 14px;
    color: #6b7280;
}
.room-option-price {
    font-size: 18px;
    font-weight: 700;
    color: #10b981;
}
.room-option-desc {
    font-size: 13px;
    color: #6b7280;
    line-height: 1.5;
}

.horarios-section {
    background: #f9fafb;
    padding: 20px;
    border-radius: 12px;
    margin: 20px 0;
}
.horarios-section h4 {
    margin: 0 0 15px 0;
    color: #1f2937;
    font-size: 18px;
}
.checkbox-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
}
.checkbox-card {
    position: relative;
    display: block;
    cursor: pointer;
}
.checkbox-card input[type="checkbox"] {
    position: absolute;
    opacity: 0;
}
.checkbox-content {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    padding: 20px;
    transition: all 0.3s;
}
.checkbox
