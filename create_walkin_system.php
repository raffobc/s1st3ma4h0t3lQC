<?php
/**
 * SISTEMA DE CHECK-IN DIRECTO (WALK-IN)
 * Archivo: C:\xampp\htdocs\hotel-system\create_walkin_system.php
 */

$baseDir = __DIR__;
$created = [];

// 1. Crear método en el controlador de reservas para walk-in
$reservationsControllerUpdate = <<<'EOD'
<?php
class HotelReservationsController {
    private $hotelDb;
    
    // Configuración de horarios
    private $horaCheckInEstandar = '15:00:00';
    private $horaCheckOutEstandar = '12:00:00';
    private $horaEarlyCheckIn = '11:00:00';
    private $horaLateCheckOut = '18:00:00';
    private $cargoEarlyCheckIn = 50.00;
    private $cargoLateCheckOut = 50.00;
    
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
    
    public function walkin() {
        // Vista de check-in directo
        
        // Obtener habitaciones disponibles AHORA
        $habitaciones = $this->hotelDb->query("
            SELECT * FROM habitaciones 
            WHERE estado = 'disponible' 
            ORDER BY numero_habitacion
        ")->fetchAll();
        
        // Obtener clientes para selector rápido
        $clientes = $this->hotelDb->query("
            SELECT * FROM clientes 
            ORDER BY created_at DESC 
            LIMIT 50
        ")->fetchAll();
        
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
    
    public function createWalkin() {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $this->hotelDb->beginTransaction();
            
            try {
                // 1. Crear o usar cliente existente
                $clienteId = $_POST["cliente_id"] ?? null;
                
                if (empty($clienteId) || $clienteId === 'new') {
                    // Crear nuevo cliente
                    $stmt = $this->hotelDb->prepare("
                        INSERT INTO clientes (nombre, documento, email, telefono)
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $_POST["cliente_nombre"],
                        $_POST["cliente_documento"],
                        $_POST["cliente_email"] ?? '',
                        $_POST["cliente_telefono"] ?? ''
                    ]);
                    $clienteId = $this->hotelDb->lastInsertId();
                }
                
                // 2. Crear la reserva
                $habitacionId = $_POST["habitacion_id"];
                $fechaEntrada = date('Y-m-d');
                $fechaSalida = $_POST["fecha_salida"];
                $numHuespedes = $_POST["numero_huespedes"];
                $observaciones = $_POST["observaciones"] ?? "";
                
                // Horarios
                $earlyCheckIn = isset($_POST["early_checkin"]) ? 1 : 0;
                $lateCheckOut = isset($_POST["late_checkout"]) ? 1 : 0;
                
                $horaEntrada = date('H:i:s'); // Hora actual
                $horaSalida = $lateCheckOut ? $this->horaLateCheckOut : $this->horaCheckOutEstandar;
                
                // Calcular días y total
                $entrada = new DateTime($fechaEntrada);
                $salida = new DateTime($fechaSalida);
                $dias = $entrada->diff($salida)->days;
                
                if ($dias < 1) $dias = 1; // Mínimo 1 día
                
                // Obtener precio de la habitación
                $stmt = $this->hotelDb->prepare("SELECT precio_noche FROM habitaciones WHERE id = ?");
                $stmt->execute([$habitacionId]);
                $precioNoche = $stmt->fetch()["precio_noche"];
                
                // Calcular cargos extras
                $cargoExtra = 0;
                if ($lateCheckOut) $cargoExtra += $this->cargoLateCheckOut;
                
                $total = ($dias * $precioNoche) + $cargoExtra;
                
                // Crear reserva con estado 'ocupada' directamente
                $stmt = $this->hotelDb->prepare("
                    INSERT INTO reservas (
                        cliente_id, habitacion_id, fecha_entrada, hora_entrada, 
                        fecha_salida, hora_salida, numero_huespedes, precio_total, 
                        observaciones, early_checkin, late_checkout, cargo_extra, estado
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'ocupada')
                ");
                
                $stmt->execute([
                    $clienteId, $habitacionId, $fechaEntrada, $horaEntrada,
                    $fechaSalida, $horaSalida, $numHuespedes, $total,
                    $observaciones, $earlyCheckIn, $lateCheckOut, $cargoExtra
                ]);
                
                $reservaId = $this->hotelDb->lastInsertId();
                
                // 3. Registrar huéspedes
                $huespedes = $_POST["huespedes"] ?? [];
                $stmt = $this->hotelDb->prepare("
                    INSERT INTO huespedes 
                    (reserva_id, nombre, documento, tipo_documento, fecha_nacimiento, 
                     nacionalidad, telefono, email, es_titular)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                foreach ($huespedes as $index => $huesped) {
                    if (!empty($huesped['nombre']) && !empty($huesped['documento'])) {
                        $stmt->execute([
                            $reservaId,
                            $huesped['nombre'],
                            $huesped['documento'],
                            $huesped['tipo_documento'] ?? 'DNI',
                            !empty($huesped['fecha_nacimiento']) ? $huesped['fecha_nacimiento'] : null,
                            $huesped['nacionalidad'] ?? 'Peruana',
                            $huesped['telefono'] ?? null,
                            $huesped['email'] ?? null,
                            $index === 0 ? 1 : 0
                        ]);
                    }
                }
                
                // 4. Actualizar estado de habitación
                $stmt = $this->hotelDb->prepare("UPDATE habitaciones SET estado = 'ocupada' WHERE id = ?");
                $stmt->execute([$habitacionId]);
                
                $this->hotelDb->commit();
                
                header("Location: /hotel/reservas?success=walkin");
                exit;
                
            } catch (Exception $e) {
                $this->hotelDb->rollBack();
                header("Location: /hotel/reservas/walkin?error=" . urlencode($e->getMessage()));
                exit;
            }
        }
    }
    
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
        
        if ($fechaEntrada && $fechaSalida) {
            $sql = "
                SELECT h.* 
                FROM habitaciones h
                WHERE h.id NOT IN (
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
    
    public function updateStatus() {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $id = $_POST["reservation_id"];
            $nuevoEstado = $_POST["nuevo_estado"];
            
            $stmt = $this->hotelDb->prepare("UPDATE reservas SET estado = ? WHERE id = ?");
            $stmt->execute([$nuevoEstado, $id]);
            
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

file_put_contents($baseDir . "/controllers/HotelReservationsController.php", $reservationsControllerUpdate);
$created[] = "HotelReservationsController.php actualizado con walk-in";

// 2. Actualizar index.php para incluir rutas de walk-in
$indexPath = $baseDir . "/public/index.php";
$indexContent = file_get_contents($indexPath);

if (strpos($indexContent, 'walkin') === false) {
    $indexContent = str_replace(
        'if ($subAction === "create") {
            $controller->create();
        } elseif ($subAction === "update-status") {
            $controller->updateStatus();
        } else {
            $controller->index();
        }',
        'if ($subAction === "create") {
            $controller->create();
        } elseif ($subAction === "walkin") {
            $controller->walkin();
        } elseif ($subAction === "create-walkin") {
            $controller->createWalkin();
        } elseif ($subAction === "update-status") {
            $controller->updateStatus();
        } else {
            $controller->index();
        }',
        $indexContent
    );
    
    file_put_contents($indexPath, $indexContent);
    $created[] = "index.php actualizado con rutas walk-in";
}

// 3. Crear vista de walk-in
$walkinView = <<<'EOD'
<?php include BASE_PATH . "/views/hotel/_header.php"; ?>

<div class="page-header">
    <div>
        <h1>🚶 Check-in Directo (Walk-in)</h1>
        <p class="subtitle">Cliente sin reserva previa</p>
    </div>
    <a href="/hotel/reservas" class="btn-secondary">← Volver</a>
</div>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error">
        ❌ Error: <?= htmlspecialchars($_GET['error']) ?>
    </div>
<?php endif; ?>

<div class="card">
    <form method="POST" action="/hotel/reservas/create-walkin" id="walkinForm">
        <div class="form-container">
            
            <!-- PASO 1: Cliente -->
            <div class="step-section active">
                <div class="step-header">
                    <span class="step-number">1</span>
                    <h3>Información del Cliente</h3>
                </div>
                
                <div class="form-group">
                    <label class="form-label">¿Es cliente registrado?</label>
                    <select id="clienteTipo" class="form-control" onchange="toggleClienteForm()">
                        <option value="new">Cliente Nuevo</option>
                        <option value="existing">Cliente Existente</option>
                    </select>
                </div>
                
                <div id="clienteExistenteDiv" style="display: none;">
                    <div class="form-group">
                        <label class="form-label">Seleccionar Cliente</label>
                        <select name="cliente_id" id="clienteExistente" class="form-control">
                            <option value="">Buscar cliente...</option>
                            <?php foreach ($clientes as $cliente): ?>
                                <option value="<?= $cliente['id'] ?>">
                                    <?= htmlspecialchars($cliente['nombre']) ?> - <?= htmlspecialchars($cliente['documento']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div id="clienteNuevoDiv">
                    <input type="hidden" name="cliente_id" value="new">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Nombre Completo *</label>
                            <input type="text" name="cliente_nombre" id="clienteNombre" 
                                   class="form-control" placeholder="Juan Pérez">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Documento *</label>
                            <input type="text" name="cliente_documento" id="clienteDocumento" 
                                   class="form-control" placeholder="DNI o Pasaporte">
                        </div>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" name="cliente_email" class="form-control" 
                                   placeholder="cliente@email.com">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Teléfono</label>
                            <input type="tel" name="cliente_telefono" class="form-control" 
                                   placeholder="+51 999 999 999">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- PASO 2: Habitación -->
            <div class="step-section active" style="margin-top: 20px;">
                <div class="step-header">
                    <span class="step-number">2</span>
                    <h3>Seleccionar Habitación</h3>
                </div>
                
                <?php if (empty($habitaciones)): ?>
                    <div class="alert alert-warning">
                        ⚠️ No hay habitaciones disponibles en este momento.
                    </div>
                <?php else: ?>
                    <div class="habitaciones-disponibles">
                        <?php foreach ($habitaciones as $hab): ?>
                            <label class="habitacion-option">
                                <input type="radio" name="habitacion_id" value="<?= $hab['id'] ?>" 
                                       data-precio="<?= $hab['precio_noche'] ?>" required>
                                <div class="habitacion-card">
                                    <div class="habitacion-numero">#<?= $hab['numero_habitacion'] ?></div>
                                    <div class="habitacion-tipo"><?= htmlspecialchars($hab['tipo']) ?></div>
                                    <div class="habitacion-info">
                                        <span>👥 <?= $hab['capacidad'] ?> personas</span>
                                        <span class="habitacion-precio">S/ <?= number_format($hab['precio_noche'], 2) ?>/noche</span>
                                    </div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- PASO 3: Fechas y Detalles -->
            <div class="step-section active" style="margin-top: 20px;">
                <div class="step-header">
                    <span class="step-number">3</span>
                    <h3>Detalles de la Estadía</h3>
                </div>
                
                <div class="info-box">
                    <strong>📅 Check-in:</strong> HOY (<?= date('d/m/Y') ?>) a las <?= date('H:i') ?>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Fecha de Salida *</label>
                        <input type="date" name="fecha_salida" id="fechaSalida" class="form-control" 
                               required min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Número de Huéspedes *</label>
                        <input type="number" name="numero_huespedes" id="numHuespedes" 
                               class="form-control" min="1" value="1" required>
                    </div>
                </div>
                
                <div class="horarios-section">
                    <h4>⏰ Opciones de Horario</h4>
                    <label class="checkbox-card">
                        <input type="checkbox" name="late_checkout" id="lateCheckout" value="1">
                        <div class="checkbox-content">
                            <div class="checkbox-title">⚡ Late Check-out</div>
                            <div class="checkbox-desc">Salida hasta 6:00 PM</div>
                            <div class="checkbox-price">+S/ 50.00</div>
                        </div>
                    </label>
                </div>
                
                <div class="price-summary">
                    <div class="price-row">
                        <span>Precio por noche:</span>
                        <strong id="precioNoche">S/ 0.00</strong>
                    </div>
                    <div class="price-row">
                        <span>Número de noches:</span>
                        <strong id="numeroNoches">0</strong>
                    </div>
                    <div class="price-row">
                        <span>Subtotal:</span>
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
                              placeholder="Notas especiales..."></textarea>
                </div>
            </div>
            
            <!-- PASO 4: Registro de Huéspedes -->
            <div class="step-section active" style="margin-top: 20px;">
                <div class="step-header">
                    <span class="step-number">4</span>
                    <h3>Registro de Huéspedes</h3>
                </div>
                
                <div class="checkin-alert">
                    <strong>📋 Importante:</strong>
                    <p style="margin: 5px 0 0 0; color: #1e40af;">
                        Registra los datos de TODOS los huéspedes que se hospedarán.
                    </p>
                </div>
                
                <div class="section-header">
                    <button type="button" onclick="agregarHuespedWalkin()" class="btn-add">
                        + Agregar Huésped
                    </button>
                </div>
                
                <div id="huespedesContainer">
                    <!-- Se llenarán con JavaScript -->
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn-primary btn-large">
                    ✅ Realizar Check-in Directo
                </button>
                <a href="/hotel/reservas" class="btn-secondary">Cancelar</a>
            </div>
        </div>
    </form>
</div>

<style>
.step-section {
    padding: 25px;
    background: white;
    border-radius: 12px;
    border: 2px solid #667eea;
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

.step-header h3 {
    margin: 0;
    color: #1f2937;
    font-size: 20px;
}

.form-container { padding: 30px; }
.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.form-group { margin-bottom: 20px; }
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

.form-control:focus {
    outline: none;
    border-color: #667eea;
}

.habitaciones-disponibles {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 15px;
}

.habitacion-option {
    cursor: pointer;
    display: block;
}

.habitacion-option input[type="radio"] {
    display: none;
}

.habitacion-card {
    background: #f9fafb;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 20px;
    transition: all 0.3s;
}

.habitacion-option input[type="radio"]:checked + .habitacion-card {
    border-color: #10b981;
    background: #f0fdf4;
}

.habitacion-option:hover .habitacion-card {
    border-color: #667eea;
    transform: translateY(-3px);
}

.habitacion-numero {
    font-size: 24px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 5px;
}

.habitacion-tipo {
    font-size: 16px;
    color: #6b7280;
    font-weight: 600;
    margin-bottom: 10px;
}

.habitacion-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 14px;
}

.habitacion-precio {
    font-size: 18px;
    font-weight: 700;
    color: #10b981;
}

.info-box {
    background: #dbeafe;
    border-left: 4px solid #3b82f6;
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    color: #1e40af;
}

.checkin-alert {
    background: #fef3c7;
    border-left: 4px solid #f59e0b;
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.checkin-alert strong {
    color: #92400e;
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

.checkbox-card input[type="checkbox"]:checked + .checkbox-content {
    border-color: #667eea;
    background: #f0f9ff;
}

.checkbox-title {
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 5px;
}

.checkbox-desc {
    color: #6b7280;
    font-size: 13px;
    margin-bottom: 8px;
}

.checkbox-price {
    color: #10b981;
    font-weight: 700;
    font-size: 16px;
}

.price-summary {
    background: #f9fafb;
    border-radius: 10px;
    padding: 20px;
    margin: 20px 0;
}

.price-row {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #e5e7eb;
}

.price-row:last-child {
    border-bottom: none;
}

.price-total {
    font-size: 18px;
    color: #667eea;
    margin-top: 10px;
    padding-top: 15px;
    border-top: 2px solid #667eea !important;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.btn-add {
    padding: 10px 20px;
    background: #10b981;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
}

.huesped-card {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 15px;
    position: relative;
}

.huesped-card.titular {
    border-color: #10b981;
    background: #f0fdf4;
}

.huesped-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.huesped-number {
    font-weight: 700;
    color: #1f2937;
    font-size: 16px;
}

.titular-badge {
    background: #10b981;
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    margin-left: 10px;
}

.btn-remove {
    background: #fee2e2;
    color: #991b1b;
    border: none;
    padding: 6px 12px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 12px;
    font-weight: 600;
}

.form-grid-2 {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
}

.form-actions {
    display: flex;
    gap: 15px;
    margin-top: 30px;
    padding-top: 30px;
    border-top: 2px solid #e5e7eb;
}

.btn-primary, .btn-secondary {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    text-decoration: none;
    display: inline-block;
}

.btn-primary {
    background: #10b981;
    color: white;
}

.btn-primary:hover {
    background: #059669;
}

.btn-primary.btn-large {
    padding: 16px 40px;
    font-size: 16px;
    flex: 1;
}

.btn-secondary {
    background: #f3f4f6;
    color: #374151;
}

.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin: 20px;
}

.alert-error {
    background: #fee2e2;
    color: #991b1b;
    border-left: 4px solid #ef4444;
}

.alert-warning {
    background: #fef3c7;
    color: #92400e;
    border-left: 4px solid #f59e0b;
}
</style>

<script>
let contadorHuespedesWalkin = 0;

function toggleClienteForm() {
    const tipo = document.getElementById('clienteTipo').value;
    const nuevoDiv = document.getElementById('clienteNuevoDiv');
    const existenteDiv = document.getElementById('clienteExistenteDiv');
    
    if (tipo === 'new') {
        nuevoDiv.style.display = 'block';
        existenteDiv.style.display = 'none';
        document.getElementById('clienteNombre').required = true;
        document.getElementById('clienteDocumento').required = true;
        document.getElementById('clienteExistente').required = false;
    } else {
        nuevoDiv.style.display = 'none';
        existenteDiv.style.display = 'block';
        document.getElementById('clienteNombre').required = false;
        document.getElementById('clienteDocumento').required = false;
        document.getElementById('clienteExistente').required = true;
    }
}

function agregarHuespedWalkin(esTitular = false, datos = {}) {
    contadorHuespedesWalkin++;
    const container = document.getElementById('huespedesContainer');
    const index = contadorHuespedesWalkin - 1;
    
    const huespedHTML = `
        <div class="huesped-card ${esTitular ? 'titular' : ''}" id="huesped-walkin-${index}">
            <div class="huesped-header">
                <div class="huesped-number">
                    Huésped ${contadorHuespedesWalkin}
                    ${esTitular ? '<span class="titular-badge">TITULAR</span>' : ''}
                </div>
                ${!esTitular ? `<button type="button" onclick="eliminarHuespedWalkin(${index})" class="btn-remove">✕ Eliminar</button>` : ''}
            </div>
            
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">Nombre Completo *</label>
                    <input type="text" name="huespedes[${index}][nombre]" 
                           class="form-control" required 
                           value="${datos.nombre || ''}"
                           placeholder="Nombre completo">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Tipo Documento *</label>
                    <select name="huespedes[${index}][tipo_documento]" class="form-control" required>
                        <option value="DNI" selected>DNI</option>
                        <option value="Pasaporte">Pasaporte</option>
                        <option value="Carnet de Extranjería">Carnet de Extranjería</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>
            </div>
            
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">N° Documento *</label>
                    <input type="text" name="huespedes[${index}][documento]" 
                           class="form-control" required 
                           value="${datos.documento || ''}"
                           placeholder="12345678">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Fecha Nacimiento</label>
                    <input type="date" name="huespedes[${index}][fecha_nacimiento]" 
                           class="form-control" max="${new Date().toISOString().split('T')[0]}">
                </div>
            </div>
            
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">Nacionalidad</label>
                    <input type="text" name="huespedes[${index}][nacionalidad]" 
                           class="form-control" 
                           value="Peruana"
                           placeholder="Peruana">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Teléfono</label>
                    <input type="tel" name="huespedes[${index}][telefono]" 
                           class="form-control" 
                           value="${datos.telefono || ''}"
                           placeholder="+51 999 999 999">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="huespedes[${index}][email]" 
                       class="form-control" 
                       value="${datos.email || ''}"
                       placeholder="correo@ejemplo.com">
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', huespedHTML);
}

function eliminarHuespedWalkin(index) {
    if (confirm('¿Eliminar este huésped?')) {
        document.getElementById(`huesped-walkin-${index}`).remove();
    }
}

// Calcular precio
document.addEventListener('DOMContentLoaded', function() {
    // Agregar primer huésped automáticamente
    agregarHuespedWalkin(true);
    
    const habitaciones = document.querySelectorAll('input[name="habitacion_id"]');
    const fechaSalida = document.getElementById('fechaSalida');
    const lateCheckout = document.getElementById('lateCheckout');
    const numHuespedes = document.getElementById('numHuespedes');
    
    function calcularTotal() {
        const habitacionSeleccionada = document.querySelector('input[name="habitacion_id"]:checked');
        if (!habitacionSeleccionada || !fechaSalida.value) return;
        
        const precio = parseFloat(habitacionSeleccionada.dataset.precio);
        const hoy = new Date();
        const salida = new Date(fechaSalida.value);
        const noches = Math.max(1, Math.ceil((salida - hoy) / (1000 * 60 * 60 * 24)));
        
        const subtotal = precio * noches;
        let cargoExtra = 0;
        
        if (lateCheckout.checked) cargoExtra += 50;
        
        const total = subtotal + cargoExtra;
        
        document.getElementById('precioNoche').textContent = 'S/ ' + precio.toFixed(2);
        document.getElementById('numeroNoches').textContent = noches;
        document.getElementById('subtotal').textContent = 'S/ ' + subtotal.toFixed(2);
        document.getElementById('cargoExtra').textContent = 'S/ ' + cargoExtra.toFixed(2);
        document.getElementById('precioTotal').textContent = 'S/ ' + total.toFixed(2);
        
        document.getElementById('cargoExtraRow').style.display = cargoExtra > 0 ? 'flex' : 'none';
    }
    
    habitaciones.forEach(hab => hab.addEventListener('change', calcularTotal));
    if (fechaSalida) fechaSalida.addEventListener('change', calcularTotal);
    if (lateCheckout) lateCheckout.addEventListener('change', calcularTotal);
    
    // Sincronizar número de huéspedes
    if (numHuespedes) {
        numHuespedes.addEventListener('change', function() {
            const num = parseInt(this.value);
            const actual = document.querySelectorAll('#huespedesContainer .huesped-card').length;
            
            if (num > actual) {
                for (let i = actual; i < num; i++) {
                    agregarHuespedWalkin(false);
                }
            }
        });
    }
});
</script>

<?php include BASE_PATH . "/views/hotel/_footer.php"; ?>
EOD;

file_put_contents($baseDir . "/views/hotel/walkin_form.php", $walkinView);
$created[] = "walkin_form.php creado";

// 4. Actualizar vista de reservas para agregar botón de walk-in
$reservationsViewUpdate = file_get_contents($baseDir . "/views/hotel/reservations.php");
$reservationsViewUpdate = str_replace(
    '<a href="/hotel/reservas/create" class="btn-primary">+ Nueva Reserva</a>',
    '<div style="display: flex; gap: 10px;">
        <a href="/hotel/reservas/create" class="btn-primary">📅 Nueva Reserva</a>
        <a href="/hotel/reservas/walkin" class="btn-success">🚶 Check-in Directo</a>
    </div>',
    $reservationsViewUpdate
);

// Agregar estilo para btn-success
if (strpos($reservationsViewUpdate, '.btn-success') === false) {
    $reservationsViewUpdate = str_replace(
        '</style>',
        '.btn-success {
    background: #10b981;
    color: white;
    padding: 12px 24px;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    display: inline-block;
}
.btn-success:hover {
    background: #059669;
}
</style>',
        $reservationsViewUpdate
    );
}

file_put_contents($baseDir . "/views/hotel/reservations.php", $reservationsViewUpdate);
$created[] = "reservations.php - Botón de walk-in agregado";

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>🚶 Sistema Walk-in Implementado</title>
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
            border-left: 4px solid #10b981;
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
        .steps {
            color: #1e40af;
            margin: 10px 0 0 20px;
        }
        .comparison {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 20px 0;
        }
        .comparison-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            border: 2px solid #e5e7eb;
        }
        .comparison-card h3 {
            color: #1f2937;
            margin-bottom: 15px;
        }
        .comparison-card ul {
            margin: 0 0 0 20px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🚶</div>
        <h1>¡Sistema Walk-in Implementado!</h1>
        <p class="subtitle">Check-in directo sin reserva previa</p>
        
        <div class="success">
            <strong>✅ Implementación exitosa</strong><br>
            Ahora puedes hacer check-in de clientes que llegan sin reserva.
        </div>
        
        <div class="info-box">
            <h3>🎯 ¿Cómo Funciona?</h3>
            <ol class="steps">
                <li>Cliente llega al hotel <strong>sin reserva previa</strong></li>
                <li>Ir a <strong>Reservas</strong> → Click en <strong>"🚶 Check-in Directo"</strong></li>
                <li>Registrar datos del cliente (nuevo o existente)</li>
                <li>Seleccionar habitación disponible</li>
                <li>Definir fecha de salida</li>
                <li>Registrar todos los huéspedes</li>
                <li>Confirmar check-in</li>
                <li>¡Cliente hospedado inmediatamente!</li>
            </ol>
        </div>
        
        <div class="comparison">
            <div class="comparison-card" style="border-color: #f59e0b;">
                <h3>📅 Con Reserva Previa</h3>
                <ul>
                    <li>Cliente reserva con anticipación</li>
                    <li>Estado: RESERVADA</li>
                    <li>Al llegar: Check-in desde la reserva</li>
                    <li>Cambio a estado: OCUPADA</li>
                </ul>
            </div>
            
            <div class="comparison-card" style="border-color: #10b981;">
                <h3>🚶 Walk-in (Sin Reserva)</h3>
                <ul>
                    <li>Cliente llega directamente</li>
                    <li>No hay reserva previa</li>
                    <li>Se crea y confirma al instante</li>
                    <li>Estado directo: OCUPADA</li>
                </ul>
            </div>
        </div>
        
        <h3>🌟 Características:</h3>
        <div class="features">
            <div class="feature">
                <h3>👤 Cliente Nuevo o Existente</h3>
                <p>Puedes registrar un cliente nuevo o seleccionar uno existente</p>
            </div>
            
            <div class="feature">
                <h3>🏠 Habitaciones Disponibles</h3>
                <p>Muestra solo habitaciones disponibles en ese momento</p>
            </div>
            
            <div class="feature">
                <h3>📅 Check-in Inmediato</h3>
                <p>Fecha de entrada es HOY con la hora actual</p>
            </div>
            
            <div class="feature">
                <h3>📆 Fecha de Salida Flexible</h3>
                <p>Define cuántas noches se quedará el huésped</p>
            </div>
            
            <div class="feature">
                <h3>💰 Cálculo Automático</h3>
                <p>Precio se calcula automáticamente según noches</p>
            </div>
            
            <div class="feature">
                <h3>👥 Registro de Huéspedes</h3>
                <p>Registra todos los huéspedes que se hospedarán</p>
            </div>
            
            <div class="feature">
                <h3>⚡ Late Check-out</h3>
                <p>Opción de late check-out con cargo adicional</p>
            </div>
            
            <div class="feature">
                <h3>🎯 Estado Directo</h3>
                <p>Se crea la reserva y pasa directamente a OCUPADA</p>
            </div>
        </div>
        
        <div class="info-box" style="background: #dcfce7; border-color: #10b981;">
            <h3 style="color: #166534;">✨ Ventajas del Walk-in:</h3>
            <ul style="color: #166534; margin: 10px 0 0 20px;">
                <li><strong>Rápido:</strong> Check-in en un solo paso</li>
                <li><strong>Eficiente:</strong> No necesitas crear reserva primero</li>
                <li><strong>Completo:</strong> Registra todos los datos necesarios</li>
                <li><strong>Profesional:</strong> Mismo nivel de detalle que reserva normal</li>
                <li><strong>Flexible:</strong> Cliente nuevo o existente</li>
            </ul>
        </div>
        
        <div class="text-center">
            <a href="/hotel/reservas/walkin" class="btn">
                🚶 Probar Walk-in
            </a>
            <a href="/hotel/reservas" class="btn">
                📋 Ver Reservas
            </a>
        </div>
    </div>
</body>
</html>
