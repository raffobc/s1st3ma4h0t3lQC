<?php
/**
 * ACTUALIZAR FORMULARIO DE RESERVAS CON DISPONIBILIDAD
 * Archivo: C:\xampp\htdocs\hotel-system\update_reservation_form.php
 */

$baseDir = __DIR__;

// 1. Actualizar HotelReservationsController.php para incluir búsqueda de disponibilidad
$controllerContent = <<<'EOD'
<?php
class HotelReservationsController {
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
            
            // Calcular días y total
            $entrada = new DateTime($fechaEntrada);
            $salida = new DateTime($fechaSalida);
            $dias = $entrada->diff($salida)->days;
            
            // Obtener precio de la habitación
            $stmt = $this->hotelDb->prepare("SELECT precio_noche FROM habitaciones WHERE id = ?");
            $stmt->execute([$habitacionId]);
            $precioNoche = $stmt->fetch()["precio_noche"];
            
            $total = $dias * $precioNoche;
            
            // Crear reserva
            $stmt = $this->hotelDb->prepare("
                INSERT INTO reservas (cliente_id, habitacion_id, fecha_entrada, fecha_salida, 
                                     numero_huespedes, precio_total, observaciones, estado)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'reservada')
            ");
            
            $stmt->execute([
                $clienteId, $habitacionId, $fechaEntrada, $fechaSalida,
                $numHuespedes, $total, $observaciones
            ]);
            
            // Actualizar estado de habitación
            $stmt = $this->hotelDb->prepare("UPDATE habitaciones SET estado = 'reservada' WHERE id = ?");
            $stmt->execute([$habitacionId]);
            
            header("Location: /hotel/reservas");
            exit;
        }
        
        // Obtener clientes
        $clientes = $this->hotelDb->query("SELECT * FROM clientes ORDER BY nombre")->fetchAll();
        
        // Obtener habitaciones disponibles (si hay fechas en GET)
        $habitaciones = [];
        $fechaEntrada = $_GET["fecha_entrada"] ?? null;
        $fechaSalida = $_GET["fecha_salida"] ?? null;
        
        if ($fechaEntrada && $fechaSalida) {
            // Buscar habitaciones que NO tienen reservas activas en esas fechas
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
        
        require_once BASE_PATH . "/views/hotel/reservation_form.php";
    }
    
    public function updateStatus() {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $id = $_POST["reservation_id"];
            $nuevoEstado = $_POST["nuevo_estado"];
            
            $stmt = $this->hotelDb->prepare("UPDATE reservas SET estado = ? WHERE id = ?");
            $stmt->execute([$nuevoEstado, $id]);
            
            // Actualizar estado de habitación según el nuevo estado
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

// 2. Crear nueva vista de formulario de reservas
$reservationFormView = <<<'EOD'
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
        <div class="step-section <?= empty($habitaciones) && (!isset($_GET['fecha_entrada']) || !isset($_GET['fecha_salida'])) ? 'active' : 'completed' ?>">
            <div class="step-header">
                <span class="step-number">1</span>
                <h3>Selecciona las Fechas</h3>
            </div>
            
            <form method="GET" action="" id="dateForm">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Fecha de Entrada *</label>
                        <input type="date" name="fecha_entrada" id="fechaEntrada" class="form-control" 
                               required min="<?= date('Y-m-d') ?>" 
                               value="<?= htmlspecialchars($_GET['fecha_entrada'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Fecha de Salida *</label>
                        <input type="date" name="fecha_salida" id="fechaSalida" class="form-control" 
                               required value="<?= htmlspecialchars($_GET['fecha_salida'] ?? '') ?>">
                    </div>
                </div>
                
                <button type="submit" class="btn-primary" id="btnBuscar">
                    🔍 Buscar Habitaciones Disponibles
                </button>
            </form>
        </div>
        
        <!-- PASO 2: Mostrar Habitaciones Disponibles y Formulario -->
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
                    
                    <form method="POST" action="/hotel/reservas/create">
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
                                                data-precio="<?= $hab['precio_noche'] ?>"
                                                data-tipo="<?= $hab['tipo'] ?>">
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
                            <div class="price-row price-total">
                                <span>Total estimado:</span>
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

.step-section.completed .step-number {
    background: #10b981;
}

.step-header h3 {
    margin: 0;
    color: #1f2937;
    font-size: 20px;
}

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
    transition: border 0.3s;
}

.form-control:focus {
    outline: none;
    border-color: #667eea;
}

.form-help {
    display: block;
    margin-top: 5px;
    font-size: 12px;
    color: #6b7280;
}

.form-help a {
    color: #667eea;
    text-decoration: none;
    font-weight: 600;
}

.btn-primary, .btn-secondary {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    display: inline-block;
    text-decoration: none;
    transition: all 0.2s;
}

.btn-primary {
    background: #667eea;
    color: white;
}

.btn-primary:hover {
    background: #5568d3;
    transform: translateY(-2px);
}

.btn-secondary {
    background: #f3f4f6;
    color: #374151;
}

.btn-secondary:hover {
    background: #e5e7eb;
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

.room-option-body {
    padding: 15px;
}

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

.form-actions {
    display: flex;
    gap: 15px;
    margin-top: 30px;
    padding-top: 30px;
    border-top: 2px solid #e5e7eb;
}

.alert {
    padding: 15px;
    border-radius: 8px;
    margin: 20px 0;
}

.alert-warning {
    background: #fef3c7;
    color: #92400e;
    border-left: 4px solid #f59e0b;
}

.alert a {
    color: #92400e;
    font-weight: 700;
    text-decoration: underline;
}
</style>

<script>
// Actualizar fecha mínima de salida
document.getElementById('fechaEntrada').addEventListener('change', function() {
    document.getElementById('fechaSalida').min = this.value;
});

// Calcular total cuando se selecciona habitación
const habitacionSelect = document.getElementById('habitacionSelect');
if (habitacionSelect) {
    habitacionSelect.addEventListener('change', function() {
        const option = this.selectedOptions[0];
        const precio = parseFloat(option.dataset.precio || 0);
        const noches = parseInt(document.getElementById('numeroNoches').textContent);
        
        document.getElementById('precioNoche').textContent = 'S/ ' + precio.toFixed(2);
        document.getElementById('precioTotal').textContent = 'S/ ' + (precio * noches).toFixed(2);
    });
}

// Click en habitación para seleccionarla
document.querySelectorAll('.room-option').forEach(room => {
    room.addEventListener('click', function() {
        const roomId = this.dataset.roomId;
        const select = document.getElementById('habitacionSelect');
        select.value = roomId;
        select.dispatchEvent(new Event('change'));
        
        // Resaltar selección
        document.querySelectorAll('.room-option').forEach(r => r.style.borderColor = '#e5e7eb');
        this.style.borderColor = '#667eea';
        this.style.background = '#f0f9ff';
    });
});
</script>

<?php include BASE_PATH . "/views/hotel/_footer.php"; ?>
EOD;

file_put_contents($baseDir . "/views/hotel/reservation_form.php", $reservationFormView);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>✅ Formulario de Reservas Actualizado</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🎉</div>
        <h1>¡Formulario Mejorado!</h1>
        <p class="subtitle">Sistema de reservas con verificación de disponibilidad</p>
        
        <div class="success">
            <strong>✅ Actualización exitosa</strong><br>
            El formulario de reservas ahora incluye búsqueda de disponibilidad en tiempo real.
        </div>
        
        <h3>🌟 Nuevas Funcionalidades:</h3>
        <div class="features">
            <div class="feature">
                <h3>📅 Paso 1: Selección de Fechas</h3>
                <p>El usuario primero selecciona las fechas de entrada y salida</p>
            </div>
            
            <div class="feature">
                <h3>🏠 Paso 2: Habitaciones Disponibles</h3>
                <p>El sistema muestra solo las habitaciones disponibles para esas fechas</p>
            </div>
            
            <div class="feature">
                <h3>✨ Paso 3: Completar Reserva</h3>
                <p>Seleccionar cliente, habitación y agregar observaciones</p>
            </div>
            
            <div class="feature">
                <h3>💰 Cálculo Automático</h3>
                <p>El precio total se calcula automáticamente según las noches</p>
            </div>
            
            <div class="feature">
                <h3>🎯 Selección Visual</h3>
                <p>Las habitaciones se muestran en tarjetas interactivas fáciles de seleccionar</p>
            </div>
        </div>
        
        <div class="text-center">
            <a href="/hotel/reservas/create" class="btn">
                🚀 Probar Nueva Reserva
            </a>
            <a href="/hotel/dashboard" class="btn">
                📊 Ir al Dashboard
            </a>
        </div>
    </div>
</body>
</html>
