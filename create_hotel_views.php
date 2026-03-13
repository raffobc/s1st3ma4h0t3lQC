<?php
/**
 * FORMULARIOS Y VISTAS COMPLETAS DEL SISTEMA HOTEL
 * Archivo: C:\xampp\htdocs\hotel-system\create_hotel_views.php
 * Ejecuta este archivo para completar todas las vistas
 */

$baseDir = __DIR__;
$created = [];

$viewFiles = [
    // views/hotel/room_form.php
    'views/hotel/room_form.php' => '<?php include BASE_PATH . "/views/hotel/_header.php"; ?>

<div class="page-header">
    <div>
        <h1><?= isset($room) ? "✏️ Editar Habitación" : "➕ Nueva Habitación" ?></h1>
        <p class="subtitle">Completa los datos de la habitación</p>
    </div>
    <a href="/hotel/habitaciones" class="btn-secondary">← Volver</a>
</div>

<div class="card">
    <form method="POST" class="form-container">
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Número de Habitación *</label>
                <input type="text" name="numero_habitacion" class="form-control" 
                       value="<?= $room["numero_habitacion"] ?? "" ?>" required placeholder="Ej: 101">
            </div>
            
            <div class="form-group">
                <label class="form-label">Tipo *</label>
                <select name="tipo" class="form-control" required>
                    <option value="">Seleccionar tipo</option>
                    <option value="Simple" <?= ($room["tipo"] ?? "") === "Simple" ? "selected" : "" ?>>Simple</option>
                    <option value="Doble" <?= ($room["tipo"] ?? "") === "Doble" ? "selected" : "" ?>>Doble</option>
                    <option value="Matrimonial" <?= ($room["tipo"] ?? "") === "Matrimonial" ? "selected" : "" ?>>Matrimonial</option>
                    <option value="Suite" <?= ($room["tipo"] ?? "") === "Suite" ? "selected" : "" ?>>Suite</option>
                    <option value="Suite Presidencial" <?= ($room["tipo"] ?? "") === "Suite Presidencial" ? "selected" : "" ?>>Suite Presidencial</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Precio por Noche (S/) *</label>
                <input type="number" name="precio_noche" class="form-control" step="0.01" 
                       value="<?= $room["precio_noche"] ?? "" ?>" required placeholder="150.00">
            </div>
            
            <div class="form-group">
                <label class="form-label">Capacidad (personas) *</label>
                <input type="number" name="capacidad" class="form-control" 
                       value="<?= $room["capacidad"] ?? "" ?>" required placeholder="2">
            </div>
            
            <?php if (isset($room)): ?>
            <div class="form-group">
                <label class="form-label">Estado *</label>
                <select name="estado" class="form-control" required>
                    <option value="disponible" <?= $room["estado"] === "disponible" ? "selected" : "" ?>>Disponible</option>
                    <option value="ocupada" <?= $room["estado"] === "ocupada" ? "selected" : "" ?>>Ocupada</option>
                    <option value="reservada" <?= $room["estado"] === "reservada" ? "selected" : "" ?>>Reservada</option>
                    <option value="limpieza" <?= $room["estado"] === "limpieza" ? "selected" : "" ?>>En Limpieza</option>
                    <option value="mantenimiento" <?= $room["estado"] === "mantenimiento" ? "selected" : "" ?>>En Mantenimiento</option>
                </select>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="form-group">
            <label class="form-label">Descripción</label>
            <textarea name="descripcion" class="form-control" rows="4" 
                      placeholder="Características, amenidades, etc."><?= $room["descripcion"] ?? "" ?></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn-primary">
                <?= isset($room) ? "💾 Actualizar Habitación" : "✨ Crear Habitación" ?>
            </button>
            <a href="/hotel/habitaciones" class="btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php include BASE_PATH . "/views/hotel/_footer.php"; ?>',

    // views/hotel/reservation_form.php
    'views/hotel/reservation_form.php' => '<?php include BASE_PATH . "/views/hotel/_header.php"; ?>

<div class="page-header">
    <div>
        <h1>📅 Nueva Reserva</h1>
        <p class="subtitle">Registra una nueva reserva</p>
    </div>
    <a href="/hotel/reservas" class="btn-secondary">← Volver</a>
</div>

<div class="card">
    <form method="POST" class="form-container" id="reservationForm">
        <div class="form-section">
            <h3 class="section-title">👤 Información del Cliente</h3>
            
            <div class="form-group">
                <label class="form-label">Cliente *</label>
                <select name="cliente_id" class="form-control" required>
                    <option value="">Seleccionar cliente</option>
                    <?php foreach ($clientes as $cliente): ?>
                        <option value="<?= $cliente["id"] ?>">
                            <?= htmlspecialchars($cliente["nombre"]) ?> - <?= htmlspecialchars($cliente["documento"]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="form-help">
                    ¿Cliente nuevo? <a href="/hotel/clientes/create" target="_blank">Registrar aquí</a>
                </small>
            </div>
        </div>
        
        <div class="form-section">
            <h3 class="section-title">🏨 Información de la Reserva</h3>
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Habitación *</label>
                    <select name="habitacion_id" class="form-control" id="habitacion" required>
                        <option value="">Seleccionar habitación</option>
                        <?php foreach ($habitaciones as $hab): ?>
                            <option value="<?= $hab["id"] ?>" 
                                    data-precio="<?= $hab["precio_noche"] ?>"
                                    data-tipo="<?= $hab["tipo"] ?>">
                                #<?= $hab["numero_habitacion"] ?> - <?= $hab["tipo"] ?> 
                                (S/ <?= number_format($hab["precio_noche"], 2) ?>/noche)
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
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Fecha de Entrada *</label>
                    <input type="date" name="fecha_entrada" class="form-control" 
                           id="fechaEntrada" required min="<?= date("Y-m-d") ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Fecha de Salida *</label>
                    <input type="date" name="fecha_salida" class="form-control" 
                           id="fechaSalida" required>
                </div>
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
                <div class="price-row price-total">
                    <span>Total estimado:</span>
                    <strong id="precioTotal">S/ 0.00</strong>
                </div>
            </div>
        </div>
        
        <div class="form-section">
            <h3 class="section-title">📝 Información Adicional</h3>
            
            <div class="form-group">
                <label class="form-label">Observaciones</label>
                <textarea name="observaciones" class="form-control" rows="4" 
                          placeholder="Peticiones especiales, notas importantes, etc."></textarea>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn-primary">✨ Crear Reserva</button>
            <a href="/hotel/reservas" class="btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<script>
    const habitacionSelect = document.getElementById("habitacion");
    const fechaEntrada = document.getElementById("fechaEntrada");
    const fechaSalida = document.getElementById("fechaSalida");
    const precioNocheEl = document.getElementById("precioNoche");
    const numeroNochesEl = document.getElementById("numeroNoches");
    const precioTotalEl = document.getElementById("precioTotal");
    
    function calcularTotal() {
        const habitacion = habitacionSelect.selectedOptions[0];
        const precio = parseFloat(habitacion?.dataset.precio || 0);
        
        const entrada = new Date(fechaEntrada.value);
        const salida = new Date(fechaSalida.value);
        
        if (entrada && salida && salida > entrada) {
            const diff = Math.ceil((salida - entrada) / (1000 * 60 * 60 * 24));
            const total = precio * diff;
            
            precioNocheEl.textContent = `S/ ${precio.toFixed(2)}`;
            numeroNochesEl.textContent = diff;
            precioTotalEl.textContent = `S/ ${total.toFixed(2)}`;
        } else {
            precioNocheEl.textContent = `S/ ${precio.toFixed(2)}`;
            numeroNochesEl.textContent = "0";
            precioTotalEl.textContent = "S/ 0.00";
        }
    }
    
    habitacionSelect.addEventListener("change", calcularTotal);
    fechaEntrada.addEventListener("change", function() {
        fechaSalida.min = this.value;
        calcularTotal();
    });
    fechaSalida.addEventListener("change", calcularTotal);
</script>

<?php include BASE_PATH . "/views/hotel/_footer.php"; ?>',

    // views/hotel/reservations.php
    'views/hotel/reservations.php' => '<?php include BASE_PATH . "/views/hotel/_header.php"; ?>

<div class="page-header">
    <div>
        <h1>📅 Reservas</h1>
        <p class="subtitle">Gestiona las reservas del hotel</p>
    </div>
    <a href="/hotel/reservas/create" class="btn-primary">+ Nueva Reserva</a>
</div>

<div class="card">
    <div class="filters-bar">
        <a href="?filter=all" class="filter-btn <?= ($filter ?? "all") === "all" ? "active" : "" ?>">
            📋 Todas
        </a>
        <a href="?filter=active" class="filter-btn <?= ($filter ?? "") === "active" ? "active" : "" ?>">
            ✅ Activas
        </a>
        <a href="?filter=finished" class="filter-btn <?= ($filter ?? "") === "finished" ? "active" : "" ?>">
            ✔️ Finalizadas
        </a>
        <a href="?filter=cancelled" class="filter-btn <?= ($filter ?? "") === "cancelled" ? "active" : "" ?>">
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
                        <th>Documento</th>
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
                            <td><strong>#<?= $reserva["id"] ?></strong></td>
                            <td><?= htmlspecialchars($reserva["cliente_nombre"]) ?></td>
                            <td><?= htmlspecialchars($reserva["cliente_documento"]) ?></td>
                            <td>
                                <span class="room-badge">
                                    🏠 <?= htmlspecialchars($reserva["numero_habitacion"]) ?>
                                </span>
                            </td>
                            <td><?= date("d/m/Y", strtotime($reserva["fecha_entrada"])) ?></td>
                            <td><?= date("d/m/Y", strtotime($reserva["fecha_salida"])) ?></td>
                            <td><?= $reserva["numero_huespedes"] ?> 👥</td>
                            <td><strong>S/ <?= number_format($reserva["precio_total"], 2) ?></strong></td>
                            <td>
                                <span class="status-badge status-<?= $reserva["estado"] ?>">
                                    <?= ucfirst($reserva["estado"]) ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <?php if ($reserva["estado"] === "reservada"): ?>
                                        <button onclick="cambiarEstado(<?= $reserva["id"] ?>, \'ocupada\')" 
                                                class="btn-action btn-success" title="Marcar como Ocupada">
                                            ✓
                                        </button>
                                    <?php endif; ?>
                                    
                                    <?php if ($reserva["estado"] === "ocupada"): ?>
                                        <button onclick="cambiarEstado(<?= $reserva["id"] ?>, \'finalizada\')" 
                                                class="btn-action btn-primary" title="Finalizar">
                                            🏁
                                        </button>
                                    <?php endif; ?>
                                    
                                    <?php if (in_array($reserva["estado"], ["reservada", "ocupada"])): ?>
                                        <button onclick="cambiarEstado(<?= $reserva["id"] ?>, \'cancelada\')" 
                                                class="btn-action btn-danger" title="Cancelar">
                                            ❌
                                        </button>
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
            "ocupada": "¿Marcar esta reserva como OCUPADA (Check-in)?",
            "finalizada": "¿FINALIZAR esta reserva (Check-out)?",
            "cancelada": "¿Estás seguro de CANCELAR esta reserva?"
        };
        
        if (confirm(mensajes[estado])) {
            document.getElementById("reservationId").value = id;
            document.getElementById("nuevoEstado").value = estado;
            document.getElementById("statusForm").submit();
        }
    }
</script>

<?php include BASE_PATH . "/views/hotel/_footer.php"; ?>',

    // views/hotel/client_form.php
    'views/hotel/client_form.php' => '<?php include BASE_PATH . "/views/hotel/_header.php"; ?>

<div class="page-header">
    <div>
        <h1>👤 Nuevo Cliente</h1>
        <p class="subtitle">Registra un nuevo cliente</p>
    </div>
    <a href="/hotel/clientes" class="btn-secondary">← Volver</a>
</div>

<div class="card">
    <form method="POST" class="form-container">
        <div class="form-section">
            <h3 class="section-title">Información Personal</h3>
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Nombre Completo *</label>
                    <input type="text" name="nombre" class="form-control" required 
                           placeholder="Juan Pérez García">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Documento de Identidad *</label>
                    <input type="text" name="documento" class="form-control" required 
                           placeholder="DNI, Pasaporte, etc.">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Fecha de Nacimiento</label>
                <input type="date" name="fecha_nacimiento" class="form-control" 
                       max="<?= date("Y-m-d") ?>">
            </div>
        </div>
        
        <div class="form-section">
            <h3 class="section-title">Información de Contacto</h3>
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" class="form-control" required 
                           placeholder="cliente@email.com">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Teléfono *</label>
                    <input type="tel" name="telefono" class="form-control" required 
                           placeholder="+51 999 999 999">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Dirección</label>
                <textarea name="direccion" class="form-control" rows="3" 
                          placeholder="Dirección completa del cliente"></textarea>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn-primary">✨ Registrar Cliente</button>
            <a href="/hotel/clientes" class="btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php include BASE_PATH . "/views/hotel/_footer.php"; ?>',

    // views/hotel/clients.php
    'views/hotel/clients.php' => '<?php include BASE_PATH . "/views/hotel/_header.php"; ?>

<div class="page-header">
    <div>
        <h1>👥 Clientes</h1>
        <p class="subtitle">Gestiona la base de datos de clientes</p>
    </div>
    <a href="/hotel/clientes/create" class="btn-primary">+ Nuevo Cliente</a>
</div>

<div class="card">
    <div class="search-bar">
        <form method="GET" action="">
            <div class="search-input-group">
                <input type="text" name="search" class="search-input" 
                       placeholder="🔍 Buscar por nombre, documento o email..." 
                       value="<?= htmlspecialchars($_GET["search"] ?? "") ?>">
                <button type="submit" class="btn-search">Buscar</button>
            </div>
        </form>
    </div>
    
    <?php if (empty($clients)): ?>
        <div class="empty-state">
            <div class="empty-icon">👥</div>
            <h3>No se encontraron clientes</h3>
            <p><?= isset($_GET["search"]) ? "Intenta con otro término de búsqueda" : "Comienza registrando un nuevo cliente" ?></p>
            <?php if (!isset($_GET["search"])): ?>
                <a href="/hotel/clientes/create" class="btn-primary" style="margin-top: 20px;">
                    + Nuevo Cliente
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Documento</th>
                        <th>Email</th>
                        <th>Teléfono</th>
                        <th>Total Reservas</th>
                        <th>Total Gastado</th>
                        <th>Registro</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clients as $client): ?>
                        <tr>
                            <td><strong>#<?= $client["id"] ?></strong></td>
                            <td><?= htmlspecialchars($client["nombre"]) ?></td>
                            <td><?= htmlspecialchars($client["documento"]) ?></td>
                            <td><?= htmlspecialchars($client["email"]) ?></td>
                            <td><?= htmlspecialchars($client["telefono"]) ?></td>
                            <td>
                                <span class="badge-count"><?= $client["total_reservas"] ?> 📅</span>
                            </td>
                            <td><strong>S/ <?= number_format($client["total_gastado"], 2) ?></strong></td>
                            <td><?= date("d/m/Y", strtotime($client["created_at"])) ?></td>
                            <td>
                                <a href="/hotel/clientes/view?id=<?= $client["id"] ?>" 
                                   class="btn-action btn-primary" title="Ver Detalles">
                                    👁️
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include BASE_PATH . "/views/hotel/_footer.php"; ?>',

    // views/hotel/client_detail.php
    'views/hotel/client_detail.php' => '<?php include BASE_PATH . "/views/hotel/_header.php"; ?>

<div class="page-header">
    <div>
        <h1>👤 <?= htmlspecialchars($client["nombre"]) ?></h1>
        <p class="subtitle">Perfil completo del cliente</p>
    </div>
    <a href="/hotel/clientes" class="btn-secondary">← Volver</a>
</div>

<div class="content-grid">
    <div>
        <div class="card" style="margin-bottom: 20px;">
            <h3 class="card-title">📋 Información Personal</h3>
            
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Documento</div>
                    <div class="info-value"><?= htmlspecialchars($client["documento"]) ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Email</div>
                    <div class="info-value"><?= htmlspecialchars($client["email"]) ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Teléfono</div>
                    <div class="info-value"><?= htmlspecialchars($client["telefono"]) ?></div>
                </div>
                
                <?php if ($client["fecha_nacimiento"]): ?>
                <div class="info-item">
                    <div class="info-label">Fecha de Nacimiento</div>
                    <div class="info-value"><?= date("d/m/Y", strtotime($client["fecha_nacimiento"])) ?></div>
                </div>
                <?php endif; ?>
                
                <?php if ($client["direccion"]): ?>
                <div class="info-item full-width">
                    <div class="info-label">Dirección</div>
                    <div class="info-value"><?= htmlspecialchars($client["direccion"]) ?></div>
                </div>
                <?php endif; ?>
                
                <div class="info-item">
                    <div class="info-label">Fecha de Registro</div>
                    <div class="info-value"><?= date("d/m/Y H:i", strtotime($client["created_at"])) ?></div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <h3 class="card-title">📅 Historial de Reservas</h3>
            
            <?php if (empty($reservations)): ?>
                <div class="empty-state-small">
                    <p>Este cliente no tiene reservas registradas</p>
                </div>
            <?php else: ?>
                <div class="timeline">
                    <?php foreach ($reservations as $reserva): ?>
                        <div class="timeline-item">
                            <div class="timeline-marker status-<?= $reserva["estado"] ?>"></div>
                            <div class="timeline-content">
                                <div class="timeline-header">
                                    <span class="room-badge">🏠 <?= htmlspecialchars($reserva["numero_habitacion"]) ?></span>
                                    <span class="status-badge status-<?= $reserva["estado"] ?>">
                                        <?= ucfirst($reserva["estado"]) ?>
                                    </span>
                                </div>
                                <div class="timeline-body">
                                    <div class="timeline-dates">
                                        📅 <?= date("d/m/Y", strtotime($reserva["fecha_entrada"])) ?> 
                                        → <?= date("d/m/Y", strtotime($reserva["fecha_salida"])) ?>
                                    </div>
                                    <div class="timeline-price">
                                        💰 S/ <?= number_format($reserva["precio_total"], 2) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div>
        <div class="card">
            <h3 class="card-title">📊 Estadísticas</h3>
            
            <div class="stats-vertical">
                <div class="stat-item">
                    <div class="stat-icon">📅</div>
                    <div class="stat-info">
                        <div class="stat-value"><?= count($reservations) ?></div>
                        <div class="stat-label">Total Reservas</div>
                    </div>
                </div>
                
                <div class="stat-item">
                    <div class="stat-icon">💰</div>
                    <div class="stat-info">
                        <div class="stat-value">
                            S/ <?php 
                                $total = array_sum(array_column($reservations, "precio_total"));
                                echo number_format($total, 2);
                            ?>
                        </div>
                        <div class="stat-label">Total Gastado</div>
                    </div>
                </div>
                
                <div class="stat-item">
                    <div class="stat-icon">✅</div>
                    <div class="stat-info">
                        <div class="stat-value">
                            <?= count(array_filter($reservations, fn($r) => $r["estado"] === "finalizada")) ?>
                        </div>
                        <div class="stat-label">Completadas</div>
                    </div>
                </div>
                
                <div class="stat-item">
                    <div class="stat-icon">🔄</div>
                    <div class="stat-info">
                        <div class="stat-value">
                            <?= count(array_filter($reservations, fn($r) => in_array($r["estado"], ["reservada", "ocupada"]))) ?>
                        </div>
                        <div class="stat-label">Activas</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include BASE_PATH . "/views/hotel/_footer.php"; ?>',

    // Actualizar CSS
    'public/css/hotel-admin-extended.css' => '/* FORMULARIOS */
.form-container {
    padding: 30px;
}

.form-section {
    margin-bottom: 40px;
}

.section-title {
    font-size: 18px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #e5e7eb;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.form-group {
    margin-bottom: 20px;
}

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

.form-actions {
    display: flex;
    gap: 15px;
    margin-top: 30px;
    padding-top: 30px;
    border-top: 2px solid #e5e7eb;
}

.btn-secondary {
    padding: 12px 24px;
    background: #f3f4f6;
    color: #374151;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    text-decoration: none;
    font-size: 14px;
    display: inline-block;
    transition: all 0.2s;
}

.btn-secondary:hover {
    background: #e5e7eb;
}

/* PRICE SUMMARY */
.price-summary {
    background: #f9fafb;
    border-radius: 10px;
    padding: 20px;
    margin-top: 20px;
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

/* FILTERS */
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
    border: 2px solid transparent;
}

.filter-btn:hover {
    background: #f3f4f6;
    color: #667eea;
}

.filter-btn.active {
    background: #667eea;
    color: white;
    border-color: #667eea;
}

/* SEARCH BAR */
.search-bar {
    padding: 20px;
    background: #f9fafb;
    border-bottom: 2px solid #e5e7eb;
}

.search-input-group {
    display: flex;
    gap: 10px;
}

.search-input {
    flex: 1;
    padding: 12px 16px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 15px;
}

.search-input:focus {
    outline: none;
    border-color: #667eea;
}

.btn-search {
    padding: 12px 24px;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
}

/* TABLE */
.table-responsive {
    overflow-x: auto;
    padding: 20px;
}

.table {
    width: 100%;
    border-collapse: collapse;
}

.table th {
    background: #f9fafb;
    padding: 15px 12px;
    text-align: left;
    font-weight: 600;
    color: #374151;
    font-size: 13px;
    border-bottom: 2px solid #e5e7eb;
    white-space: nowrap;
}

.table td {
    padding: 15px 12px;
    border-bottom: 1px solid #e5e7eb;
    font-size: 14px;
}

.table tbody tr:hover {
    background: #f9fafb;
}

.room-badge {
    background: #dbeafe;
    color: #1e40af;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
}

.badge-count {
    background: #f3f4f6;
    color: #374151;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
}

/* ACTION BUTTONS */
.action-buttons {
    display: flex;
    gap: 5px;
}

.btn-action {
    padding: 6px 12px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.2s;
}

.btn-action.btn-success {
    background: #dcfce7;
    color: #166534;
}

.btn-action.btn-success:hover {
    background: #bbf7d0;
}

.btn-action.btn-danger {
    background: #fee2e2;
    color: #991b1b;
}

.btn-action.btn-danger:hover {
    background: #fecaca;
}

.btn-action.btn-primary {
    background: #dbeafe;
    color: #1e40af;
}

.btn-action.btn-primary:hover {
    background: #bfdbfe;
}

/* EMPTY STATE */
.empty-state {
    text-align: center;
    padding: 60px 20px;
}

.empty-icon {
    font-size: 64px;
    margin-bottom: 20px;
}

.empty-state h3 {
    font-size: 24px;
    color: #1f2937;
    margin-bottom: 10px;
}

.empty-state p {
    color: #6b7280;
    font-size: 16px;
}

.empty-state-small {
    text-align: center;
    padding: 40px 20px;
    color: #6b7280;
}

/* CLIENT DETAIL */
.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.info-item {
    padding: 15px;
    background: #f9fafb;
    border-radius: 8px;
}

.info-item.full-width {
    grid-column: 1 / -1;
}

.info-label {
    font-size: 12px;
    color: #6b7280;
    font-weight: 600;
    margin-bottom: 5px;
    text-transform: uppercase;
}

.info-value {
    font-size: 16px;
    color: #1f2937;
    font-weight: 600;
}

/* TIMELINE */
.timeline {
    position: relative;
    padding: 20px 0;
}

.timeline::before {
    content: "";
    position: absolute;
    left: 20px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e5e7eb;
}

.timeline-item {
    position: relative;
    padding-left: 50px;
    margin-bottom: 30px;
}

.timeline-marker {
    position: absolute;
    left: 12px;
    top: 5px;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    border: 3px solid white;
    box-shadow: 0 0 0 2px #e5e7eb;
}

.timeline-marker.status-reservada {
    background: #f59e0b;
}

.timeline-marker.status-ocupada {
    background: #10b981;
}

.timeline-marker.status-finalizada {
    background: #6b7280;
}

.timeline-marker.status-cancelada {
    background: #ef4444;
}

.timeline-content {
    background: #f9fafb;
    padding: 15px;
    border-radius: 8px;
    border-left: 3px solid #667eea;
}

.timeline-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.timeline-body {
    font-size: 14px;
}

.timeline-dates {
    color: #6b7280;
    margin-bottom: 5px;
}

.timeline-price {
    color: #059669;
    font-weight: 700;
}

/* STATS VERTICAL */
.stats-vertical {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background: #f9fafb;
    border-radius: 10px;
}

.stat-item .stat-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.stat-info {
    flex: 1;
}

.stat-item .stat-value {
    font-size: 24px;
    font-weight: 700;
    color: #1f2937;
}

.stat-item .stat-label {
    font-size: 12px;
    color: #6b7280;
    font-weight: 600;
}'
