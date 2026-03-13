<?php
/**
 * SISTEMA DE CHECK-IN CON REGISTRO DE HUÉSPEDES
 * Archivo: C:\xampp\htdocs\hotel-system\create_guests_system.php
 */

$baseDir = __DIR__;
$created = [];

// 1. Primero crear la tabla de huéspedes en la base de datos
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
        
        // Crear tabla de huéspedes si no existe
        $hotelDb->exec("
            CREATE TABLE IF NOT EXISTS huespedes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                reserva_id INT NOT NULL,
                nombre VARCHAR(255) NOT NULL,
                documento VARCHAR(50) NOT NULL,
                tipo_documento ENUM('DNI', 'Pasaporte', 'Carnet de Extranjería', 'Otro') DEFAULT 'DNI',
                fecha_nacimiento DATE NULL,
                nacionalidad VARCHAR(100) DEFAULT 'Peruana',
                telefono VARCHAR(20) NULL,
                email VARCHAR(255) NULL,
                es_titular BOOLEAN DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (reserva_id) REFERENCES reservas(id) ON DELETE CASCADE,
                INDEX idx_reserva (reserva_id),
                INDEX idx_documento (documento)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        $created[] = $hotel['nombre'] . " - Tabla huespedes creada";
        
    } catch (Exception $e) {
        $created[] = $hotel['nombre'] . " - Error: " . $e->getMessage();
    }
}

// 2. Crear controlador para huéspedes
$guestsController = <<<'EOD'
<?php
class HotelGuestsController {
    private $hotelDb;
    
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
    
    public function save() {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $reservaId = $_POST["reserva_id"];
            $huespedes = $_POST["huespedes"] ?? [];
            
            // Iniciar transacción
            $this->hotelDb->beginTransaction();
            
            try {
                // Eliminar huéspedes existentes de esta reserva
                $stmt = $this->hotelDb->prepare("DELETE FROM huespedes WHERE reserva_id = ?");
                $stmt->execute([$reservaId]);
                
                // Insertar nuevos huéspedes
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
                            $index === 0 ? 1 : 0 // Primer huésped es el titular
                        ]);
                    }
                }
                
                // Actualizar estado de la reserva a 'ocupada'
                $stmt = $this->hotelDb->prepare("UPDATE reservas SET estado = 'ocupada' WHERE id = ?");
                $stmt->execute([$reservaId]);
                
                // Actualizar estado de la habitación
                $stmt = $this->hotelDb->prepare("
                    UPDATE habitaciones h
                    JOIN reservas r ON h.id = r.habitacion_id
                    SET h.estado = 'ocupada'
                    WHERE r.id = ?
                ");
                $stmt->execute([$reservaId]);
                
                $this->hotelDb->commit();
                
                // Si es AJAX
                if (!empty($_POST["ajax"])) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true]);
                    exit;
                }
                
                header("Location: /hotel/reservas?success=checkin");
                exit;
                
            } catch (Exception $e) {
                $this->hotelDb->rollBack();
                
                if (!empty($_POST["ajax"])) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                    exit;
                }
                
                header("Location: /hotel/reservas?error=checkin");
                exit;
            }
        }
    }
    
    public function getByReserva() {
        $reservaId = $_GET["reserva_id"] ?? 0;
        
        $stmt = $this->hotelDb->prepare("
            SELECT * FROM huespedes 
            WHERE reserva_id = ? 
            ORDER BY es_titular DESC, id ASC
        ");
        $stmt->execute([$reservaId]);
        $huespedes = $stmt->fetchAll();
        
        header('Content-Type: application/json');
        echo json_encode($huespedes);
        exit;
    }
}
EOD;

file_put_contents($baseDir . "/controllers/HotelGuestsController.php", $guestsController);
$created[] = "HotelGuestsController.php creado";

// 3. Actualizar index.php para incluir rutas de huéspedes
$indexPath = $baseDir . "/public/index.php";
$indexContent = file_get_contents($indexPath);

if (strpos($indexContent, 'huespedes') === false) {
    $huespedRoutesCode = '
    } elseif ($action === "huespedes") {
        require_once "../controllers/HotelGuestsController.php";
        $subAction = $uri[2] ?? "save";
        $controller = new HotelGuestsController();
        
        if ($subAction === "save") {
            $controller->save();
        } elseif ($subAction === "get") {
            $controller->getByReserva();
        }';
    
    $indexContent = str_replace(
        '} elseif ($action === "clientes") {',
        $huespedRoutesCode . '
    } elseif ($action === "clientes") {',
        $indexContent
    );
    
    file_put_contents($indexPath, $indexContent);
    $created[] = "index.php actualizado con rutas de huéspedes";
}

// 4. Actualizar vista de reservas con modal mejorado
$reservationsView = <<<'EOD'
<?php include BASE_PATH . "/views/hotel/_header.php"; ?>

<div class="page-header">
    <div>
        <h1>📅 Reservas</h1>
        <p class="subtitle">Gestiona las reservas del hotel</p>
    </div>
    <a href="/hotel/reservas/create" class="btn-primary">+ Nueva Reserva</a>
</div>

<?php if (isset($_GET['success']) && $_GET['success'] === 'checkin'): ?>
    <div class="alert alert-success">
        ✅ Check-in realizado exitosamente. Los huéspedes han sido registrados.
    </div>
<?php endif; ?>

<?php if (isset($_GET['error']) && $_GET['error'] === 'checkin'): ?>
    <div class="alert alert-error">
        ❌ Error al realizar el check-in. Por favor intenta nuevamente.
    </div>
<?php endif; ?>

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
        </div>
    <?php else: ?>
        <div class="reservations-grid">
            <?php foreach ($reservations as $reserva): ?>
                <div class="reservation-card status-<?= $reserva['estado'] ?>">
                    <div class="reservation-header">
                        <div class="reservation-id">#<?= $reserva['id'] ?></div>
                        <span class="status-badge status-<?= $reserva['estado'] ?>">
                            <?= ucfirst($reserva['estado']) ?>
                        </span>
                    </div>
                    
                    <div class="reservation-body">
                        <div class="reservation-client">
                            <strong>👤 <?= htmlspecialchars($reserva['cliente_nombre']) ?></strong>
                            <small><?= htmlspecialchars($reserva['cliente_documento']) ?></small>
                            <small>📧 <?= htmlspecialchars($reserva['cliente_email']) ?></small>
                            <small>📱 <?= htmlspecialchars($reserva['cliente_telefono']) ?></small>
                        </div>
                        
                        <div class="reservation-room">
                            <span class="room-badge">🏠 Habitación <?= htmlspecialchars($reserva['numero_habitacion']) ?></span>
                            <span><?= htmlspecialchars($reserva['tipo_habitacion']) ?></span>
                        </div>
                        
                        <div class="reservation-dates">
                            <div class="date-item">
                                <strong>Check-in:</strong>
                                <span><?= date('d/m/Y', strtotime($reserva['fecha_entrada'])) ?></span>
                                <small>⏰ <?= isset($reserva['hora_entrada']) ? date('g:i A', strtotime($reserva['hora_entrada'])) : '3:00 PM' ?></small>
                            </div>
                            
                            <div class="date-item">
                                <strong>Check-out:</strong>
                                <span><?= date('d/m/Y', strtotime($reserva['fecha_salida'])) ?></span>
                                <small>⏰ <?= isset($reserva['hora_salida']) ? date('g:i A', strtotime($reserva['hora_salida'])) : '12:00 PM' ?></small>
                            </div>
                        </div>
                        
                        <div class="reservation-info">
                            <div>👥 <?= $reserva['numero_huespedes'] ?> huéspedes</div>
                            <div class="reservation-price">
                                <strong>S/ <?= number_format($reserva['precio_total'], 2) ?></strong>
                            </div>
                        </div>
                    </div>
                    
                    <div class="reservation-actions">
                        <?php if ($reserva['estado'] === 'reservada'): ?>
                            <button onclick="mostrarCheckinModal(<?= htmlspecialchars(json_encode($reserva)) ?>)" 
                                    class="btn-action-full btn-checkin">
                                ✅ Realizar Check-in
                            </button>
                        <?php endif; ?>
                        
                        <?php if ($reserva['estado'] === 'ocupada'): ?>
                            <button onclick="cambiarEstado(<?= $reserva['id'] ?>, 'finalizada')" 
                                    class="btn-action-full btn-checkout">
                                🏁 Realizar Check-out
                            </button>
                        <?php endif; ?>
                        
                        <?php if (in_array($reserva['estado'], ['reservada', 'ocupada'])): ?>
                            <button onclick="cambiarEstado(<?= $reserva['id'] ?>, 'cancelada')" 
                                    class="btn-action-outline btn-cancel">
                                ❌ Cancelar
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal de Check-in con Huéspedes -->
<div id="checkinModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2>✅ Check-in - Registro de Huéspedes</h2>
            <button onclick="cerrarModalCheckin()" class="btn-close">×</button>
        </div>
        <div class="modal-body">
            <form id="checkinForm" onsubmit="guardarCheckin(event)">
                <input type="hidden" name="reserva_id" id="reservaId">
                
                <div class="checkin-alert">
                    <strong>📋 Información Importante:</strong>
                    <ul>
                        <li>Solicitar documento de identidad de TODOS los huéspedes</li>
                        <li>El primer huésped registrado será el titular</li>
                        <li>Puedes agregar más huéspedes si es necesario</li>
                        <li>Verifica que los datos sean correctos</li>
                    </ul>
                </div>
                
                <div class="checkin-summary" id="reservaSummary">
                    <!-- Se llenará con JavaScript -->
                </div>
                
                <div class="huespedes-section">
                    <div class="section-header">
                        <h3>👥 Registro de Huéspedes</h3>
                        <button type="button" onclick="agregarHuesped()" class="btn-add">
                            + Agregar Huésped
                        </button>
                    </div>
                    
                    <div id="huespedesContainer">
                        <!-- Se llenarán los huéspedes con JavaScript -->
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button type="submit" class="btn-confirm" id="btnConfirmarCheckin">
                        ✅ Confirmar Check-in
                    </button>
                    <button type="button" onclick="cerrarModalCheckin()" class="btn-cancel-modal">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<form id="statusForm" method="POST" action="/hotel/reservas/update-status" style="display: none;">
    <input type="hidden" name="reservation_id" id="reservationId">
    <input type="hidden" name="nuevo_estado" id="nuevoEstado">
</form>

<style>
.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin: 20px;
    font-weight: 600;
}
.alert-success {
    background: #dcfce7;
    color: #166534;
    border-left: 4px solid #10b981;
}
.alert-error {
    background: #fee2e2;
    color: #991b1b;
    border-left: 4px solid #ef4444;
}

.reservations-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 20px;
    padding: 20px;
}

.reservation-card {
    background: white;
    border-radius: 12px;
    border: 2px solid #e5e7eb;
    overflow: hidden;
}

.reservation-card.status-reservada { border-left: 5px solid #f59e0b; }
.reservation-card.status-ocupada { border-left: 5px solid #10b981; }

.reservation-header {
    background: #f9fafb;
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 2px solid #e5e7eb;
}

.reservation-id {
    font-size: 18px;
    font-weight: 700;
    color: #1f2937;
}

.reservation-body {
    padding: 20px;
}

.reservation-client {
    display: flex;
    flex-direction: column;
    gap: 4px;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e5e7eb;
}

.reservation-client strong {
    font-size: 16px;
    color: #1f2937;
}

.reservation-client small {
    color: #6b7280;
    font-size: 13px;
}

.reservation-room {
    display: flex;
    flex-direction: column;
    gap: 5px;
    margin-bottom: 15px;
}

.reservation-dates {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-bottom: 15px;
    padding: 15px;
    background: #f9fafb;
    border-radius: 8px;
}

.date-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.reservation-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    background: #f0f9ff;
    border-radius: 8px;
}

.reservation-price strong {
    color: #10b981;
    font-size: 20px;
}

.reservation-actions {
    padding: 15px 20px;
    background: #f9fafb;
    border-top: 2px solid #e5e7eb;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.btn-action-full {
    width: 100%;
    padding: 12px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    font-size: 14px;
}

.btn-checkin {
    background: #10b981;
    color: white;
}

.btn-checkin:hover {
    background: #059669;
}

.btn-checkout {
    background: #3b82f6;
    color: white;
}

.btn-action-outline {
    width: 100%;
    padding: 10px;
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    color: #6b7280;
}

.btn-cancel:hover {
    border-color: #ef4444;
    color: #ef4444;
}

.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    overflow-y: auto;
}

.modal-large {
    max-width: 900px;
}

.modal-content {
    background: white;
    margin: 2% auto;
    width: 90%;
    max-width: 600px;
    border-radius: 15px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
    max-height: 95vh;
    display: flex;
    flex-direction: column;
}

.modal-header {
    padding: 25px 30px;
    border-bottom: 2px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-shrink: 0;
}

.modal-header h2 {
    margin: 0;
    color: #1f2937;
    font-size: 24px;
}

.btn-close {
    background: none;
    border: none;
    font-size: 32px;
    color: #6b7280;
    cursor: pointer;
    width: 40px;
    height: 40px;
}

.modal-body {
    padding: 30px;
    overflow-y: auto;
    flex: 1;
}

.checkin-alert {
    background: #dbeafe;
    border-left: 4px solid #3b82f6;
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 25px;
}

.checkin-alert strong {
    color: #1e40af;
    display: block;
    margin-bottom: 10px;
}

.checkin-alert ul {
    margin: 0 0 0 20px;
    color: #1e40af;
    font-size: 14px;
}

.checkin-summary {
    background: #f9fafb;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 25px;
}

.checkin-item {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #e5e7eb;
}

.checkin-item:last-child {
    border-bottom: none;
}

.huespedes-section {
    margin-bottom: 25px;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.section-header h3 {
    margin: 0;
    color: #1f2937;
    font-size: 20px;
}

.btn-add {
    padding: 8px 16px;
    background: #10b981;
    color: white;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    font-size: 13px;
}

.btn-add:hover {
    background: #059669;
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

.btn-remove:hover {
    background: #fecaca;
}

.form-grid-2 {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
}

.form-group {
    margin-bottom: 15px;
}

.form-label {
    display: block;
    margin-bottom: 6px;
    font-weight: 600;
    color: #374151;
    font-size: 13px;
}

.form-control {
    width: 100%;
    padding: 10px 12px;
    border: 2px solid #e5e7eb;
    border-radius: 6px;
    font-size: 14px;
}

.form-control:focus {
    outline: none;
    border-color: #667eea;
}

.modal-actions {
    display: flex;
    gap: 10px;
    margin-top: 25px;
    padding-top: 20px;
    border-top: 2px solid #e5e7eb;
}

.btn-confirm {
    flex: 1;
    padding: 15px;
    background: #10b981;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 700;
    cursor: pointer;
    font-size: 16px;
}

.btn-confirm:hover {
    background: #059669;
}

.btn-cancel-modal {
    padding: 15px 25px;
    background: #f3f4f6;
    color: #374151;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
}

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
    border: 2px solid transparent;
}

.filter-btn.active {
    background: #667eea;
    color: white;
}

.room-badge {
    background: #dbeafe;
    color: #1e40af;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
}

.status-badge {
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.status-reservada { background: #fef3c7; color: #92400e; }
.status-ocupada { background: #dcfce7; color: #166534; }
.status-finalizada { background: #f3f4f6; color: #374151; }
.status-cancelada { background: #fee2e2; color: #991b1b; }

.loading {
    pointer-events: none;
    opacity: 0.6;
}
</style>

<script>
let contadorHuespedes = 0;
let reservaActual = null;

function mostrarCheckinModal(reserva) {
    reservaActual = reserva;
    const modal = document.getElementById('checkinModal');
    document.getElementById('reservaId').value = reserva.id;
    
    // Llenar resumen
    const summary = document.getElementById('reservaSummary');
    const fechaEntrada = new Date(reserva.fecha_entrada);
    const fechaSalida = new Date(reserva.fecha_salida);
    const noches = Math.ceil((fechaSalida - fechaEntrada) / (1000 * 60 * 60 * 24));
    
    summary.innerHTML = `
        <h3 style="margin-bottom: 15px; color: #1f2937;">📋 Resumen de la Reserva</h3>
        <div class="checkin-item">
            <strong>Cliente Titular:</strong>
            <span>${reserva.cliente_nombre}</span>
        </div>
        <div class="checkin-item">
            <strong>Habitación:</strong>
            <span>#${reserva.numero_habitacion} - ${reserva.tipo_habitacion}</span>
        </div>
        <div class="checkin-item">
            <strong>Huéspedes esperados:</strong>
            <span>${reserva.numero_huespedes}</span>
        </div>
        <div class="checkin-item">
            <strong>Noches:</strong>
            <span>${noches}</span>
        </div>
    `;
    
    // Limpiar y crear huéspedes
    const container = document.getElementById('huespedesContainer');
    container.innerHTML = '';
    contadorHuespedes = 0;
    
    // Crear primer huésped (titular) con datos del cliente
    agregarHuesped(true, {
        nombre: reserva.cliente_nombre,
        documento: reserva.cliente_documento,
        email: reserva.cliente_email,
        telefono: reserva.cliente_telefono
    });
    
    // Agregar campos vacíos para los demás huéspedes
    const numHuespedes = parseInt(reserva.numero_huespedes);
    for (let i = 1; i < numHuespedes; i++) {
        agregarHuesped(false);
    }
    
    modal.style.display = 'block';
}

function cerrarModalCheckin() {
    document.getElementById('checkinModal').style.display = 'none';
}

function agregarHuesped(esTitular = false, datos = {}) {
    contadorHuespedes++;
    const container = document.getElementById('huespedesContainer');
    const index = contadorHuespedes - 1;
    
    const huespedHTML = `
        <div class="huesped-card ${esTitular ? 'titular' : ''}" id="huesped-${index}">
            <div class="huesped-header">
                <div class="huesped-number">
                    Huésped ${contadorHuespedes}
                    ${esTitular ? '<span class="titular-badge">TITULAR</span>' : ''}
                </div>
                ${!esTitular ? `<button type="button" onclick="eliminarHuesped(${index})" class="btn-remove">✕ Eliminar</button>` : ''}
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

function eliminarHuesped(index) {
    if (confirm('¿Eliminar este huésped?')) {
        document.getElementById(`huesped-${index}`).remove();
    }
}

function guardarCheckin(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    formData.append('ajax', '1');
    
    const btn = document.getElementById('btnConfirmarCheckin');
    btn.classList.add('loading');
    btn.disabled = true;
    btn.textContent = 'Procesando...';
    
    fetch('/hotel/huespedes/save', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarNotificacion('Check-in realizado exitosamente', 'success');
            setTimeout(() => {
                window.location.href = '/hotel/reservas?success=checkin';
            }, 1500);
        } else {
            mostrarNotificacion('Error al realizar check-in: ' + (data.error || 'Desconocido'), 'error');
            btn.classList.remove('loading');
            btn.disabled = false;
            btn.textContent = '✅ Confirmar Check-in';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarNotificacion('Error al realizar check-in', 'error');
        btn.classList.remove('loading');
        btn.disabled = false;
        btn.textContent = '✅ Confirmar Check-in';
    });
}

function cambiarEstado(id, estado) {
    const mensajes = {
        "finalizada": "¿Realizar el check-out?\n\nLa habitación pasará a estado de LIMPIEZA.",
        "cancelada": "¿Estás seguro de CANCELAR esta reserva?"
    };
    
    if (confirm(mensajes[estado])) {
        document.getElementById("reservationId").value = id;
        document.getElementById("nuevoEstado").value = estado;
        document.getElementById("statusForm").submit();
    }
}

function mostrarNotificacion(mensaje, tipo) {
    const notif = document.createElement('div');
    notif.className = `notification notification-${tipo}`;
    notif.textContent = mensaje;
    notif.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 25px;
        background: ${tipo === 'success' ? '#10b981' : '#ef4444'};
        color: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 10000;
        animation: slideInRight 0.3s ease;
    `;
    
    document.body.appendChild(notif);
    
    setTimeout(() => {
        notif.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => notif.remove(), 300);
    }, 3000);
}

window.onclick = function(event) {
    const modal = document.getElementById('checkinModal');
    if (event.target === modal) {
        cerrarModalCheckin();
    }
}
</script>

<?php include BASE_PATH . "/views/hotel/_footer.php"; ?>
EOD;

file_put_contents($baseDir . "/views/hotel/reservations.php", $reservationsView);
$created[] = "reservations.php - Check-in con huéspedes implementado";

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>✅ Sistema de Huéspedes Implementado</title>
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
        .steps {
            color: #1e40af;
            margin: 10px 0 0 20px;
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
        ul {
            margin: 10px 0 0 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">👥</div>
        <h1>¡Sistema de Huéspedes Implementado!</h1>
        <p class="subtitle">Registro completo de todos los huéspedes durante el check-in</p>
        
        <div class="success">
            <strong>✅ Implementación exitosa</strong><br>
            Se han creado <?= count($created) ?> componentes del sistema de huéspedes.
        </div>
        
        <div class="info-box">
            <h3>📋 Proceso de Check-in Mejorado:</h3>
            <ol class="steps">
                <li>Cliente llega al hotel con su reserva</li>
                <li>Recepcionista hace click en "✅ Realizar Check-in"</li>
                <li>Se abre modal con formulario de huéspedes</li>
                <li>El <strong>primer huésped</strong> se llena automáticamente con datos del titular</li>
                <li>Se registran los datos de TODOS los huéspedes</li>
                <li>Puedes <strong>agregar más huéspedes</strong> si llegaron más de los esperados</li>
                <li>Puedes <strong>eliminar huéspedes</strong> si llegaron menos</li>
                <li>Se confirma el check-in</li>
                <li>Reserva pasa a OCUPADA y habitación se marca como ocupada</li>
            </ol>
        </div>
        
        <h3>🌟 Características Implementadas:</h3>
        <div class="features">
            <div class="feature">
                <h3>👤 Primer Huésped Automático</h3>
                <p>El titular de la reserva se prellenará automáticamente</p>
            </div>
            
            <div class="feature">
                <h3>➕ Agregar Huéspedes</h3>
                <p>Botón para agregar más huéspedes dinámicamente</p>
            </div>
            
            <div class="feature">
                <h3>➖ Eliminar Huéspedes</h3>
                <p>Opción de eliminar huéspedes (excepto el titular)</p>
            </div>
            
            <div class="feature">
                <h3>📋 Datos Completos</h3>
                <p>Nombre, documento, tipo documento, fecha nacimiento, nacionalidad, teléfono, email</p>
            </div>
            
            <div class="feature">
                <h3>🏷️ Identificación Visual</h3>
                <p>El huésped titular tiene un badge especial y fondo verde</p>
            </div>
            
            <div class="feature">
                <h3>💾 Base de Datos</h3>
                <p>Nueva tabla "huespedes" con relación a reservas</p>
            </div>
            
            <div class="feature">
                <h3>🔒 Validación</h3>
                <p>Campos obligatorios: nombre y documento</p>
            </div>
            
            <div class="feature">
                <h3>🚀 Guardado Rápido</h3>
                <p>Todo se guarda con AJAX sin recargar la página</p>
            </div>
        </div>
        
        <div class="info-box" style="background: #dcfce7; border-color: #10b981;">
            <h3 style="color: #166534;">✨ Importante:</h3>
            <ul style="color: #166534;">
                <li><strong>Solicitar documento</strong> de TODOS los huéspedes</li>
                <li>El <strong>primer huésped</strong> siempre es el titular de la reserva</li>
                <li>Puedes agregar <strong>más huéspedes</strong> de los inicialmente reservados</li>
                <li>Los datos se almacenan para futuras estadísticas y reportes</li>
                <li>La tabla de huéspedes está vinculada a cada reserva</li>
            </ul>
        </div>
        
        <div class="text-center">
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
