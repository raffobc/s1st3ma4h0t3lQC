<?php include BASE_PATH . "/views/hotel/_header.php"; ?>

<div class="page-header">
    <div>
        <h1>🚶 Check-in Directo (Walk-in)</h1>
        <p class="subtitle">Cliente sin reserva previa</p>
    </div>
    <a href="<?= BASE_URL ?>/hotel/reservas" class="btn-secondary">← Volver</a>
</div>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error">
        ❌ Error: <?= htmlspecialchars($_GET['error']) ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="form-container">
        
        <!-- PASO 1: Fechas -->
        <div class="step-section <?= empty($_GET['fecha_salida']) ? 'active' : 'completed' ?>">
            <div class="step-header">
                <span class="step-number">1</span>
                <h3>Definir Estadía</h3>
            </div>
            
            <form method="GET" action="" id="fechasForm">
                <div class="info-box">
                    <strong>📅 Check-in:</strong> HOY (<?= date('d/m/Y') ?>) a las <?= date('H:i') ?>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Fecha de Salida *</label>
                        <input type="date" name="fecha_salida" id="fechaSalidaBuscar" class="form-control" 
                               required min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                               value="<?= htmlspecialchars($_GET['fecha_salida'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Número de Huéspedes *</label>
                        <input type="number" name="numero_huespedes" class="form-control" 
                               min="1" value="<?= htmlspecialchars($_GET['numero_huespedes'] ?? '1') ?>" required>
                    </div>
                </div>
                
                <button type="submit" class="btn-primary">
                    🔍 Buscar Habitaciones Disponibles
                </button>
            </form>
        </div>
        
        <?php if (!empty($_GET['fecha_salida'])): ?>
        
        <form method="POST" action="<?= BASE_URL ?>/hotel/reservas/create-walkin" id="walkinForm">
            <input type="hidden" name="fecha_salida" value="<?= htmlspecialchars($_GET['fecha_salida']) ?>">
            <input type="hidden" name="numero_huespedes" value="<?= htmlspecialchars($_GET['numero_huespedes']) ?>">
            
            <!-- PASO 2: Habitación -->
            <div class="step-section active" style="margin-top: 20px;">
                <div class="step-header">
                    <span class="step-number">2</span>
                    <h3>Seleccionar Habitación</h3>
                </div>
                
                <?php if (empty($habitaciones)): ?>
                    <div class="alert alert-warning">
                        ⚠️ No hay habitaciones disponibles para <strong><?= htmlspecialchars($_GET['numero_huespedes']) ?> huésped<?= $_GET['numero_huespedes'] > 1 ? 'es' : '' ?></strong> 
                        en las fechas seleccionadas.
                        <br><br>
                        <strong>Sugerencias:</strong>
                        <ul style="margin: 10px 0 0 20px;">
                            <li>Intenta con menos huéspedes</li>
                            <li>Intenta con otras fechas</li>
                            <li>Considera reservar múltiples habitaciones</li>
                        </ul>
                        <a href="<?= BASE_URL ?>/hotel/reservas/walkin" style="display: inline-block; margin-top: 10px; padding: 8px 16px; background: #f59e0b; color: white; border-radius: 6px; text-decoration: none; font-weight: 600;">
                            Buscar de Nuevo
                        </a>
                    </div>
                <?php else: ?>
                    <div class="info-box">
                        ℹ️ Mostrando habitaciones para <strong><?= htmlspecialchars($_GET['numero_huespedes']) ?> huésped<?= $_GET['numero_huespedes'] > 1 ? 'es' : '' ?></strong>, 
                        disponibles del <strong><?= date('d/m/Y') ?></strong> al 
                        <strong><?= date('d/m/Y', strtotime($_GET['fecha_salida'])) ?></strong>
                        (<?php 
                            $hoy = new DateTime();
                            $salida = new DateTime($_GET['fecha_salida']);
                            echo max(1, $hoy->diff($salida)->days);
                        ?> noche<?= (max(1, $hoy->diff($salida)->days) > 1) ? 's' : '' ?>)
                    </div>
                    
                    <div class="habitaciones-disponibles">
                        <?php foreach ($habitaciones as $hab): ?>
                            <label class="habitacion-option">
                                <input type="radio" name="habitacion_id" value="<?= $hab['id'] ?>" 
                                       data-precio="<?= $hab['precio_noche'] ?>" required>
                                <div class="habitacion-card">
                                    <div class="habitacion-numero">#<?= $hab['numero_habitacion'] ?></div>
                                    <div class="habitacion-tipo"><?= htmlspecialchars($hab['tipo']) ?></div>
                                    <div class="habitacion-info">
                                        <span>👥 <?= $hab['capacidad'] ?> personas</span>
                                        <span class="habitacion-precio">S/ <?= number_format($hab['precio_noche'], 2) ?>/noche</span>
                                    </div>
                                    <?php if (!empty($hab['descripcion'])): ?>
                                        <div class="habitacion-desc"><?= htmlspecialchars($hab['descripcion']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- PASO 3: Cliente -->
                    <div class="step-section active" style="margin-top: 20px;">
                        <div class="step-header">
                            <span class="step-number">3</span>
                            <h3>Información del Cliente</h3>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">¿Es cliente registrado?</label>
                            <select id="clienteTipo" class="form-control" onchange="toggleClienteForm()">
                                <option value="new">Cliente Nuevo</option>
                                <option value="existing">Cliente Existente</option>
                            </select>
                        </div>
                        
                        <div id="clienteExistenteDiv" style="display: none;">
                            <div class="form-group">
                                <label class="form-label">Seleccionar Cliente</label>
                                <select name="cliente_id" id="clienteExistente" class="form-control">
                                    <option value="">Buscar cliente...</option>
                                    <?php foreach ($clientes as $cliente): ?>
                                        <option value="<?= $cliente['id'] ?>"
                                                data-nombre="<?= htmlspecialchars($cliente['nombre'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-documento="<?= htmlspecialchars($cliente['documento'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                data-telefono="<?= htmlspecialchars($cliente['telefono'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                data-email="<?= htmlspecialchars($cliente['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars($cliente['nombre']) ?> - <?= htmlspecialchars($cliente['documento']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div id="clienteNuevoDiv">
                            <input type="hidden" name="cliente_id" id="clienteIdNuevoFlag" value="new">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">Documento (DNI) *</label>
                                    <input type="text" name="cliente_documento" id="clienteDocumento" 
                                           class="form-control" placeholder="DNI o Pasaporte"
                                           onblur="buscarClientePorDocumentoWalkin()">
                                    <small id="clienteDocumentoWalkinHint" style="display:block; margin-top:6px; color:#6b7280;"></small>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Nombre Completo *</label>
                                    <input type="text" name="cliente_nombre" id="clienteNombre" 
                                           class="form-control" placeholder="Juan Pérez">
                                </div>
                            </div>
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="cliente_email" class="form-control" 
                                           placeholder="cliente@email.com">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Teléfono</label>
                                    <input type="tel" name="cliente_telefono" class="form-control" 
                                           placeholder="+51 999 999 999">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- PASO 4: Opciones y Precio -->
                    <div class="step-section active" style="margin-top: 20px;">
                        <div class="step-header">
                            <span class="step-number">4</span>
                            <h3>Opciones y Precio</h3>
                        </div>
                        
                        <div class="horarios-section">
                            <h4>⏰ Opciones de Horario</h4>
                            <label class="checkbox-card">
                                <input type="checkbox" name="late_checkout" id="lateCheckout" value="1">
                                <div class="checkbox-content">
                                    <div class="checkbox-title">⚡ Late Check-out</div>
                                    <div class="checkbox-desc">Salida hasta 6:00 PM</div>
                                    <div class="checkbox-price">+S/ 50.00</div>
                                </div>
                            </label>
                        </div>
                        
                        <div class="price-summary">
                            <div class="price-row">
                                <span>Precio por noche:</span>
                                <strong id="precioNoche">S/ 0.00</strong>
                            </div>
                            <div class="price-row">
                                <span>Número de noches:</span>
                                <strong id="numeroNoches">
                                    <?php 
                                    $hoy = new DateTime();
                                    $salida = new DateTime($_GET['fecha_salida']);
                                    echo max(1, $hoy->diff($salida)->days);
                                    ?>
                                </strong>
                            </div>
                            <div class="price-row">
                                <span>Subtotal:</span>
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
                                      placeholder="Notas especiales..."></textarea>
                        </div>
                    </div>
                    
                    <!-- PASO 5: Registro de Huéspedes -->
                    <div class="step-section active" style="margin-top: 20px;">
                        <div class="step-header">
                            <span class="step-number">5</span>
                            <h3>Registro de Huéspedes</h3>
                        </div>
                        
                        <div class="checkin-alert">
                            <strong>📋 Importante:</strong>
                            <p style="margin: 5px 0 0 0; color: #1e40af;">
                                Registra los datos de TODOS los huéspedes que se hospedarán 
                                (<?= htmlspecialchars($_GET['numero_huespedes']) ?> huésped<?= $_GET['numero_huespedes'] > 1 ? 'es' : '' ?>).
                            </p>
                        </div>
                        
                        <div class="section-header">
                            <button type="button" onclick="agregarHuespedWalkin()" class="btn-add">
                                + Agregar Huésped
                            </button>
                        </div>
                        
                        <div id="huespedesContainer">
                            <!-- Se llenarán con JavaScript -->
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-primary btn-large">
                            ✅ Realizar Check-in Directo
                        </button>
                        <a href="<?= BASE_URL ?>/hotel/reservas/walkin" class="btn-secondary">
                            Cambiar Fechas
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
        </form>
        
        <?php endif; ?>
    </div>
</div>

<style>
.step-section {
    padding: 25px;
    background: #f9fafb;
    border-radius: 12px;
    border: 2px solid #e5e7eb;
    opacity: 0.7;
}

.step-section.active {
    background: white;
    border-color: #667eea;
    opacity: 1;
}

.step-section.completed {
    background: #f0fdf4;
    border-color: #10b981;
    opacity: 1;
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

.step-section.completed .step-number {
    background: #10b981;
}

.step-header h3 {
    margin: 0;
    color: #1f2937;
    font-size: 20px;
}

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

.habitaciones-disponibles {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 15px;
    margin: 20px 0;
}

.habitacion-option {
    cursor: pointer;
    display: block;
}

.habitacion-option input[type="radio"] {
    display: none;
}

.habitacion-card {
    background: #f9fafb;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 20px;
    transition: all 0.3s;
}

.habitacion-option input[type="radio"]:checked + .habitacion-card {
    border-color: #10b981;
    background: #f0fdf4;
}

.habitacion-option:hover .habitacion-card {
    border-color: #667eea;
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
}

.habitacion-numero {
    font-size: 24px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 5px;
}

.habitacion-tipo {
    font-size: 16px;
    color: #6b7280;
    font-weight: 600;
    margin-bottom: 10px;
}

.habitacion-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 14px;
}

.habitacion-precio {
    font-size: 18px;
    font-weight: 700;
    color: #10b981;
}

.habitacion-desc {
    font-size: 13px;
    color: #6b7280;
    margin-top: 10px;
    line-height: 1.4;
}

.info-box {
    background: #dbeafe;
    border-left: 4px solid #3b82f6;
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    color: #1e40af;
}

.checkin-alert {
    background: #fef3c7;
    border-left: 4px solid #f59e0b;
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.checkin-alert strong {
    color: #92400e;
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

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.btn-add {
    padding: 10px 20px;
    background: #10b981;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
}

.huesped-card {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 15px;
}

.huesped-card.titular {
    border-color: #10b981;
    background: #f0fdf4;
}

.huesped-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.huesped-number {
    font-weight: 700;
    color: #1f2937;
    font-size: 16px;
}

.titular-badge {
    background: #10b981;
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    margin-left: 10px;
}

.btn-remove {
    background: #fee2e2;
    color: #991b1b;
    border: none;
    padding: 6px 12px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 12px;
    font-weight: 600;
}

.form-grid-2 {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
}

.huesped-source {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 14px;
    margin-bottom: 15px;
}

.form-actions {
    display: flex;
    gap: 15px;
    margin-top: 30px;
    padding-top: 30px;
    border-top: 2px solid #e5e7eb;
}

.btn-primary, .btn-secondary {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    text-decoration: none;
    display: inline-block;
}

.btn-primary {
    background: #10b981;
    color: white;
}

.btn-primary:hover {
    background: #059669;
}

.btn-primary.btn-large {
    padding: 16px 40px;
    font-size: 16px;
    flex: 1;
}

.btn-secondary {
    background: #f3f4f6;
    color: #374151;
}

.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin: 20px 0;
}

.alert-error {
    background: #fee2e2;
    color: #991b1b;
    border-left: 4px solid #ef4444;
}

.alert-warning {
    background: #fef3c7;
    color: #92400e;
    border-left: 4px solid #f59e0b;
}

.alert-warning a {
    color: #92400e;
    font-weight: 700;
    text-decoration: underline;
}
</style>

<script>
let contadorHuespedesWalkin = 0;
const clientesDB = <?= json_encode(array_map(function ($cliente) {
    return [
        'id' => (int)($cliente['id'] ?? 0),
        'nombre' => (string)($cliente['nombre'] ?? ''),
        'documento' => (string)($cliente['documento'] ?? ''),
        'telefono' => (string)($cliente['telefono'] ?? ''),
        'email' => (string)($cliente['email'] ?? ''),
    ];
}, $clientes ?? []), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function toggleClienteForm() {
    const tipo = document.getElementById('clienteTipo').value;
    const nuevoDiv = document.getElementById('clienteNuevoDiv');
    const existenteDiv = document.getElementById('clienteExistenteDiv');
    const nuevoFlag = document.getElementById('clienteIdNuevoFlag');
    
    if (tipo === 'new') {
        nuevoDiv.style.display = 'block';
        existenteDiv.style.display = 'none';
        document.getElementById('clienteNombre').required = true;
        document.getElementById('clienteDocumento').required = true;
        document.getElementById('clienteExistente').required = false;
        if (nuevoFlag) nuevoFlag.disabled = false;
    } else {
        nuevoDiv.style.display = 'none';
        existenteDiv.style.display = 'block';
        document.getElementById('clienteNombre').required = false;
        document.getElementById('clienteDocumento').required = false;
        document.getElementById('clienteExistente').required = true;
        if (nuevoFlag) nuevoFlag.disabled = true;
    }

    syncTitularFromCliente();
}

function setWalkinDocumentoHint(message, ok = false) {
    const hint = document.getElementById('clienteDocumentoWalkinHint');
    if (!hint) return;
    hint.textContent = message || '';
    hint.style.color = ok ? '#059669' : '#6b7280';
}

function buscarClientePorDocumentoWalkin() {
    const documentoInput = document.getElementById('clienteDocumento');
    const clienteTipo = document.getElementById('clienteTipo');
    const clienteSelect = document.getElementById('clienteExistente');

    if (!documentoInput || !clienteTipo || !clienteSelect || clienteTipo.value !== 'new') {
        return;
    }

    const documento = documentoInput.value.trim();
    if (documento.length < 6) {
        setWalkinDocumentoHint('');
        return;
    }

    fetch('<?= BASE_URL ?>/hotel/clientes/find-by-document?documento=' + encodeURIComponent(documento))
        .then(response => response.json())
        .then(data => {
            if (!data.success || !data.found || !data.cliente) {
                setWalkinDocumentoHint('No se encontró cliente con este documento.');
                return;
            }

            if (data.source === 'local' && data.cliente.id) {
                let option = clienteSelect.querySelector(`option[value="${data.cliente.id}"]`);
                if (!option) {
                    option = document.createElement('option');
                    option.value = data.cliente.id;
                    option.textContent = `${data.cliente.nombre} - ${data.cliente.documento}`;
                    option.dataset.nombre = data.cliente.nombre || '';
                    option.dataset.documento = data.cliente.documento || '';
                    option.dataset.telefono = data.cliente.telefono || '';
                    option.dataset.email = data.cliente.email || '';
                    clienteSelect.appendChild(option);
                }

                clienteTipo.value = 'existing';
                toggleClienteForm();
                clienteSelect.value = String(data.cliente.id);
                syncTitularFromCliente();
                setWalkinDocumentoHint('Cliente local encontrado y cargado automáticamente.', true);
                return;
            }

            document.getElementById('clienteNombre').value = data.cliente.nombre || '';
            document.querySelector('input[name="cliente_email"]').value = data.cliente.email || '';
            document.querySelector('input[name="cliente_telefono"]').value = data.cliente.telefono || '';
            syncTitularFromCliente();
            setWalkinDocumentoHint('Datos cargados desde RENIEC. Completa y continúa con el registro.', true);
        })
        .catch(() => {
            setWalkinDocumentoHint('No se pudo validar el documento ahora.');
        });
}

function setTitularData(datos = {}) {
    const titularNombre = document.querySelector('input[name="huespedes[0][nombre]"]');
    const titularDocumento = document.querySelector('input[name="huespedes[0][documento]"]');
    const titularTelefono = document.querySelector('input[name="huespedes[0][telefono]"]');
    const titularEmail = document.querySelector('input[name="huespedes[0][email]"]');

    if (!titularNombre || !titularDocumento || !titularTelefono || !titularEmail) {
        return;
    }

    titularNombre.value = datos.nombre || '';
    titularDocumento.value = datos.documento || '';
    titularTelefono.value = datos.telefono || '';
    titularEmail.value = datos.email || '';
}

function syncTitularFromCliente() {
    const tipo = document.getElementById('clienteTipo')?.value;

    if (tipo === 'existing') {
        const select = document.getElementById('clienteExistente');
        const selected = select?.selectedOptions?.[0];
        if (!selected || !selected.value) {
            setTitularData({});
            return;
        }

        setTitularData({
            nombre: selected.dataset.nombre || '',
            documento: selected.dataset.documento || '',
            telefono: selected.dataset.telefono || '',
            email: selected.dataset.email || ''
        });
        return;
    }

    setTitularData({
        nombre: document.getElementById('clienteNombre')?.value || '',
        documento: document.getElementById('clienteDocumento')?.value || '',
        telefono: document.querySelector('input[name="cliente_telefono"]')?.value || '',
        email: document.querySelector('input[name="cliente_email"]')?.value || ''
    });
}

function agregarHuespedWalkin(esTitular = false, datos = {}) {
    contadorHuespedesWalkin++;
    const container = document.getElementById('huespedesContainer');
    const index = contadorHuespedesWalkin - 1;
    
    const huespedHTML = `
        <div class="huesped-card ${esTitular ? 'titular' : ''}" id="huesped-walkin-${index}">
            <div class="huesped-header">
                <div class="huesped-number">
                    Huésped ${contadorHuespedesWalkin}
                    ${esTitular ? '<span class="titular-badge">TITULAR</span>' : ''}
                </div>
                ${!esTitular ? `<button type="button" onclick="eliminarHuespedWalkin(${index})" class="btn-remove">✕ Eliminar</button>` : ''}
            </div>
            ${!esTitular ? `
            <div class="huesped-source">
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Origen de datos</label>
                        <select class="form-control" onchange="cambiarModoCargaHuesped(${index}, this.value)">
                            <option value="new">Registrar datos nuevos</option>
                            <option value="db">Buscar en base de datos</option>
                        </select>
                    </div>
                    <div class="form-group" id="huesped-db-search-wrap-${index}" style="display:none;">
                        <label class="form-label">Buscar cliente</label>
                        <input type="text" id="huesped-db-search-${index}" class="form-control"
                               placeholder="Nombre o documento" oninput="filtrarClientesDB(${index})">
                    </div>
                </div>
                <div class="form-group" id="huesped-db-select-wrap-${index}" style="display:none; margin-bottom: 0;">
                    <label class="form-label">Resultado</label>
                    <select id="huesped-db-select-${index}" class="form-control" onchange="aplicarClienteDBAHuesped(${index})">
                        <option value="">Selecciona cliente...</option>
                    </select>
                </div>
            </div>
            ` : ''}
            
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">Nombre Completo *</label>
                    <input type="text" name="huespedes[${index}][nombre]" 
                           class="form-control" required 
                           value="${datos.nombre || ''}"
                           placeholder="Nombre completo">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Tipo Documento *</label>
                    <select name="huespedes[${index}][tipo_documento]" class="form-control" required>
                        <option value="DNI" selected>DNI</option>
                        <option value="Pasaporte">Pasaporte</option>
                        <option value="Carnet de Extranjería">Carnet de Extranjería</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>
            </div>
            
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">N° Documento *</label>
                    <input type="text" name="huespedes[${index}][documento]" 
                           class="form-control" required 
                           value="${datos.documento || ''}"
                           placeholder="12345678">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Fecha Nacimiento</label>
                    <input type="date" name="huespedes[${index}][fecha_nacimiento]" 
                           class="form-control" max="${new Date().toISOString().split('T')[0]}">
                </div>
            </div>
            
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">Nacionalidad</label>
                    <input type="text" name="huespedes[${index}][nacionalidad]" 
                           class="form-control" 
                           value="Peruana"
                           placeholder="Peruana">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Teléfono</label>
                    <input type="tel" name="huespedes[${index}][telefono]" 
                           class="form-control" 
                           value="${datos.telefono || ''}"
                           placeholder="+51 999 999 999">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="huespedes[${index}][email]" 
                       class="form-control" 
                       value="${datos.email || ''}"
                       placeholder="correo@ejemplo.com">
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', huespedHTML);

    if (!esTitular) {
        renderClientesDB(index);
    }
}

function renderClientesDB(index, filtro = '') {
    const select = document.getElementById(`huesped-db-select-${index}`);
    if (!select) return;

    const texto = (filtro || '').trim().toLowerCase();
    const filtrados = texto
        ? clientesDB.filter(c =>
            (c.nombre || '').toLowerCase().includes(texto) ||
            (c.documento || '').toLowerCase().includes(texto)
        )
        : clientesDB;

    const options = filtrados.map(c =>
        `<option value="${c.id}">${escapeHtml(c.nombre)} - ${escapeHtml(c.documento || 'Sin documento')}</option>`
    ).join('');

    select.innerHTML = '<option value="">Selecciona cliente...</option>' + options;
}

function cambiarModoCargaHuesped(index, modo) {
    const searchWrap = document.getElementById(`huesped-db-search-wrap-${index}`);
    const selectWrap = document.getElementById(`huesped-db-select-wrap-${index}`);

    if (!searchWrap || !selectWrap) return;

    const mostrar = modo === 'db';
    searchWrap.style.display = mostrar ? 'block' : 'none';
    selectWrap.style.display = mostrar ? 'block' : 'none';

    if (mostrar) {
        renderClientesDB(index);
    }
}

function filtrarClientesDB(index) {
    const input = document.getElementById(`huesped-db-search-${index}`);
    renderClientesDB(index, input ? input.value : '');
}

function aplicarClienteDBAHuesped(index) {
    const select = document.getElementById(`huesped-db-select-${index}`);
    if (!select || !select.value) return;

    const cliente = clientesDB.find(c => String(c.id) === String(select.value));
    if (!cliente) return;

    const nombre = document.querySelector(`input[name="huespedes[${index}][nombre]"]`);
    const documento = document.querySelector(`input[name="huespedes[${index}][documento]"]`);
    const telefono = document.querySelector(`input[name="huespedes[${index}][telefono]"]`);
    const email = document.querySelector(`input[name="huespedes[${index}][email]"]`);

    if (nombre) nombre.value = cliente.nombre || '';
    if (documento) documento.value = cliente.documento || '';
    if (telefono) telefono.value = cliente.telefono || '';
    if (email) email.value = cliente.email || '';
}

function eliminarHuespedWalkin(index) {
    if (confirm('¿Eliminar este huésped?')) {
        document.getElementById(`huesped-walkin-${index}`).remove();
    }
}

// Inicializar al cargar
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($_GET['fecha_salida'])): ?>
    // Agregar primer huésped automáticamente
    agregarHuespedWalkin(true);
    
    // Agregar huéspedes adicionales según el número indicado
    const numHuespedes = parseInt('<?= $_GET['numero_huespedes'] ?? 1 ?>');
    for (let i = 1; i < numHuespedes; i++) {
        agregarHuespedWalkin(false);
    }

    document.getElementById('clienteExistente')?.addEventListener('change', syncTitularFromCliente);
    document.getElementById('clienteNombre')?.addEventListener('input', syncTitularFromCliente);
    const dniInputWalkin = document.getElementById('clienteDocumento');
    dniInputWalkin?.addEventListener('input', syncTitularFromCliente);
    dniInputWalkin?.addEventListener('blur', buscarClientePorDocumentoWalkin);
    dniInputWalkin?.focus();
    document.querySelector('input[name="cliente_telefono"]')?.addEventListener('input', syncTitularFromCliente);
    document.querySelector('input[name="cliente_email"]')?.addEventListener('input', syncTitularFromCliente);
    syncTitularFromCliente();
    
    // Calcular precio
    const habitaciones = document.querySelectorAll('input[name="habitacion_id"]');
    const lateCheckout = document.getElementById('lateCheckout');
    const noches = parseInt(document.getElementById('numeroNoches').textContent);
    
    function calcularTotal() {
        const habitacionSeleccionada = document.querySelector('input[name="habitacion_id"]:checked');
        if (!habitacionSeleccionada) return;
        
        const precio = parseFloat(habitacionSeleccionada.dataset.precio);
        const subtotal = precio * noches;
        let cargoExtra = 0;
        
        if (lateCheckout.checked) cargoExtra += 50;
        
        const total = subtotal + cargoExtra;
        
        document.getElementById('precioNoche').textContent = 'S/ ' + precio.toFixed(2);
        document.getElementById('subtotal').textContent = 'S/ ' + subtotal.toFixed(2);
        document.getElementById('cargoExtra').textContent = 'S/ ' + cargoExtra.toFixed(2);
        document.getElementById('precioTotal').textContent = 'S/ ' + total.toFixed(2);
        
        document.getElementById('cargoExtraRow').style.display = cargoExtra > 0 ? 'flex' : 'none';
    }
    
    habitaciones.forEach(hab => hab.addEventListener('change', calcularTotal));
    if (lateCheckout) lateCheckout.addEventListener('change', calcularTotal);
    <?php endif; ?>
});
</script>

<?php include BASE_PATH . "/views/hotel/_footer.php"; ?>
