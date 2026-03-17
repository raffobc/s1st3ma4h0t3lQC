<?php include BASE_PATH . "/views/hotel/_header.php"; ?>

<div class="page-header">
    <div>
        <h1>Seguridad de Cuenta</h1>
        <p class="subtitle">Cambia la contrasena de tu usuario</p>
    </div>
    <a href="<?= BASE_URL ?>/hotel/dashboard" class="btn-secondary">Volver</a>
</div>

<div class="card" style="max-width: 700px; margin: 0 auto;">
    <form method="POST" class="form-container" autocomplete="off">
        <?php if (!empty($error)): ?>
            <div class="alert alert-error" style="margin-bottom: 20px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success" style="margin-bottom: 20px;">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <div class="form-group">
            <label class="form-label">Contrasena actual</label>
            <div class="password-wrap">
                <input type="password" name="current_password" id="currentPassword" class="form-control" required autofocus>
                <button type="button" class="toggle-password" data-target="currentPassword" aria-label="Mostrar u ocultar contrasena">👁</button>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Nueva contrasena</label>
            <div class="password-wrap">
                <input type="password" name="new_password" id="newPassword" class="form-control" required minlength="6">
                <button type="button" class="toggle-password" data-target="newPassword" aria-label="Mostrar u ocultar contrasena">👁</button>
            </div>
            <small class="form-help">Minimo 6 caracteres.</small>
        </div>

        <div class="form-group">
            <label class="form-label">Confirmar nueva contrasena</label>
            <div class="password-wrap">
                <input type="password" name="confirm_password" id="confirmPassword" class="form-control" required minlength="6">
                <button type="button" class="toggle-password" data-target="confirmPassword" aria-label="Mostrar u ocultar contrasena">👁</button>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary">Actualizar contrasena</button>
            <a href="<?= BASE_URL ?>/hotel/dashboard" class="btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<style>
.form-container { padding: 30px; }
.form-group { margin-bottom: 18px; }
.form-label { display:block; margin-bottom:8px; font-weight:600; color:#374151; }
.password-wrap { position: relative; }
.form-control { width:100%; padding:12px 16px; border:2px solid #e5e7eb; border-radius:8px; font-size:15px; }
.password-wrap .form-control { padding-right: 48px; }
.form-control:focus { outline:none; border-color:#2563eb; }
.toggle-password {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    width: 34px;
    height: 34px;
    border: none;
    border-radius: 8px;
    background: #f3f4f6;
    cursor: pointer;
    font-size: 16px;
    line-height: 1;
}
.toggle-password:hover { background: #e5e7eb; }
.form-actions { display:flex; gap:12px; margin-top:24px; }
.alert { padding:12px 14px; border-radius:8px; font-weight:600; }
.alert-error { background:#fee2e2; color:#991b1b; border-left:4px solid #ef4444; }
.alert-success { background:#dcfce7; color:#166534; border-left:4px solid #22c55e; }
</style>

<script>
document.querySelectorAll('.toggle-password').forEach(button => {
    button.addEventListener('click', () => {
        const targetId = button.getAttribute('data-target');
        const input = document.getElementById(targetId);
        if (!input) return;

        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        button.textContent = show ? '🙈' : '👁';
    });
});
</script>

<?php include BASE_PATH . "/views/hotel/_footer.php"; ?>
