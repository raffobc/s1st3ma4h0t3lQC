<?php
class Reservation {
    private PDO $db;

    public function __construct() {
        $this->db = new PDO(
            "mysql:host=localhost;dbname=hotel_master;charset=utf8mb4",
            'root',
            '',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    }

    public function getAllReservations(string $filter = 'all'): array {
        $sql = "
            SELECT r.*,
                   h.numero_habitacion, h.tipo as tipo_habitacion, h.precio_noche,
                   c.nombre as cliente_nombre, c.documento as cliente_documento,
                   c.email as cliente_email, c.telefono as cliente_telefono,
                   COALESCE(SUM(p.monto), 0) as total_pagado,
                   (r.total - COALESCE(SUM(p.monto), 0)) as saldo_pendiente
            FROM reservas r
            JOIN habitaciones h ON r.habitacion_id = h.id
            JOIN clientes c ON r.cliente_id = c.id
            LEFT JOIN pagos p ON r.id = p.reserva_id
        ";

        $conditions = [];
        $params = [];

        if ($filter === 'active') {
            $conditions[] = "r.estado IN ('reservada', 'ocupada')";
        } elseif ($filter === 'finished') {
            $conditions[] = "r.estado = 'finalizada'";
        } elseif ($filter === 'cancelled') {
            $conditions[] = "r.estado = 'cancelada'";
        } elseif ($filter === 'upcoming') {
            $conditions[] = "r.fecha_entrada > CURDATE() AND r.estado = 'reservada'";
        } elseif ($filter === 'current') {
            $conditions[] = "r.fecha_entrada <= CURDATE() AND r.fecha_salida > CURDATE() AND r.estado IN ('reservada', 'ocupada')";
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $sql .= " GROUP BY r.id ORDER BY r.fecha_entrada DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getReservationById(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT r.*,
                   h.numero_habitacion, h.tipo as tipo_habitacion, h.precio_noche,
                   c.nombre as cliente_nombre, c.documento as cliente_documento,
                   c.email as cliente_email, c.telefono as cliente_telefono,
                   COALESCE(SUM(p.monto), 0) as total_pagado,
                   (r.total - COALESCE(SUM(p.monto), 0)) as saldo_pendiente
            FROM reservas r
            JOIN habitaciones h ON r.habitacion_id = h.id
            JOIN clientes c ON r.cliente_id = c.id
            LEFT JOIN pagos p ON r.id = p.reserva_id
            WHERE r.id = ?
            GROUP BY r.id
        ");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function createReservation(array $data): int|false {
        $this->db->beginTransaction();

        try {
            // Calcular el total
            $fechaEntrada = new DateTime($data['fecha_entrada']);
            $fechaSalida = new DateTime($data['fecha_salida']);
            $noches = $fechaEntrada->diff($fechaSalida)->days;

            // Obtener precio de la habitación
            $stmt = $this->db->prepare("SELECT precio_noche FROM habitaciones WHERE id = ?");
            $stmt->execute([$data['habitacion_id']]);
            $precioNoche = $stmt->fetch()['precio_noche'];

            $total = $noches * $precioNoche;

            // Crear la reserva
            $stmt = $this->db->prepare("
                INSERT INTO reservas (
                    habitacion_id, cliente_id, fecha_entrada, fecha_salida,
                    fecha_checkin, fecha_checkout, estado, total, adelanto, observaciones
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $data['habitacion_id'],
                $data['cliente_id'],
                $data['fecha_entrada'],
                $data['fecha_salida'],
                null, // fecha_checkin
                null, // fecha_checkout
                'reservada',
                $total,
                $data['adelanto'] ?? 0,
                $data['observaciones'] ?? null
            ]);

            $reservationId = (int)$this->db->lastInsertId();

            // Si hay adelanto, registrar el pago
            if (($data['adelanto'] ?? 0) > 0) {
                $this->addPayment($reservationId, [
                    'monto' => $data['adelanto'],
                    'metodo_pago' => $data['metodo_pago'] ?? 'efectivo',
                    'observaciones' => 'Adelanto reserva'
                ]);
            }

            $this->db->commit();
            return $reservationId;

        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error creando reserva: " . $e->getMessage());
            return false;
        }
    }

    public function updateReservation(int $id, array $data): bool {
        $fields = [];
        $values = [];

        if (isset($data['fecha_entrada'])) {
            $fields[] = "fecha_entrada = ?";
            $values[] = $data['fecha_entrada'];
        }
        if (isset($data['fecha_salida'])) {
            $fields[] = "fecha_salida = ?";
            $values[] = $data['fecha_salida'];
        }
        if (isset($data['estado'])) {
            $fields[] = "estado = ?";
            $values[] = $data['estado'];
        }
        if (isset($data['total'])) {
            $fields[] = "total = ?";
            $values[] = $data['total'];
        }
        if (isset($data['adelanto'])) {
            $fields[] = "adelanto = ?";
            $values[] = $data['adelanto'];
        }
        if (isset($data['observaciones'])) {
            $fields[] = "observaciones = ?";
            $values[] = $data['observaciones'];
        }

        if (empty($fields)) {
            return false;
        }

        $values[] = $id;
        $sql = "UPDATE reservas SET " . implode(", ", $fields) . " WHERE id = ?";

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($values);
        } catch (PDOException $e) {
            error_log("Error actualizando reserva: " . $e->getMessage());
            return false;
        }
    }

    public function cancelReservation(int $id): bool {
        try {
            $stmt = $this->db->prepare("UPDATE reservas SET estado = 'cancelada' WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error cancelando reserva: " . $e->getMessage());
            return false;
        }
    }

    public function checkIn(int $id): bool {
        $this->db->beginTransaction();

        try {
            // Actualizar reserva
            $stmt = $this->db->prepare("
                UPDATE reservas
                SET estado = 'ocupada', fecha_checkin = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$id]);

            // Actualizar estado de habitación
            $stmt = $this->db->prepare("
                UPDATE habitaciones
                SET estado = 'ocupada'
                WHERE id = (SELECT habitacion_id FROM reservas WHERE id = ?)
            ");
            $stmt->execute([$id]);

            $this->db->commit();
            return true;

        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error en check-in: " . $e->getMessage());
            return false;
        }
    }

    public function checkOut(int $id): bool {
        $this->db->beginTransaction();

        try {
            // Obtener información de la reserva
            $reservation = $this->getReservationById($id);
            if (!$reservation) {
                throw new Exception("Reserva no encontrada");
            }

            // Calcular total final (incluyendo servicios extras si los hay)
            $totalFinal = $reservation['total'];

            // Actualizar reserva
            $stmt = $this->db->prepare("
                UPDATE reservas
                SET estado = 'finalizada', fecha_checkout = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$id]);

            // Cambiar habitación a limpieza
            $stmt = $this->db->prepare("
                UPDATE habitaciones
                SET estado = 'limpieza'
                WHERE id = ?
            ");
            $stmt->execute([$reservation['habitacion_id']]);

            $this->db->commit();
            return true;

        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error en check-out: " . $e->getMessage());
            return false;
        }
    }

    public function addPayment(int $reservationId, array $data): int|false {
        $stmt = $this->db->prepare("
            INSERT INTO pagos (
                reserva_id, monto, metodo_pago, fecha_pago, observaciones, usuario_id
            ) VALUES (?, ?, ?, NOW(), ?, ?)
        ");

        try {
            $stmt->execute([
                $reservationId,
                $data['monto'],
                $data['metodo_pago'],
                $data['observaciones'] ?? null,
                $_SESSION['hotel_user_id'] ?? null
            ]);
            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error agregando pago: " . $e->getMessage());
            return false;
        }
    }

    public function getReservationPayments(int $reservationId): array {
        $stmt = $this->db->prepare("
            SELECT p.*, u.nombre as usuario_nombre
            FROM pagos p
            LEFT JOIN usuarios u ON p.usuario_id = u.id
            WHERE p.reserva_id = ?
            ORDER BY p.fecha_pago DESC
        ");
        $stmt->execute([$reservationId]);
        return $stmt->fetchAll();
    }

    public function getReservationStats(): array {
        $stmt = $this->db->query("
            SELECT
                COUNT(*) as total_reservas,
                SUM(CASE WHEN estado = 'reservada' THEN 1 ELSE 0 END) as reservas_activas,
                SUM(CASE WHEN estado = 'ocupada' THEN 1 ELSE 0 END) as habitaciones_ocupadas,
                SUM(CASE WHEN estado = 'finalizada' THEN 1 ELSE 0 END) as reservas_completadas,
                SUM(CASE WHEN estado = 'cancelada' THEN 1 ELSE 0 END) as reservas_canceladas,
                COALESCE(SUM(total), 0) as ingresos_totales,
                COALESCE(SUM(adelanto), 0) as adelantos_totales
            FROM reservas
            WHERE YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())
        ");
        return $stmt->fetch();
    }

    public function getUpcomingCheckIns(): array {
        $stmt = $this->db->query("
            SELECT r.*,
                   h.numero_habitacion,
                   c.nombre as cliente_nombre, c.telefono as cliente_telefono
            FROM reservas r
            JOIN habitaciones h ON r.habitacion_id = h.id
            JOIN clientes c ON r.cliente_id = c.id
            WHERE r.fecha_entrada = CURDATE()
            AND r.estado = 'reservada'
            AND r.fecha_checkin IS NULL
            ORDER BY r.fecha_entrada ASC
        ");
        return $stmt->fetchAll();
    }

    public function getUpcomingCheckOuts(): array {
        $stmt = $this->db->query("
            SELECT r.*,
                   h.numero_habitacion,
                   c.nombre as cliente_nombre, c.telefono as cliente_telefono
            FROM reservas r
            JOIN habitaciones h ON r.habitacion_id = h.id
            JOIN clientes c ON r.cliente_id = c.id
            WHERE r.fecha_salida = CURDATE()
            AND r.estado = 'ocupada'
            AND r.fecha_checkout IS NULL
            ORDER BY r.fecha_salida ASC
        ");
        return $stmt->fetchAll();
    }
}
?>
