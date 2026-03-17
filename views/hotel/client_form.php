<?php include BASE_PATH . "/views/hotel/_header.php"; ?>

<div class="page-header">
    <div>
        <h1><?= isset($client) ? "✏️ Editar Cliente" : "👤 Nuevo Cliente" ?></h1>
        <p class="subtitle"><?= isset($client) ? "Actualiza la información del cliente" : "Registra un nuevo cliente" ?></p>
    </div>
    <a href="<?= BASE_URL ?>/hotel/clientes" class="btn-secondary">← Volver</a>
</div>

<div class="card">
    <?php if (isset($_GET['error']) && $_GET['error'] === 'duplicate'): ?>
        <div class="alert alert-error" style="margin: 20px 20px 0 20px;">
            ❌ Ya existe un cliente con ese documento.
        </div>
    <?php elseif (isset($_GET['error']) && $_GET['error'] === 'required'): ?>
        <div class="alert alert-error" style="margin: 20px 20px 0 20px;">
            ❌ Completa nombre y documento para continuar.
        </div>
    <?php elseif (isset($_GET['error']) && $_GET['error'] === 'save'): ?>
        <div class="alert alert-error" style="margin: 20px 20px 0 20px;">
            ❌ No se pudo registrar el cliente. Intenta nuevamente.
        </div>
    <?php endif; ?>

    <form method="POST" class="form-container">
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Documento de Identidad (DNI) *</label>
                <input type="text" name="documento" id="documentoInput" class="form-control" required placeholder="DNI, Pasaporte"
                       autofocus value="<?= htmlspecialchars($client['documento'] ?? '') ?>">
                <small id="dniHint" style="display:block; margin-top:6px; color:#6b7280;"></small>
            </div>
            
            <div class="form-group">
                <label class="form-label">Nombre Completo *</label>
                <input type="text" name="nombre" id="nombreInput" class="form-control" required placeholder="Juan Pérez García"
                       value="<?= htmlspecialchars($client['nombre'] ?? '') ?>">
            </div>
        </div>
        
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Email *</label>
                <input type="email" name="email" class="form-control" required placeholder="cliente@email.com"
                       value="<?= htmlspecialchars($client['email'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">Teléfono *</label>
                <input type="tel" name="telefono" class="form-control" required placeholder="+51 999 999 999"
                       value="<?= htmlspecialchars($client['telefono'] ?? '') ?>">
            </div>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Ciudad</label>
                <input type="text" name="ciudad" class="form-control" placeholder="Lima"
                       value="<?= htmlspecialchars($client['ciudad'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label">País</label>
                <input type="text" name="pais" class="form-control" placeholder="Perú"
                       value="<?= htmlspecialchars($client['pais'] ?? '') ?>">
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn-primary"><?= isset($client) ? '💾 Actualizar Cliente' : '✨ Registrar Cliente' ?></button>
            <a href="<?= BASE_URL ?>/hotel/clientes" class="btn-secondary">Cancelar</a>
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
.alert { padding: 14px 18px; border-radius: 10px; font-weight: 600; }
.alert-error { background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }
</style>

<?php include BASE_PATH . "/views/hotel/_footer.php"; ?>

<script>
document.getElementById('documentoInput').addEventListener('blur', function () {
    const doc = this.value.trim();
    if (doc.length < 6) return;

    const hint = document.getElementById('dniHint');
    hint.textContent = 'Buscando...';
    hint.style.color = '#6b7280';

    fetch('<?= BASE_URL ?>/hotel/clientes/find-by-document?documento=' + encodeURIComponent(doc))
        .then(r => r.json())
        .then(data => {
            if (!data.success || !data.found || !data.cliente) {
                hint.textContent = 'DNI no encontrado. Completa los datos manualmente.';
                hint.style.color = '#6b7280';
                return;
            }
            const c = data.cliente;
            const nombreInput = document.getElementById('nombreInput');
            if (nombreInput && !nombreInput.value) nombreInput.value = c.nombre || '';
            const emailInput = document.querySelector('input[name="email"]');
            if (emailInput && !emailInput.value) emailInput.value = c.email || '';
            const telefonoInput = document.querySelector('input[name="telefono"]');
            if (telefonoInput && !telefonoInput.value) telefonoInput.value = c.telefono || '';
            hint.textContent = data.source === 'local'
                ? '✅ Cliente encontrado en la base de datos local.'
                : '✅ Datos cargados desde RENIEC. Verifica y completa.';
            hint.style.color = '#059669';
            setTimeout(() => nombreInput?.focus(), 50);
        })
        .catch(() => {
            hint.textContent = 'No se pudo consultar el DNI ahora.';
            hint.style.color = '#6b7280';
        });
});
</script>
