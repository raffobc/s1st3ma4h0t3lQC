<?php include BASE_PATH . "/views/hotel/_header.php"; ?>

<div class="page-header">
    <div>
        <h1><?= isset($room) ? "✏️ Editar Habitación" : "➕ Nueva Habitación" ?></h1>
        <p class="subtitle">Completa los datos de la habitación</p>
    </div>
    <a href="<?= BASE_URL ?>/hotel/habitaciones" class="btn-secondary">← Volver</a>
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
            <a href="<?= BASE_URL ?>/hotel/habitaciones" class="btn-secondary">Cancelar</a>
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
