<?php
/**
 * SISTEMA DE CHECK-IN PARA RESERVAS
 * Archivo: C:\xampp\htdocs\hotel-system\create_checkin_system.php
 */

$baseDir = __DIR__;
$created = [];

// 1. Actualizar vista de reservas con botón detallado de check-in
$reservationsView = <<<'EOD'
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
                                <small>⏰ <?= isset($reserva['hora_entrada']) ? date('g:i A', strtotime($reserva['hora_entrada'])) : '3:00 PM' ?>
                                    <?php if (isset($reserva['early_checkin']) && $reserva['early_checkin']): ?>
                                        <span class="badge-extra">⚡ Early</span>
                                    <?php endif; ?>
                                </small>
                            </div>
                            
                            <div class="date-item">
                                <strong>Check-out:</strong>
                                <span><?= date('d/m/Y', strtotime($reserva['fecha_salida'])) ?></span>
                                <small>⏰ <?= isset($reserva['hora_salida']) ? date('g:i A', strtotime($reserva['hora_salida'])) : '12:00 PM' ?>
                                    <?php if (isset($reserva['late_checkout']) && $reserva['late_checkout']): ?>
                                        <span class="badge-extra">⚡ Late</span>
                                    <?php endif; ?>
                                </small>
                            </div>
                        </div>
                        
                        <div class="reservation-info">
                            <div>👥 <?= $reserva['numero_huespedes'] ?> huéspedes</div>
                            <div class="reservation-price">
                                <strong>S/ <?= number_format($reserva['precio_total'], 2) ?></strong>
                                <?php if (isset($reserva['cargo_extra']) && $reserva['cargo_extra'] > 0): ?>
                                    <small>(+S/ <?= number_format($reserva['cargo_extra'], 2) ?> extras)</small>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($reserva['observaciones'])): ?>
                            <div class="reservation-notes">
                                <strong>📝 Observaciones:</strong>
                                <p><?= htmlspecialchars($reserva['observaciones']) ?></p>
                            </div>
                        <?php endif; ?>
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
                                ❌ Cancelar Reserva
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal de Check-in -->
<div id="checkinModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>✅ Realizar Check-in</h2>
            <button onclick="cerrarModal()" class="btn-close">×</button>
        </div>
        <div class="modal-body" id="checkinInfo">
            <!-- Se llenará dinámicamente -->
        </div>
    </div>
</div>

<form id="statusForm" method="POST" action="/hotel/reservas/update-status" style="display: none;">
    <input type="hidden" name="reservation_id" id="reservationId">
    <input type="hidden" name="nuevo_estado" id="nuevoEstado">
</form>

<style>
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
    transition: all 0.3s;
}

.reservation-card.status-reservada {
    border-left: 5px solid #f59e0b;
}

.reservation-card.status-ocupada {
    border-left: 5px solid #10b981;
}

.reservation-card.status-finalizada {
    border-left: 5px solid #6b7280;
    opacity: 0.8;
}

.reservation-card.status-cancelada {
    border-left: 5px solid #ef4444;
    opacity: 0.7;
}

.reservation-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
}

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

.date-item strong {
    color: #374151;
    font-size: 13px;
}

.date-item span {
    color: #1f2937;
    font-weight: 600;
}

.date-item small {
    color: #6b7280;
    font-size: 12px;
}

.reservation-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    background: #f0f9ff;
    border-radius: 8px;
    margin-bottom: 15px;
}

.reservation-price {
    text-align: right;
}

.reservation-price strong {
    color: #10b981;
    font-size: 20px;
}

.reservation-price small {
    display: block;
    color: #6b7280;
    font-size: 11px;
}

.reservation-notes {
    background: #fef3c7;
    padding: 12px;
    border-radius: 8px;
    border-left: 3px solid #f59e0b;
    margin-bottom: 15px;
}

.reservation-notes strong {
    color: #92400e;
    font-size: 13px;
}

.reservation-notes p {
    margin: 5px 0 0 0;
    color: #78350f;
    font-size: 13px;
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
    transition: all 0.2s;
    font-size: 14px;
}

.btn-checkin {
    background: #10b981;
    color: white;
}

.btn-checkin:hover {
    background: #059669;
    transform: translateY(-2px);
}

.btn-checkout {
    background: #3b82f6;
    color: white;
}

.btn-checkout:hover {
    background: #2563eb;
    transform: translateY(-2px);
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
    font-size: 13px;
}

.btn-cancel:hover {
    border-color: #ef4444;
    color: #ef4444;
}

/* Modal */
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

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.modal-content {
    background: white;
    margin: 5% auto;
    width: 90%;
    max-width: 600px;
    border-radius: 15px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
    animation: slideDown 0.3s;
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

.checkin-summary {
    background: #f9fafb;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 25px;
}

.checkin-item {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid #e5e7eb;
}

.checkin-item:last-child {
    border-bottom: none;
}

.checkin-item strong {
    color: #374151;
}

.checkin-item span {
    color: #1f2937;
    font-weight: 600;
}

.checkin-alert {
    background: #dbeafe;
    border-left: 4px solid #3b82f6;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.checkin-alert strong {
    color: #1e40af;
    display: block;
    margin-bottom: 8px;
}

.checkin-alert ul {
    margin: 0 0 0 20px;
    color: #1e40af;
}

.modal-actions {
    display: flex;
    gap: 10px;
    margin-top: 25px;
}

.btn-confirm {
    flex: 1;
    padding: 15px;
    background: #10b981;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 700;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-confirm:hover {
    background: #059669;
    transform: translateY(-2px);
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
    flex-wrap: wrap;
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
    border: 2px solid transparent;
}

.filter-btn:hover {
    background: #f3f4f6;
}

.filter-btn.active {
    background: #667eea;
    color: white;
    border-color: #667eea;
}

.badge-extra {
    background: #fef3c7;
    color: #92400e;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 700;
    margin-left: 5px;
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
    text-transform: uppercase;
}

.status-reservada { background: #fef3c7; color: #92400e; }
.status-ocupada { background: #dcfce7; color: #166534; }
.status-finalizada { background: #f3f4f6; color: #374151; }
.status-cancelada { background: #fee2e2; color: #991b1b; }
</style>

<script>
function mostrarCheckinModal(reserva) {
    const modal = document.getElementById('checkinModal');
    const info = document.getElementById('checkinInfo');
    
    const fechaEntrada = new Date(reserva.fecha_entrada);
    const fechaSalida = new Date(reserva.fecha_salida);
    const noches = Math.ceil((fechaSalida - fechaEntrada) / (1000 * 60 * 60 * 24));
    
    info.innerHTML = `
        <div class="checkin-alert">
            <strong>⚠️ Verificar antes del Check-in:</strong>
            <ul>
                <li>Solicitar documento de identidad del cliente</li>
                <li>Confirmar método de pago</li>
                <li>Entregar llaves de la habitación</li>
                <li>Explicar servicios del hotel</li>
                <li>Confirmar hora de check-out</li>
            </ul>
        </div>
        
        <div class="checkin-summary">
            <h3 style="margin-bottom: 15px; color: #1f2937;">📋 Resumen de la Reserva</h3>
            
            <div class="checkin-item">
                <strong>Cliente:</strong>
                <span>${reserva.cliente_nombre}</span>
            </div>
            <div class="checkin-item">
                <strong>Documento:</strong>
                <span>${reserva.cliente_documento}</span>
            </div>
            <div class="checkin-item">
                <strong>Habitación:</strong>
                <span>#${reserva.numero_habitacion} - ${reserva.tipo_habitacion}</span>
            </div>
            <div class="checkin-item">
                <strong>Fecha entrada:</strong>
                <span>${fechaEntrada.toLocaleDateString('es-PE')}</span>
            </div>
            <div class="checkin-item">
                <strong>Hora entrada:</strong>
                <span>${reserva.hora_entrada || '15:00:00'}</span>
            </div>
            <div class="checkin-item">
                <strong>Fecha salida:</strong>
                <span>${fechaSalida.toLocaleDateString('es-PE')}</span>
            </div>
            <div class="checkin-item">
                <strong>Hora salida:</strong>
                <span>${reserva.hora_salida || '12:00:00'}</span>
            </div>
            <div class="checkin-item">
                <strong>Noches:</strong>
                <span>${noches}</span>
            </div>
            <div class="checkin-item">
                <strong>Huéspedes:</strong>
                <span>${reserva.numero_huespedes}</span>
            </div>
            <div class="checkin-item" style="background: #f0f9ff; margin: 10px -10px 0; padding: 15px 10px; border-radius: 8px;">
                <strong style="font-size: 16px;">Total a pagar:</strong>
                <span style="font-size: 20px; color: #10b981;">S/ ${parseFloat(reserva.precio_total).toFixed(2)}</span>
            </div>
        </div>
        
        <div class="modal-actions">
            <button onclick="confirmarCheckin(${reserva.id})" class="btn-confirm">
                ✅ Confirmar Check-in
            </button>
            <button onclick="cerrarModal()" class="btn-cancel-modal">
                Cancelar
            </button>
        </div>
    `;
    
    modal.style.display = 'block';
}

function cerrarModal() {
    document.getElementById('checkinModal').style.display = 'none';
}

function confirmarCheckin(id) {
    if (confirm('¿Confirmar el check-in de esta reserva?\n\nLa habitación pasará a estado OCUPADA.')) {
        cambiarEstado(id, 'ocupada');
    }
}

function cambiarEstado(id, estado) {
    const mensajes = {
        "ocupada": "El check-in se realizará ahora.",
        "finalizada": "¿Realizar el check-out?\n\nLa habitación pasará a estado de LIMPIEZA.",
        "cancelada": "¿Estás seguro de CANCELAR esta reserva?\n\nEsta acción no se puede deshacer."
    };
    
    if (estado === 'ocupada' || confirm(mensajes[estado])) {
        document.getElementById("reservationId").value = id;
        document.getElementById("nuevoEstado").value = estado;
        document.getElementById("statusForm").submit();
    }
}

// Cerrar modal al hacer click fuera
window.onclick = function(event) {
    const modal = document.getElementById('checkinModal');
    if (event.target == modal) {
        cerrarModal();
    }
}
</script>

<?php include BASE_PATH . "/views/hotel/_footer.php"; ?>
EOD;

file_put_contents($baseDir . "/views/hotel/reservations.php", $reservationsView);
$created[] = "reservations.php - Sistema de check-in mejorado";

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>✅ Sistema de Check-in Implementado</title>
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
        .process {
            background: #dbeafe;
            border-left: 4px solid #3b82f6;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .process h3 {
            color: #1e40af;
            margin-bottom: 15px;
        }
        .process-steps {
            display: grid;
            gap: 10px;
        }
        .step {
            background: white;
            padding: 15px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .step-number {
            width: 35px;
            height: 35px;
            background: #3b82f6;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            flex-shrink: 0;
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
        <div class="icon">✅</div>
        <h1>¡Sistema de Check-in Listo!</h1>
        <p class="subtitle">Gestión profesional de llegadas al hotel</p>
        
        <div class="success">
            <strong>✅ Sistema implementado correctamente</strong><br>
            Ahora puedes realizar check-in de forma profesional y organizada.
        </div>
        
        <div class="process">
            <h3>📋 Proceso de Check-in:</h3>
            <div class="process-steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <div>
                        <strong>Cliente llega al hotel</strong>
                        <p style="margin: 0; color: #6b7280; font-size: 13px;">Con o sin reserva previa</p>
                    </div>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <div>
                        <strong>Buscar reserva</strong>
                        <p style="margin: 0; color: #6b7280; font-size: 13px;">En la lista de reservas con estado "Reservada"</p>
                    </div>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <div>
                        <strong>Click en "Realizar Check-in"</strong>
                        <p style="margin: 0; color: #6b7280; font-size: 13px;">Se abre modal con información detallada</p>
                    </div>
                </div>
                <div class="step">
                    <div class="step-number">4</div>
                    <div>
                        <strong>Verificar información</strong>
                        <p style="margin: 0; color: #6b7280; font-size: 13px;">Documento, habitación, fechas, precios</p>
                    </div>
                </div>
                <div class="step">
                    <div class="step-number">5</div>
                    <div>
                        <strong>Confirmar check-in</strong>
                        <p style="margin: 0; color: #6b7280; font-size: 13px;">Reserva cambia a "Ocupada" y habitación se marca como ocupada</p>
                    </div>
                </div>
            </div>
        </div>
        
        <h3>🌟 Funcionalidades Implementadas:</h3>
        <div class="features">
            <div class="feature">
                <h3>📋 Vista en Tarjetas</h3>
                <p>Cada reserva se muestra en una tarjeta visual con toda la información importante</p>
            </div>
            
            <div class="feature">
                <h3>✅ Botón de Check-in</h3>
                <p>Botón destacado para realizar check-in de reservas confirmadas</p>
            </div>
            
            <div class="feature">
                <h3>💬 Modal Informativo</h3>
                <p>Ventana emergente con resumen completo antes de confirmar</p>
            </div>
            
            <div class="feature">
                <h3>✓ Lista de Verificación</h3>
                <p>Checklist de tareas a realizar durante el check-in</p>
            </div>
            
            <div class="feature">
                <h3>🏁 Check-out Rápido</h3>
                <p>Botón para realizar check-out cuando el huésped sale</p>
            </div>
            
            <div class="feature">
                <h3>🎨 Estados Visuales</h3>
                <p>Códigos de color para identificar rápidamente el estado de cada reserva</p>
            </div>
            
            <div class="feature">
                <h3>📱 Información Completa</h3>
                <p>Datos del cliente, habitación, fechas, horarios y precios en un solo lugar</p>
            </div>
        </div>
        
        <div class="success" style="background: #dbeafe; border-color: #3b82f6;">
            <strong style="color: #1e40af;">💡 Cómo Usar:</strong>
            <ol style="margin: 10px 0 0 20px; color: #1e40af;">
                <li>Ve a <strong>Reservas</strong> en el menú</li>
                <li>Busca la reserva del cliente que acaba de llegar</li>
                <li>Haz click en <strong>"✅ Realizar Check-in"</strong></li>
                <li>Verifica toda la información en el modal</li>
                <li>Confirma el check-in</li>
                <li>¡La habitación automáticamente pasa a estado OCUPADA!</li>
            </ol>
        </div>
        
        <div class="text-center">
            <a href="/hotel/reservas" class="btn">
                📋 Ver Reservas
            </a>
            <a href="/hotel/dashboard" class="btn">
                📊 Ir al Dashboard
            </a>
        </div>
    </div>
</body>
</html>
