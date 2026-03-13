<?php include BASE_PATH . "/views/hotel/_header.php"; ?>

<div class="page-header">
    <div>
        <h1>👤 <?= htmlspecialchars($client['nombre']) ?></h1>
        <p class="subtitle">Perfil completo del cliente</p>
    </div>
    <a href="<?= BASE_URL ?>/hotel/clientes" class="btn-secondary">← Volver</a>
</div>

<div class="content-grid">
    <div>
        <div class="card" style="margin-bottom: 20px;">
            <h3 class="card-title">📋 Información Personal</h3>
            
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Documento</div>
                    <div class="info-value"><?= htmlspecialchars($client['documento']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Email</div>
                    <div class="info-value"><?= htmlspecialchars($client['email']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Teléfono</div>
                    <div class="info-value"><?= htmlspecialchars($client['telefono']) ?></div>
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
                            <div class="timeline-marker status-<?= $reserva['estado'] ?>"></div>
                            <div class="timeline-content">
                                <div class="timeline-header">
                                    <span class="room-badge">🏠 <?= htmlspecialchars($reserva['numero_habitacion']) ?></span>
                                    <span class="status-badge status-<?= $reserva['estado'] ?>">
                                        <?= ucfirst($reserva['estado']) ?>
                                    </span>
                                </div>
                                <div class="timeline-body">
                                    <div class="timeline-dates">
                                        📅 <?= date('d/m/Y', strtotime($reserva['fecha_entrada'])) ?> 
                                        → <?= date('d/m/Y', strtotime($reserva['fecha_salida'])) ?>
                                    </div>
                                    <div class="timeline-price">
                                        💰 S/ <?= number_format($reserva['precio_total'], 2) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include BASE_PATH . "/views/hotel/_footer.php"; ?>
