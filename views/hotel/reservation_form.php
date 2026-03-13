<?php 
// Definir configuración por defecto si no existe
if (!isset($config)) {
    $config = [
        'hora_checkin_estandar' => '15:00:00',
        'hora_checkout_estandar' => '12:00:00',
        'hora_early_checkin' => '11:00:00',
        'hora_late_checkout' => '18:00:00',
        'cargo_early_checkin' => 50.00,
        'cargo_late_checkout' => 50.00
    ];
}

// Asegurar que $clientes existe
if (!isset($clientes)) {
    $clientes = [];
}

// Asegurar que $habitaciones existe
if (!isset($habitaciones)) {
    $habitaciones = [];
}

include BASE_PATH . "/views/hotel/_header.php"; 
?>

<div class="page-header">
    <div>
        <h1>📅 Nueva Reserva</h1>
        <p class="subtitle">Selecciona las fechas para ver habitaciones disponibles</p>
    </div>
    <a href="<?= BASE_URL ?>/hotel/reservas" class="btn-secondary">← Volver</a>
</div>

<div class="card">
    <div class="form-container">
        
        <!-- PASO 1: Seleccionar Fechas -->
        <div class="step-section <?= empty($habitaciones) && (!isset($_GET['fecha_entrada'])) ? 'active' : 'completed' ?>">
            <div class="step-header">
                <span class="step-number">1</span>
                <h3>Selecciona las Fechas</h3>
            </div>
            
            <form method="GET" action="" id="searchDatesForm">
                <input type="hidden" name="numero_huespedes" value="1" id="numHuespedesHidden">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Fecha de Entrada *</label>
                        <input type="date" name="fecha_entrada" id="fechaEntrada" class="form-control" 
                               required min="<?= date('Y-m-d') ?>" 
                               value="<?= htmlspecialchars($_GET['fecha_entrada'] ?? '') ?>">
                        <small class="form-help">
                            ⏰ Check-in estándar: 3:00 PM
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Fecha de Salida *</label>
                        <input type="date" name="fecha_salida" id="fechaSalida" class="form-control" 
                               required value="<?= htmlspecialchars($_GET['fecha_salida'] ?? '') ?>">
                        <small class="form-help">
                            ⏰ Check-out estándar: 12:00 PM
                        </small>
                    </div>
                </div>
                
                <button type="submit" class="btn-primary">
                    🔍 Buscar Habitaciones Disponibles
                </button>
            </form>
        </div>
        
        <!-- PASO 2: Mostrar Resultados -->
        <?php if (isset($_GET['fecha_entrada']) && isset($_GET['fecha_salida'])): ?>
        <div class="step-section active" style="margin-top: 30px;">
            <div class="step-header">
                <span class="step-number">2</span>
                <h3>Habitaciones Disponibles</h3>
            </div>
            
            <?php if (empty($habitaciones)): ?>
                <div class="alert alert-warning">
                    ⚠️ No hay habitaciones disponibles para las fechas seleccionadas. 
                    <a href="<?= BASE_URL ?>/hotel/reservas/create">Intenta con otras fechas</a>
                </div>
            <?php else: ?>
                <div class="info-box">
                    <strong>📋 Información de Horarios:</strong>
                    <div style="margin-top: 10px; display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                        <div><strong>✅ Check-in Estándar:</strong> 3:00 PM (Incluido)</div>
                        <div><strong>✅ Check-out Estándar:</strong> 12:00 PM (Incluido)</div>
                        <div><strong>⚡ Early Check-in:</strong> 11:00 AM (+S/ 50.00)</div>
                        <div><strong>⚡ Late Check-out:</strong> 6:00 PM (+S/ 50.00)</div>
                    </div>
                </div>
                
                <!-- Grid de Habitaciones -->
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
                                <?php if (!empty($hab['descripcion'])): ?>
                                    <div class="room-option-desc"><?= htmlspecialchars($hab['descripcion']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- PASO 3: Formulario de Reserva -->
                <div class="step-section active" style="margin-top: 30px;">
                    <div class="step-header">
                        <span class="step-number">3</span>
                        <h3>Completar Reserva</h3>
                    </div>
                    
                    <form method="POST" action="<?= BASE_URL ?>/hotel/reservas/create">
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
                                ¿Cliente nuevo? <a href="#" onclick="event.preventDefault(); abrirModalCliente();" style="color: #10b981; font-weight: 700;">+ Registrar aquí</a>
                            </small>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Habitación *</label>
                                <select name="habitacion_id" class="form-control" id="habitacionSelect" required>
                                    <option value="">Seleccionar habitación</option>
                                    <?php foreach ($habitaciones as $hab): ?>
                                        <option value="<?= $hab['id'] ?>" data-precio="<?= $hab['precio_noche'] ?>">
                                            #<?= $hab['numero_habitacion'] ?> - <?= $hab['tipo'] ?> 
                                            (S/ <?= number_format($hab['precio_noche'], 2) ?>/noche)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Número de Huéspedes *</label>
                                <input type="number" name="numero_huespedes" class="form-control" min="1" value="1" required>
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
                                        <div class="checkbox-desc">Ingreso desde 11:00 AM</div>
                                        <div class="checkbox-price">+S/ 50.00</div>
                                    </div>
                                </label>
                                
                                <label class="checkbox-card">
                                    <input type="checkbox" name="late_checkout" id="lateCheckout" value="1">
                                    <div class="checkbox-content">
                                        <div class="checkbox-title">⚡ Late Check-out</div>
                                        <div class="checkbox-desc">Salida hasta 6:00 PM</div>
                                        <div class="checkbox-price">+S/ 50.00</div>
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
                                    $entrada = new DateTime($_GET['fecha_entrada']);
                                    $salida = new DateTime($_GET['fecha_salida']);
                                    echo $entrada->diff($salida)->days;
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
                            <a href="<?= BASE_URL ?>/hotel/reservas" class="btn-secondary">Cancelar</a>
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
.price-row:last-child { border-bottom: none; }
.price-total {
    font-size: 18px;
    color: #667eea;
    margin-top: 10px;
    padding-top: 15px;
    border-top: 2px solid #667eea !important;
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
}
.btn-primary {
    background: #667eea;
    color: white;
}
.btn-primary:hover { background: #5568d3; }
.btn-secondary {
    background: #f3f4f6;
    color: #374151;
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
</style>


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

<script>
// Sincronizar número de huéspedes con el campo oculto
function syncNumHuespedes() {
    const numHuespedesInput = document.querySelector('input[name="numero_huespedes"]');
    const hiddenInput = document.getElementById('numHuespedesHidden');
    if (numHuespedesInput && hiddenInput) {
        hiddenInput.value = numHuespedesInput.value;
    }
}

// Agregar listener al formulario de búsqueda
const searchForm = document.getElementById('searchDatesForm');
if (searchForm) {
    searchForm.addEventListener('submit', function(e) {
        syncNumHuespedes();
    });
}

document.getElementById('fechaEntrada').addEventListener('change', function() {
    document.getElementById('fechaSalida').min = this.value;
});

const habitacionSelect = document.getElementById('habitacionSelect');
const earlyCheckin = document.getElementById('earlyCheckin');
const lateCheckout = document.getElementById('lateCheckout');

function calcularTotal() {
    const option = habitacionSelect.selectedOptions[0];
    const precio = parseFloat(option?.dataset.precio || 0);
    const noches = parseInt(document.getElementById('numeroNoches').textContent);
    
    const subtotal = precio * noches;
    let cargoExtra = 0;
    
    if (earlyCheckin.checked) cargoExtra += 50;
    if (lateCheckout.checked) cargoExtra += 50;
    
    const total = subtotal + cargoExtra;
    
    document.getElementById('precioNoche').textContent = 'S/ ' + precio.toFixed(2);
    document.getElementById('subtotal').textContent = 'S/ ' + subtotal.toFixed(2);
    document.getElementById('cargoExtra').textContent = 'S/ ' + cargoExtra.toFixed(2);
    document.getElementById('precioTotal').textContent = 'S/ ' + total.toFixed(2);
    
    document.getElementById('cargoExtraRow').style.display = cargoExtra > 0 ? 'flex' : 'none';
}

if (habitacionSelect) {
    habitacionSelect.addEventListener('change', calcularTotal);
    earlyCheckin.addEventListener('change', calcularTotal);
    lateCheckout.addEventListener('change', calcularTotal);
}

document.querySelectorAll('.room-option').forEach(room => {
    room.addEventListener('click', function() {
        const roomId = this.dataset.roomId;
        habitacionSelect.value = roomId;
        habitacionSelect.dispatchEvent(new Event('change'));
        
        document.querySelectorAll('.room-option').forEach(r => {
            r.style.borderColor = '#e5e7eb';
            r.style.background = 'white';
        });
        this.style.borderColor = '#667eea';
        this.style.background = '#f0f9ff';
    });
});
</script>

<?php include BASE_PATH . "/views/hotel/_footer.php"; ?>
