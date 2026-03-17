<?php include BASE_PATH . "/views/hotel/_header.php"; ?>

<div class="page-header">
    <div>
        <h1>Editar Reserva #<?= (int)$reservation['id'] ?></h1>
        <p class="subtitle">Actualiza fechas, habitacion, huespedes y observaciones</p>
    </div>
    <a href="<?= BASE_URL ?>/hotel/reservas" class="btn-secondary">Volver</a>
</div>

<div class="card" style="padding: 24px; max-width: 900px; margin: 0 auto;">
    <form method="POST" action="<?= BASE_URL ?>/hotel/reservas/edit" class="form-grid">
        <input type="hidden" name="id" value="<?= (int)$reservation['id'] ?>">

        <div class="form-group full">
            <label>Cliente</label>
            <select name="cliente_id" required>
                <?php foreach ($clientes as $cliente): ?>
                    <option value="<?= (int)$cliente['id'] ?>" <?= (int)$cliente['id'] === (int)$reservation['cliente_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cliente['nombre']) ?> - <?= htmlspecialchars($cliente['documento']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Fecha entrada</label>
            <input type="date" name="fecha_entrada" value="<?= htmlspecialchars($reservation['fecha_entrada']) ?>" required>
        </div>

        <div class="form-group">
            <label>Fecha salida</label>
            <input type="date" name="fecha_salida" value="<?= htmlspecialchars($reservation['fecha_salida']) ?>" required>
        </div>

        <div class="form-group full">
            <label>Habitacion</label>
            <select name="habitacion_id" required>
                <?php foreach ($habitaciones as $habitacion): ?>
                    <option value="<?= (int)$habitacion['id'] ?>" <?= (int)$habitacion['id'] === (int)$reservation['habitacion_id'] ? 'selected' : '' ?>>
                        #<?= htmlspecialchars($habitacion['numero_habitacion']) ?> - <?= htmlspecialchars($habitacion['tipo']) ?>
                        (S/ <?= number_format((float)$habitacion['precio_noche'], 2) ?>/noche)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Numero de huespedes</label>
            <input type="number" name="numero_huespedes" min="1" value="<?= (int)$reservation['numero_huespedes'] ?>" required>
        </div>

        <div class="form-group checkbox-wrap">
            <label>
                <input type="checkbox" name="early_checkin" value="1" <?= !empty($reservation['early_checkin']) ? 'checked' : '' ?>> Early check-in
            </label>
            <label>
                <input type="checkbox" name="late_checkout" value="1" <?= !empty($reservation['late_checkout']) ? 'checked' : '' ?>> Late check-out
            </label>
        </div>

        <div class="form-group full">
            <label>Observaciones</label>
            <textarea name="observaciones" rows="4"><?= htmlspecialchars($reservation['observaciones'] ?? '') ?></textarea>
        </div>

        <div class="actions full">
            <button type="submit" class="btn-primary">Guardar Cambios</button>
            <a href="<?= BASE_URL ?>/hotel/reservas" class="btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<style>
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}
.form-group { display: grid; gap: 6px; }
.form-group.full { grid-column: 1 / -1; }
.form-group label { font-weight: 600; color: #374151; font-size: 14px; }
.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    padding: 10px 12px;
    font-size: 14px;
}
.checkbox-wrap {
    display: flex;
    align-items: center;
    gap: 16px;
    padding-top: 26px;
}
.actions { display: flex; gap: 10px; }
.btn-primary {
    padding: 10px 16px;
    border-radius: 8px;
    text-decoration: none;
    border: none;
    cursor: pointer;
    font-weight: 700;
    background: #4f46e5;
    color: white;
}
.btn-secondary {
    padding: 10px 16px;
    border-radius: 8px;
    text-decoration: none;
    border: none;
    cursor: pointer;
    font-weight: 700;
    background: #e5e7eb;
    color: #111827;
}
@media (max-width: 768px) {
    .form-grid { grid-template-columns: 1fr; }
    .navbar-menu { display: none; }
}
</style>

<?php include BASE_PATH . "/views/hotel/_footer.php"; ?>
