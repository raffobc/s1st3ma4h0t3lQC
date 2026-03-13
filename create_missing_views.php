<?php
/**
 * CREAR TODAS LAS VISTAS FALTANTES
 * Archivo: C:\xampp\htdocs\hotel-system\create_missing_views.php
 */

$baseDir = __DIR__;
$created = [];

// 1. views/hotel/rooms.php
$roomsView = <<<'EOD'
<?php include BASE_PATH . "/views/hotel/_header.php"; ?>

<div class="page-header">
    <div>
        <h1>🏠 Habitaciones</h1>
        <p class="subtitle">Gestiona las habitaciones de tu hotel</p>
    </div>
    <a href="/hotel/habitaciones/create" class="btn-primary">+ Nueva Habitación</a>
</div>

<div class="card">
    <div class="rooms-grid">
        <?php if (empty($rooms)): ?>
            <div class="empty-state" style="grid-column: 1/-1;">
                <div class="empty-icon">🏠</div>
                <h3>No hay habitaciones</h3>
                <p>Comienza creando tu primera habitación</p>
            </div>
        <?php else: ?>
            <?php foreach ($rooms as $room): ?>
                <div class="room-card">
                    <div class="room-header">
                        <div class="room-number"><?= htmlspecialchars($room["numero_habitacion"]) ?></div>
                        <span class="status-badge status-<?= $room["estado"] ?>">
                            <?= ucfirst($room["estado"]) ?>
                        </span>
                    </div>
                    
                    <div class="room-body">
                        <div class="room-type"><?= htmlspecialchars($room["tipo"]) ?></div>
                        <div class="room-info">
                            <span>👥 <?= $room["capacidad"] ?> personas</span>
                            <span>💰 S/ <?= number_format($room["precio_noche"], 2) ?>/noche</span>
                        </div>
                        
                        <?php if ($room["descripcion"]): ?>
                            <p class="room-description"><?= htmlspecialchars($room["descripcion"]) ?></p>
                        <?php endif; ?>
                        
                        <?php if ($room["reservas_activas"] > 0): ?>
                            <div class="room-alert">
                                ⚠️ <?= $room["reservas_activas"] ?> reserva(s) activa(s)
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="room-footer">
                        <a href="/hotel/habitaciones/edit?id=<?= $room["id"] ?>" class="btn-edit">
                            ✏️ Editar
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.rooms-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    padding: 20px;
}
.room-card {
    background: #f9fafb;
    border-radius: 12px;
    overflow: hidden;
    border: 2px solid #e5e7eb;
    transition: all 0.3s;
}
.room-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    border-color: #667eea;
}
.room-header {
    background: white;
    padding: 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 2px solid #e5e7eb;
}
.room-number {
    font-size: 24px;
    font-weight: 700;
    color: #1f2937;
}
.room-body {
    padding: 20px;
}
.room-type {
    font-size: 18px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 10px;
}
.room-info {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
    font-size: 14px;
    color: #6b7280;
}
.room-description {
    font-size: 13px;
    color: #6b7280;
    line-height: 1.5;
}
.room-alert {
    background: #fef3c7;
    color: #92400e;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 12px;
    margin-top: 10px;
}
.room-footer {
    padding: 15px;
    background: white;
    border-top: 2px solid #e5e7eb;
}
.btn-edit {
    display: block;
    text-align: center;
    padding: 10px;
    background: #667eea;
    color: white;
    text-decoration: none;
    border-radius: 6px;
    font-weight: 600;
    transition: background 0.2s;
}
.btn-edit:hover {
    background: #5568d3;
}
</style>

<?php include BASE_PATH . "/views/hotel/_footer.php"; ?>
EOD;

file_put_contents($baseDir . "/views/hotel/rooms.php", $roomsView);
$created[] = "views/hotel/rooms.php";

// 2. views/hotel/room_form.php
$roomFormView = <<<'EOD'
<?php include BASE_PATH . "/views/hotel/_header.php"; ?>

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
                       value="<?= isset($room) ? htmlspecialchars($room['numero_habitacion']) : '' ?>" 
                       required placeholder="Ej: 101">
            </div>
            
            <div class="form-group">
                <label class="form-label">Tipo *</label>
                <select name="tipo" class="form-control" required>
                    <option value="">Seleccionar tipo</option>
                    <option value="Simple" <?= (isset($room) && $room['tipo'] === 'Simple') ? 'selected' : '' ?>>Simple</option>
                    <option value="Doble" <?= (isset($room) && $room['tipo'] === 'Doble') ? 'selected' : '' ?>>Doble</option>
                    <option value="Matrimonial" <?= (isset($room) && $room['tipo'] === 'Matrimonial') ? 'selected' : '' ?>>Matrimonial</option>
                    <option value="Suite" <?= (isset($room) && $room['tipo'] === 'Suite') ? 'selected' : '' ?>>Suite</option>
                    <option value="Suite Presidencial" <?= (isset($room) && $room['tipo'] === 'Suite Presidencial') ? 'selected' : '' ?>>Suite Presidencial</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Precio por Noche (S/) *</label>
                <input type="number" name="precio_noche" class="form-control" step="0.01" 
                       value="<?= isset($room) ? $room['precio_noche'] : '' ?>" 
                       required placeholder="150.00">
            </div>
            
            <div class="form-group">
                <label class="form-label">Capacidad (personas) *</label>
                <input type="number" name="capacidad" class="form-control" 
                       value="<?= isset($room) ? $room['capacidad'] : '' ?>" 
                       required placeholder="2">
            </div>
            
            <?php if (isset($room)): ?>
            <div class="form-group">
                <label class="form-label">Estado *</label>
                <select name="estado" class="form-control" required>
                    <option value="disponible" <?= $room['estado'] === 'disponible' ? 'selected' : '' ?>>Disponible</option>
                    <option value="ocupada" <?= $room['estado'] === 'ocupada' ? 'selected' : '' ?>>Ocupada</option>
                    <option value="reservada" <?= $room['estado'] === 'reservada' ? 'selected' : '' ?>>Reservada</option>
                    <option value="limpieza" <?= $room['estado'] === 'limpieza' ? 'selected' : '' ?>>En Limpieza</option>
                    <option value="mantenimiento" <?= $room['estado'] === 'mantenimiento' ? 'selected' : '' ?>>En Mantenimiento</option>
                </select>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="form-group">
            <label class="form-label">Descripción</label>
            <textarea name="descripcion" class="form-control" rows="4" 
                      placeholder="Características, amenidades, etc."><?= isset($room) ? htmlspecialchars($room['descripcion']) : '' ?></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn-primary">
                <?= isset($room) ? '💾 Actualizar Habitación' : '✨ Crear Habitación' ?>
            </button>
            <a href="/hotel/habitaciones" class="btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<style>
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
}
</style>

<?php include BASE_PATH . "/views/hotel/_footer.php"; ?>
EOD;

file_put_contents($baseDir . "/views/hotel/room_form.php", $roomFormView);
$created[] = "views/hotel/room_form.php";

// 3. views/hotel/reservations.php
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
                            <td><?= htmlspecialchars($reserva['cliente_nombre']) ?></td>
                            <td><span class="room-badge">🏠 <?= htmlspecialchars($reserva['numero_habitacion']) ?></span></td>
                            <td><?= date('d/m/Y', strtotime($reserva['fecha_entrada'])) ?></td>
                            <td><?= date('d/m/Y', strtotime($reserva['fecha_salida'])) ?></td>
                            <td><?= $reserva['numero_huespedes'] ?> 👥</td>
                            <td><strong>S/ <?= number_format($reserva['precio_total'], 2) ?></strong></td>
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
</style>

<?php include BASE_PATH . "/views/hotel/_footer.php"; ?>
EOD;

file_put_contents($baseDir . "/views/hotel/reservations.php", $reservationsView);
$created[] = "views/hotel/reservations.php";

// 4. views/hotel/reservation_form.php
$reservationFormView = <<<'EOD'
<?php include BASE_PATH . "/views/hotel/_header.php"; ?>

<div class="page-header">
    <div>
        <h1>📅 Nueva Reserva</h1>
        <p class="subtitle">Registra una nueva reserva</p>
    </div>
    <a href="/hotel/reservas" class="btn-secondary">← Volver</a>
</div>

<div class="card">
    <form method="POST" class="form-container">
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
            <small class="form-help">¿Cliente nuevo? <a href="/hotel/clientes/create" target="_blank">Registrar aquí</a></small>
        </div>
        
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Habitación *</label>
                <select name="habitacion_id" class="form-control" required>
                    <option value="">Seleccionar habitación</option>
                    <?php foreach ($habitaciones as $hab): ?>
                        <option value="<?= $hab['id'] ?>">
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
        
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Fecha de Entrada *</label>
                <input type="date" name="fecha_entrada" class="form-control" required min="<?= date('Y-m-d') ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">Fecha de Salida *</label>
                <input type="date" name="fecha_salida" class="form-control" required>
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">Observaciones</label>
            <textarea name="observaciones" class="form-control" rows="3" placeholder="Peticiones especiales, notas..."></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn-primary">✨ Crear Reserva</button>
            <a href="/hotel/reservas" class="btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<style>
.form-container { padding: 30px; }
.form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
.form-group { margin-bottom: 20px; }
.form-label { display: block; margin-bottom: 8px; font-weight: 600; color: #374151; font-size: 14px; }
.form-control { width: 100%; padding: 12px 16px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 15px; }
.form-control:focus { outline: none; border-color: #667eea; }
.form-help { display: block; margin-top: 5px; font-size: 12px; color: #6b7280; }
.form-help a { color: #667eea; text-decoration: none; font-weight: 600; }
.form-actions { display: flex; gap: 15px; margin-top: 30px; padding-top: 30px; border-top: 2px solid #e5e7eb; }
.btn-secondary { padding: 12px 24px; background: #f3f4f6; color: #374151; border: none; border-radius: 8px; font-weight: 600; text-decoration: none; font-size: 14px; display: inline-block; }
</style>

<?php include BASE_PATH . "/views/hotel/_footer.php"; ?>
EOD;

file_put_contents($baseDir . "/views/hotel/reservation_form.php", $reservationFormView);
$created[] = "views/hotel/reservation_form.php";

// 5. views/hotel/client_form.php
$clientFormView = <<<'EOD'
<?php include BASE_PATH . "/views/hotel/_header.php"; ?>

<div class="page-header">
    <div>
        <h1>👤 Nuevo Cliente</h1>
        <p class="subtitle">Registra un nuevo cliente</p>
    </div>
    <a href="/hotel/clientes" class="btn-secondary">← Volver</a>
</div>

<div class="card">
    <form method="POST" class="form-container">
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Nombre Completo *</label>
                <input type="text" name="nombre" class="form-control" required placeholder="Juan Pérez García">
            </div>
            
            <div class="form-group">
                <label class="form-label">Documento de Identidad *</label>
                <input type="text" name="documento" class="form-control" required placeholder="DNI, Pasaporte">
            </div>
        </div>
        
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Email *</label>
                <input type="email" name="email" class="form-control" required placeholder="cliente@email.com">
            </div>
            
            <div class="form-group">
                <label class="form-label">Teléfono *</label>
                <input type="tel" name="telefono" class="form-control" required placeholder="+51 999 999 999">
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">Fecha de Nacimiento</label>
            <input type="date" name="fecha_nacimiento" class="form-control" max="<?= date('Y-m-d') ?>">
        </div>
        
        <div class="form-group">
            <label class="form-label">Dirección</label>
            <textarea name="direccion" class="form-control" rows="3" placeholder="Dirección completa"></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn-primary">✨ Registrar Cliente</button>
            <a href="/hotel/clientes" class="btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<style>
.form-container { padding: 30px; }
.form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
.form-group { margin-bottom: 20px; }
.form-label { display: block; margin-bottom: 8px; font-weight: 600; color: #374151; font-size: 14px; }
.form-control { width: 100%; padding: 12px 16px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 15px; }
.form-control:focus { outline: none; border-color: #667eea; }
.form-actions { display: flex; gap: 15px; margin-top: 30px; padding-top: 30px; border-top: 2px solid #e5e7eb; }
.btn-secondary { padding: 12px 24px; background: #f3f4f6; color: #374151; border: none; border-radius: 8px; font-weight: 600; text-decoration: none; font-size: 14px; display: inline-block; }
</style>

<?php include BASE_PATH . "/views/hotel/_footer.php"; ?>
EOD;

file_put_contents($baseDir . "/views/hotel/client_form.php", $clientFormView);
$created[] = "views/hotel/client_form.php";

// Verificar y crear archivos de layout si no existen
if (!file_exists($baseDir . "/views/hotel/_header.php")) {
    $headerContent = <<<'EOD'
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $_SESSION["hotel_name"] ?? "Hotel Admin" ?></title>
    <link rel="stylesheet" href="/hotel-system/public/css/hotel-admin.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-brand">
            <div class="navbar-brand-icon">🏨</div>
            <div>
                <div class="navbar-brand-text"><?= htmlspecialchars($_SESSION["hotel_name"] ?? "Hotel") ?></div>
                <div class="navbar-hotel">Panel de Administración</div>
            </div>
        </div>
        
        <div class="navbar-menu">
            <a href="/hotel/dashboard" class="nav-link">Dashboard</a>
            <a href="/hotel/habitaciones" class="nav-link">Habitaciones</a>
            <a href="/hotel/reservas" class="nav-link">Reservas</a>
            <a href="/hotel/clientes" class="nav-link">Clientes</a>
        </div>
        
        <div class="navbar-user">
            <span style="font-weight: 600;"><?= htmlspecialchars($_SESSION["hotel_user_name"] ?? "Usuario") ?></span>
            <a href="/hotel/logout" class="btn-logout">Salir</a>
        </div>
    </nav>
    
    <div class="container">
EOD;
    
    file_put_contents($baseDir . "/views/hotel/_header.php", $headerContent);
    $created[] = "views/hotel/_header.php";
}

if (!file_exists($baseDir . "/views/hotel/_footer.php")) {
    $footerContent = <<<'EOD'
    </div>
</body>
</html>
EOD;
    
    file_put_contents($baseDir . "/views/hotel/_footer.php", $footerContent);
    $created[] = "views/hotel/_footer.php";
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>✅ Todas las Vistas Creadas</title>
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
        .success {
            background: #dcfce7;
            border-left: 4px solid #10b981;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: center;
        }
        .files {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .file {
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
        .text-center { text-align: center; margin-top: 30px; }
        .icon { font-size: 64px; text-align: center; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🎉</div>
        <h1>¡Sistema Completo!</h1>
        
        <div class="success">
            <strong>✅ Todas las vistas han sido creadas exitosamente</strong><br>
            <?= count($created) ?> archivos creados
        </div>
        
        <h3>📁 Arch
