<?php include BASE_PATH . "/views/hotel/_header.php"; ?>

<div class="container">
    <div class="dashboard-header" style="margin-bottom: 20px;">
        <h1>Usuarios del Hotel</h1>
        <p class="subtitle">Crea y administra cuentas del personal operativo</p>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">Operacion realizada correctamente.</div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error">
            <?php
            $error = $_GET['error'];
            if ($error === 'csrf') echo 'Solicitud invalida (CSRF).';
            elseif ($error === 'duplicate') echo 'El correo ya existe.';
            elseif ($error === 'email') echo 'Correo no valido.';
            elseif ($error === 'pass') echo 'La contraseña debe tener al menos 6 caracteres.';
            elseif ($error === 'self') echo 'No puedes desactivar tu propio usuario.';
            else echo 'No se pudo completar la operacion.';
            ?>
        </div>
    <?php endif; ?>

    <div class="card" style="padding: 24px; margin-bottom: 20px;">
        <h2 style="margin-top: 0;">Crear Usuario</h2>
        <form method="POST" action="<?= BASE_URL ?>/hotel/usuarios/create" class="user-form-grid">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

            <div class="form-group">
                <label>Nombre completo</label>
                <input type="text" name="nombre" required>
            </div>

            <div class="form-group">
                <label>Correo</label>
                <input type="email" name="email" required>
            </div>

            <div class="form-group">
                <label>Contraseña</label>
                <input type="password" name="password" required minlength="6">
            </div>

            <div class="form-group">
                <label>Rol</label>
                <select name="rol" required>
                    <option value="recepcion">Recepcion</option>
                    <option value="staff" selected>Staff</option>
                    <option value="admin">Admin</option>
                </select>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary">Crear usuario</button>
            </div>
        </form>
    </div>

    <div class="card" style="padding: 24px;">
        <h2 style="margin-top: 0;">Usuarios Registrados</h2>
        <?php if (empty($users)): ?>
            <p>No hay usuarios registrados.</p>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Accion</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['nombre']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars(ucfirst($user['rol'])) ?></td>
                            <td><?= (int)$user['activo'] === 1 ? 'Activo' : 'Inactivo' ?></td>
                            <td>
                                <form method="POST" action="<?= BASE_URL ?>/hotel/usuarios/toggle" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                    <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">
                                    <input type="hidden" name="activo" value="<?= (int)$user['activo'] === 1 ? 0 : 1 ?>">
                                    <button type="submit" class="btn-toggle">
                                        <?= (int)$user['activo'] === 1 ? 'Desactivar' : 'Activar' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<style>
.user-form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
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
.form-actions {
    grid-column: 1 / -1;
}
.btn-primary {
    border: none;
    border-radius: 8px;
    background: #4f46e5;
    color: #fff;
    padding: 10px 16px;
    font-weight: 700;
    cursor: pointer;
}
.btn-toggle {
    border: none;
    border-radius: 6px;
    background: #e5e7eb;
    color: #111827;
    padding: 8px 12px;
    font-weight: 600;
    cursor: pointer;
}
.table {
    width: 100%;
    border-collapse: collapse;
}
.table th,
.table td {
    border-bottom: 1px solid #e5e7eb;
    padding: 10px;
    text-align: left;
}
.alert {
    padding: 10px 12px;
    border-radius: 8px;
    margin-bottom: 16px;
    font-weight: 600;
}
.alert-success {
    background: #d1fae5;
    color: #065f46;
}
.alert-error {
    background: #fee2e2;
    color: #991b1b;
}
</style>

<?php include BASE_PATH . "/views/hotel/_footer.php"; ?>
