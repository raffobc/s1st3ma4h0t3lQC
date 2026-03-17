<?php include BASE_PATH . "/views/hotel/_header.php"; ?>

<div class="container" style="max-width: 900px; margin: 30px auto;">
    <div class="card" style="padding: 28px;">
        <div style="display: flex; justify-content: space-between; align-items: start; gap: 20px; margin-bottom: 20px;">
            <div>
                <h1 style="margin: 0; color: #111827;">Recibo de Check-out</h1>
                <p style="margin: 8px 0 0; color: #6b7280;">Reserva #<?= (int)$reservation['id'] ?></p>
            </div>
            <div style="text-align: right; color: #374151; font-size: 14px;">
                <div><strong>Fecha:</strong> <?= date('d/m/Y H:i') ?></div>
                <div><strong>Estado:</strong> <?= htmlspecialchars(ucfirst($reservation['estado'])) ?></div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
            <div class="box">
                <h3>Cliente</h3>
                <p><strong><?= htmlspecialchars($reservation['cliente_nombre']) ?></strong></p>
                <p>Doc: <?= htmlspecialchars($reservation['cliente_documento']) ?></p>
                <p>Email: <?= htmlspecialchars($reservation['cliente_email'] ?? '-') ?></p>
                <p>Telefono: <?= htmlspecialchars($reservation['cliente_telefono'] ?? '-') ?></p>
            </div>
            <div class="box">
                <h3>Estadia</h3>
                <p>Habitacion: #<?= htmlspecialchars($reservation['numero_habitacion']) ?> (<?= htmlspecialchars($reservation['tipo_habitacion']) ?>)</p>
                <p>Entrada: <?= date('d/m/Y', strtotime($reservation['fecha_entrada'])) ?></p>
                <p>Salida: <?= date('d/m/Y', strtotime($reservation['fecha_salida'])) ?></p>
            </div>
        </div>

        <div class="box" style="margin-bottom: 20px;">
            <h3>Resumen</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px 0; color: #6b7280;">Total reserva</td>
                    <td style="padding: 8px 0; text-align: right;"><strong>S/ <?= number_format($totalReserva, 2) ?></strong></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #6b7280;">Total pagado</td>
                    <td style="padding: 8px 0; text-align: right;"><strong>S/ <?= number_format($totalPagado, 2) ?></strong></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #6b7280;">Saldo</td>
                    <td style="padding: 8px 0; text-align: right;"><strong>S/ <?= number_format($balance, 2) ?></strong></td>
                </tr>
            </table>
        </div>

        <div class="box" style="margin-bottom: 20px;">
            <h3>Pagos</h3>
            <?php if (empty($payments)): ?>
                <p style="color: #6b7280;">No hay pagos registrados.</p>
            <?php else: ?>
                <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                    <thead>
                        <tr>
                            <th style="text-align: left; padding: 8px; border-bottom: 1px solid #e5e7eb;">Fecha</th>
                            <th style="text-align: left; padding: 8px; border-bottom: 1px solid #e5e7eb;">Metodo</th>
                            <th style="text-align: left; padding: 8px; border-bottom: 1px solid #e5e7eb;">Comprobante</th>
                            <th style="text-align: right; padding: 8px; border-bottom: 1px solid #e5e7eb;">Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td style="padding: 8px; border-bottom: 1px solid #f3f4f6;"><?= date('d/m/Y H:i', strtotime($payment['fecha_pago'])) ?></td>
                                <td style="padding: 8px; border-bottom: 1px solid #f3f4f6;"><?= htmlspecialchars(ucfirst($payment['metodo_pago'])) ?></td>
                                <td style="padding: 8px; border-bottom: 1px solid #f3f4f6;"><?= htmlspecialchars($payment['comprobante'] ?? '-') ?></td>
                                <td style="padding: 8px; border-bottom: 1px solid #f3f4f6; text-align: right;">S/ <?= number_format((float)$payment['monto'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div style="display: flex; gap: 10px;">
            <a href="<?= BASE_URL ?>/hotel/reservas" class="btn">Volver a reservas</a>
            <button onclick="window.print()" class="btn btn-primary">Imprimir</button>
        </div>
    </div>
</div>

<style>
.box {
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 14px;
}
.box h3 { margin: 0 0 10px; color: #111827; }
.box p { margin: 6px 0; color: #374151; }
.btn {
    padding: 10px 14px;
    border-radius: 8px;
    text-decoration: none;
    border: none;
    background: #e5e7eb;
    color: #111827;
    cursor: pointer;
    font-weight: 700;
}
.btn-primary {
    background: #2563eb;
    color: white;
}
@media print {
    .navbar, .user-menu, .btn { display: none !important; }
}
</style>

<?php include BASE_PATH . "/views/hotel/_footer.php"; ?>
