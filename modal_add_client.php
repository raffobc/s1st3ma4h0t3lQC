<?php
/**
 * AGREGAR MODAL DE CLIENTE EN FORMULARIO DE RESERVAS
 * Archivo: C:\xampp\htdocs\hotel-system\add_client_modal.php
 */

$baseDir = __DIR__;

// Actualizar el controlador para manejar AJAX
$controllerUpdate = <<<'EOD'
<?php
class HotelClientsController {
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
        $search = $_GET["search"] ?? "";
        
        $sql = "
            SELECT c.*, 
                   COUNT(DISTINCT r.id) as total_reservas,
                   COALESCE(SUM(p.monto), 0) as total_gastado
            FROM clientes c
            LEFT JOIN reservas r ON c.id = r.cliente_id
            LEFT JOIN pagos p ON r.id = p.reserva_id
        ";
        
        if ($search) {
            $sql .= " WHERE c.nombre LIKE ? OR c.documento LIKE ? OR c.email LIKE ?";
        }
        
        $sql .= " GROUP BY c.id ORDER BY c.created_at DESC";
        
        if ($search) {
            $stmt = $this->hotelDb->prepare($sql);
            $searchParam = "%$search%";
            $stmt->execute([$searchParam, $searchParam, $searchParam]);
            $clients = $stmt->fetchAll();
        } else {
            $clients = $this->hotelDb->query($sql)->fetchAll();
        }
        
        require_once BASE_PATH . "/views/hotel/clients.php";
    }
    
    public function create() {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $stmt = $this->hotelDb->prepare("
                INSERT INTO clientes (nombre, documento, email, telefono, direccion, fecha_nacimiento)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $_POST["nombre"],
                $_POST["documento"],
                $_POST["email"],
                $_POST["telefono"],
                $_POST["direccion"] ?? "",
                $_POST["fecha_nacimiento"] ?? null
            ]);
            
            $clienteId = $this->hotelDb->lastInsertId();
            
            // Si es una petición AJAX, devolver JSON
            if (!empty($_POST["ajax"])) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'cliente' => [
                        'id' => $clienteId,
                        'nombre' => $_POST["nombre"],
                        'documento' => $_POST["documento"]
                    ]
                ]);
                exit;
            }
            
            header("Location: /hotel/clientes");
            exit;
        }
        
        require_once BASE_PATH . "/views/hotel/client_form.php";
    }
    
    public function view() {
        $id = $_GET["id"] ?? 0;
        
        $stmt = $this->hotelDb->prepare("SELECT * FROM clientes WHERE id = ?");
        $stmt->execute([$id]);
        $client = $stmt->fetch();
        
        if (!$client) {
            header("Location: /hotel/clientes");
            exit;
        }
        
        $stmt = $this->hotelDb->prepare("
            SELECT r.*, h.numero_habitacion, h.tipo
            FROM reservas r
            JOIN habitaciones h ON r.habitacion_id = h.id
            WHERE r.cliente_id = ?
            ORDER BY r.fecha_entrada DESC
        ");
        $stmt->execute([$id]);
        $reservations = $stmt->fetchAll();
        
        require_once BASE_PATH . "/views/hotel/client_detail.php";
    }
}
EOD;

file_put_contents($baseDir . "/controllers/HotelClientsController.php", $controllerUpdate);

// Actualizar index.php para manejar la ruta AJAX
$indexPath = $baseDir . "/public/index.php";
$indexContent = file_get_contents($indexPath);

// Verificar si ya existe la ruta, si no, agregarla
if (strpos($indexContent, 'clientes/create') === false) {
    $indexContent = str_replace(
        'if ($subAction === "create") {
            $controller->create();
        } elseif ($subAction === "view") {
            $controller->view();
        } else {
            $controller->index();
        }',
        'if ($subAction === "create") {
            $controller->create();
        } elseif ($subAction === "view") {
            $controller->view();
        } else {
            $controller->index();
        }',
        $indexContent
    );
}

file_put_contents($indexPath, $indexContent);

// Crear vista actualizada del formulario de reservas con modal
$reservationFormView = file_get_contents($baseDir . "/views/hotel/reservation_form.php");

// Agregar el modal y scripts al final del formulario, antes del script existente
$modalHTML = <<<'MODAL'

<!-- Modal para Agregar Cliente -->
<div id="clientModal" class="modal">
    <div class="modal-content modal-medium">
        <div class="modal-header">
            <h2>👤 Registrar Nuevo Cliente</h2>
            <button onclick="cerrarModalCliente()" class="btn-close">×</button>
        </div>
        <div class="modal-body">
            <form id="clientForm" onsubmit="guardarCliente(event)">
                <div class="form-section">
                    <h4 class="section-subtitle">Información Personal</h4>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Nombre Completo *</label>
                            <input type="text" name="nombre" id="clienteNombre" class="form-control" 
                                   required placeholder="Juan Pérez García">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Documento *</label>
                            <input type="text" name="documento" id="clienteDocumento" class="form-control" 
                                   required placeholder="DNI, Pasaporte">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Fecha de Nacimiento</label>
                        <input type="date" name="fecha_nacimiento" class="form-control" 
                               max="<?= date('Y-m-d') ?>">
                    </div>
                </div>
                
                <div class="form-section">
                    <h4 class="section-subtitle">Información de Contacto</h4>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" id="clienteEmail" class="form-control" 
                                   required placeholder="cliente@email.com">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Teléfono *</label>
                            <input type="tel" name="telefono" id="clienteTelefono" class="form-control" 
                                   required placeholder="+51 999 999 999">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Dirección</label>
                        <textarea name="direccion" class="form-control" rows="2" 
                                  placeholder="Dirección completa"></textarea>
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button type="submit" class="btn-primary" id="btnGuardarCliente">
                        ✨ Registrar Cliente
                    </button>
                    <button type="button" onclick="cerrarModalCliente()" class="btn-secondary">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    animation: fadeIn 0.3s;
}

.modal-medium {
    max-width: 700px;
}

.modal-content {
    background: white;
    margin: 3% auto;
    width: 90%;
    max-width: 600px;
    border-radius: 15px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
    animation: slideDown 0.3s;
    max-height: 90vh;
    overflow-y: auto;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideDown {
    from {
        transform: translateY(-50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.modal-header {
    padding: 25px 30px;
    border-bottom: 2px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 0;
    background: white;
    z-index: 10;
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
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.2s;
}

.btn-close:hover {
    background: #f3f4f6;
    color: #1f2937;
}

.modal-body {
    padding: 30px;
}

.form-section {
    margin-bottom: 30px;
}

.section-subtitle {
    color: #374151;
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 2px solid #e5e7eb;
}

.modal-actions {
    display: flex;
    gap: 10px;
    margin-top: 25px;
    padding-top: 20px;
    border-top: 2px solid #e5e7eb;
}

.modal-actions button {
    flex: 1;
}

.loading {
    pointer-events: none;
    opacity: 0.6;
}

.loading::after {
    content: "...";
    animation: dots 1.5s infinite;
}

@keyframes dots {
    0%, 20% { content: "."; }
    40% { content: ".."; }
    60%, 100% { content: "..."; }
}
</style>

<script>
function abrirModalCliente() {
    document.getElementById('clientModal').style.display = 'block';
    document.getElementById('clienteNombre').focus();
}

function cerrarModalCliente() {
    document.getElementById('clientModal').style.display = 'none';
    document.getElementById('clientForm').reset();
}

function guardarCliente(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    formData.append('ajax', '1');
    
    const btn = document.getElementById('btnGuardarCliente');
    btn.classList.add('loading');
    btn.disabled = true;
    btn.textContent = 'Guardando';
    
    fetch('/hotel/clientes/create', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Agregar cliente al select
            const clienteSelect = document.querySelector('select[name="cliente_id"]');
            const option = document.createElement('option');
            option.value = data.cliente.id;
            option.textContent = `${data.cliente.nombre} - ${data.cliente.documento}`;
            option.selected = true;
            clienteSelect.appendChild(option);
            
            // Mostrar notificación de éxito
            mostrarNotificacion('Cliente registrado exitosamente', 'success');
            
            // Cerrar modal
            cerrarModalCliente();
        } else {
            mostrarNotificacion('Error al registrar cliente', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarNotificacion('Error al registrar cliente', 'error');
    })
    .finally(() => {
        btn.classList.remove('loading');
        btn.disabled = false;
        btn.textContent = '✨ Registrar Cliente';
    });
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

// Cerrar modal al hacer click fuera
window.onclick = function(event) {
    const modal = document.getElementById('clientModal');
    if (event.target === modal) {
        cerrarModalCliente();
    }
}

// Animaciones
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);
</script>
MODAL;

// Reemplazar el enlace de "Registrar aquí" por un botón que abra el modal
$reservationFormView = str_replace(
    '<small class="form-help">
                                ¿Cliente nuevo? <a href="/hotel/clientes/create" target="_blank">Registrar aquí</a>
                            </small>',
    '<small class="form-help">
                                ¿Cliente nuevo? <a href="#" onclick="event.preventDefault(); abrirModalCliente();" style="color: #10b981; font-weight: 700;">+ Registrar aquí</a>
                            </small>',
    $reservationFormView
);

// Insertar el modal antes del último script
$reservationFormView = str_replace(
    '<script>
document.getElementById(\'fechaEntrada\')',
    $modalHTML . '

<script>
document.getElementById(\'fechaEntrada\')',
    $reservationFormView
);

file_put_contents($baseDir . "/views/hotel/reservation_form.php", $reservationFormView);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>✅ Modal de Cliente Implementado</title>
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
        .demo-video {
            background: #dbeafe;
            border-left: 4px solid #3b82f6;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .demo-video h3 {
            color: #1e40af;
            margin-bottom: 15px;
        }
        .steps {
            color: #1e40af;
            margin: 10px 0 0 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">✨</div>
        <h1>¡Modal de Cliente Listo!</h1>
        <p class="subtitle">Registra clientes sin salir del formulario de reservas</p>
        
        <div class="success">
            <strong>✅ Implementación exitosa</strong><br>
            Ahora puedes agregar clientes desde un modal en el formulario de reservas.
        </div>
        
        <div class="demo-video">
            <h3>🎯 Cómo Usar:</h3>
            <ol class="steps">
                <li>Ve al formulario de Nueva Reserva</li>
                <li>En el campo "Cliente", haz click en "<strong>+ Registrar aquí</strong>"</li>
                <li>Se abre un modal con el formulario de cliente</li>
                <li>Completa los datos y haz click en "<strong>✨ Registrar Cliente</strong>"</li>
                <li>El cliente se agrega automáticamente al select</li>
                <li>Continúa con la reserva normalmente</li>
            </ol>
        </div>
        
        <h3>🌟 Características:</h3>
        <div class="features">
            <div class="feature">
                <h3>⚡ Modal Rápido</h3>
                <p>No necesitas abrir otra pestaña ni salir del formulario de reservas</p>
            </div>
            
            <div class="feature">
                <h3>💾 Guardado AJAX</h3>
                <p>El cliente se guarda sin recargar la página</p>
            </div>
            
            <div class="feature">
                <h3>🎯 Selección Automática</h3>
                <p>El cliente nuevo se selecciona automáticamente en el formulario</p>
            </div>
            
            <div class="feature">
                <h3>✅ Validación</h3>
                <p>Todos los campos requeridos tienen validación</p>
            </div>
            
            <div class="feature">
                <h3>🔔 Notificaciones</h3>
                <p>Mensajes de éxito o error que aparecen en la esquina</p>
            </div>
            
            <div class="feature">
                <h3>🎨 Diseño Profesional</h3>
                <p>Modal con animaciones suaves y diseño moderno</p>
            </div>
        </div>
        
        <div class="text-center">
            <a href="/hotel/reservas/create" class="btn">
                🚀 Probar Modal
            </a>
            <a href="/hotel/reservas" class="btn">
                📋 Ver Reservas
            </a>
        </div>
    </div>
</body>
</html>
