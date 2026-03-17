<?php include BASE_PATH . "/views/hotel/_header.php"; ?>

<div class="container">
    <div class="dashboard-header" style="margin-bottom: 20px;">
        <h1>Editar Usuario</h1>
        <p class="subtitle">Actualiza datos del usuario del hotel</p>
    </div>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error">
            <?php
            $error = $_GET['error'];
            if ($error === 'duplicate') echo 'El correo ya existe.';
            elseif ($error === 'email') echo 'Correo no valido.';
            elseif ($error === 'pass') echo 'La contraseña debe tener al menos 6 caracteres.';
            else echo 'No se pudo actualizar el usuario.';
            ?>
        </div>
    <?php endif; ?>

    <div class="card" style="padding: 24px; max-width: 760px; margin: 0 auto;">
        <form method="POST" action="<?= BASE_URL ?>/hotel/usuarios/edit" class="form-grid">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
            <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">

            <div class="form-group">
                <label>Nombre completo</label>
                <input type="text" name="nombre" required value="<?= htmlspecialchars($user['nombre']) ?>">
            </div>

            <div class="form-group">
                <label>Correo</label>
                <input type="email" name="email" required value="<?= htmlspecialchars($user['email']) ?>">
            </div>

            <div class="form-group">
                <label>Rol</label>
                <select name="rol" required>
                    <option value="recepcion" <?= $user['rol'] === 'recepcion' ? 'selected' : '' ?>>Recepcion</option>
                    <option value="staff" <?= $user['rol'] === 'staff' ? 'selected' : '' ?>>Staff</option>
                    <option value="admin" <?= $user['rol'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>

            <div class="form-group">
                <label>Nueva contraseña (opcional)</label>
                <input type="password" name="password" minlength="6" placeholder="Dejar vacio para mantener la actual">
            </div>

            <div class="actions">
                <button type="submit" class="btn-primary">Guardar cambios</button>
                <a href="<?= BASE_URL ?>/hotel/usuarios" class="btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<style>
.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 14px;
}
.form-group {
    display: grid;
    gap: 6px;
}
.form-group input,
.form-group select {
    border: 1px solid #d1d5db;
    border-radius: 8px;
    padding: 10px 12px;
    font-size: 14px;
}
.actions {
    grid-column: 1 / -1;
    display: flex;
    gap: 10px;
}
.btn-primary {
    border: none;
    border-radius: 8px;
    background: #4f46e5;
    color: #fff;
    padding: 10px 16px;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
}
.btn-secondary {
    border: none;
    border-radius: 8px;
    background: #e5e7eb;
    color: #111827;
    padding: 10px 16px;
    font-weight: 700;
    text-decoration: none;
}
.alert {
    padding: 10px 12px;
    border-radius: 8px;
    margin-bottom: 16px;
    font-weight: 600;
}
.alert-error {
    background: #fee2e2;
    color: #991b1b;
}
</style>

<?php include BASE_PATH . "/views/hotel/_footer.php"; ?>
