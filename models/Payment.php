<?php
class Payment {
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

    public function getPaymentsByReservation(int $reservationId): array {
        $stmt = $this->db->prepare("
            SELECT p.*,
                   u.nombre as usuario_nombre,
                   r.total as reserva_total,
                   (r.total - COALESCE(SUM(p2.monto), 0)) as saldo_pendiente
            FROM pagos p
            LEFT JOIN usuarios u ON p.usuario_id = u.id
            LEFT JOIN reservas r ON p.reserva_id = r.id
            LEFT JOIN pagos p2 ON p2.reserva_id = r.id AND p2.id <= p.id
            WHERE p.reserva_id = ?
            GROUP BY p.id
            ORDER BY p.fecha_pago DESC
        ");
        $stmt->execute([$reservationId]);
        return $stmt->fetchAll();
    }

    public function createPayment(array $data): int|false {
        $stmt = $this->db->prepare("
            INSERT INTO pagos (
                reserva_id, monto, metodo_pago, fecha_pago, comprobante, observaciones, usuario_id
            ) VALUES (?, ?, ?, NOW(), ?, ?, ?)
        ");

        try {
            $stmt->execute([
                $data['reserva_id'],
                $data['monto'],
                $data['metodo_pago'],
                $data['comprobante'] ?? null,
                $data['observaciones'] ?? null,
                $_SESSION['hotel_user_id'] ?? null
            ]);
            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creando pago: " . $e->getMessage());
            return false;
        }
    }

    public function getPaymentById(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT p.*,
                   u.nombre as usuario_nombre,
                   r.id as reserva_id, r.fecha_entrada, r.fecha_salida,
                   h.numero_habitacion,
                   c.nombre as cliente_nombre
            FROM pagos p
            LEFT JOIN usuarios u ON p.usuario_id = u.id
            JOIN reservas r ON p.reserva_id = r.id
            JOIN habitaciones h ON r.habitacion_id = h.id
            JOIN clientes c ON r.cliente_id = c.id
            WHERE p.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function updatePayment(int $id, array $data): bool {
        $fields = [];
        $values = [];

        if (isset($data['monto'])) {
            $fields[] = "monto = ?";
            $values[] = $data['monto'];
        }
        if (isset($data['metodo_pago'])) {
            $fields[] = "metodo_pago = ?";
            $values[] = $data['metodo_pago'];
        }
        if (isset($data['comprobante'])) {
            $fields[] = "comprobante = ?";
            $values[] = $data['comprobante'];
        }
        if (isset($data['observaciones'])) {
            $fields[] = "observaciones = ?";
            $values[] = $data['observaciones'];
        }

        if (empty($fields)) {
            return false;
        }

        $values[] = $id;
        $sql = "UPDATE pagos SET " . implode(", ", $fields) . " WHERE id = ?";

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($values);
        } catch (PDOException $e) {
            error_log("Error actualizando pago: " . $e->getMessage());
            return false;
        }
    }

    public function deletePayment(int $id): bool {
        try {
            $stmt = $this->db->prepare("DELETE FROM pagos WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error eliminando pago: " . $e->getMessage());
            return false;
        }
    }

    public function getAllPayments(string $startDate = null, string $endDate = null, string $method = null): array {
        $conditions = [];
        $params = [];

        if ($startDate) {
            $conditions[] = "DATE(p.fecha_pago) >= ?";
            $params[] = $startDate;
        }

        if ($endDate) {
            $conditions[] = "DATE(p.fecha_pago) <= ?";
            $params[] = $endDate;
        }

        if ($method) {
            $conditions[] = "p.metodo_pago = ?";
            $params[] = $method;
        }

        $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

        $sql = "
            SELECT p.*,
                   u.nombre as usuario_nombre,
                   r.fecha_entrada, r.fecha_salida,
                   h.numero_habitacion,
                   c.nombre as cliente_nombre
            FROM pagos p
            LEFT JOIN usuarios u ON p.usuario_id = u.id
            JOIN reservas r ON p.reserva_id = r.id
            JOIN habitaciones h ON r.habitacion_id = h.id
            JOIN clientes c ON r.cliente_id = c.id
            $whereClause
            ORDER BY p.fecha_pago DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getPaymentStats(string $period = 'month'): array {
        $dateCondition = match($period) {
            'day' => "DATE(fecha_pago) = CURDATE()",
            'week' => "YEARWEEK(fecha_pago) = YEARWEEK(CURDATE())",
            'month' => "YEAR(fecha_pago) = YEAR(CURDATE()) AND MONTH(fecha_pago) = MONTH(CURDATE())",
            'year' => "YEAR(fecha_pago) = YEAR(CURDATE())",
            default => "YEAR(fecha_pago) = YEAR(CURDATE()) AND MONTH(fecha_pago) = MONTH(CURDATE())"
        };

        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) as total_pagos,
                COALESCE(SUM(monto), 0) as total_monto,
                AVG(monto) as promedio_pago,
                metodo_pago,
                COUNT(*) as cantidad_por_metodo
            FROM pagos
            WHERE $dateCondition
            GROUP BY metodo_pago
            ORDER BY total_monto DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getRevenueByPeriod(string $startDate, string $endDate): array {
        $stmt = $this->db->prepare("
            SELECT
                DATE(fecha_pago) as fecha,
                COALESCE(SUM(monto), 0) as ingresos_diarios,
                metodo_pago
            FROM pagos
            WHERE DATE(fecha_pago) BETWEEN ? AND ?
            GROUP BY DATE(fecha_pago), metodo_pago
            ORDER BY fecha ASC
        ");
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetchAll();
    }

    public function getOutstandingBalances(): array {
        $stmt = $this->db->query("
            SELECT r.id, r.total,
                   COALESCE(SUM(p.monto), 0) as pagado,
                   (r.total - COALESCE(SUM(p.monto), 0)) as pendiente,
                   h.numero_habitacion,
                   c.nombre as cliente_nombre,
                   r.fecha_entrada, r.fecha_salida
            FROM reservas r
            JOIN habitaciones h ON r.habitacion_id = h.id
            JOIN clientes c ON r.cliente_id = c.id
            LEFT JOIN pagos p ON r.id = p.reserva_id
            WHERE r.estado IN ('reservada', 'ocupada')
            GROUP BY r.id
            HAVING pendiente > 0
            ORDER BY pendiente DESC
        ");
        return $stmt->fetchAll();
    }

    public function validatePaymentData(array $data): array {
        $errors = [];

        if (!isset($data['monto']) || $data['monto'] <= 0) {
            $errors[] = "El monto del pago debe ser mayor a 0";
        }

        if (!isset($data['metodo_pago']) || empty($data['metodo_pago'])) {
            $errors[] = "El método de pago es obligatorio";
        }

        $validMethods = ['efectivo', 'tarjeta', 'transferencia', 'otro'];
        if (!in_array($data['metodo_pago'], $validMethods)) {
            $errors[] = "Método de pago no válido";
        }

        if (!isset($data['reserva_id']) || $data['reserva_id'] <= 0) {
            $errors[] = "La reserva es obligatoria";
        }

        return $errors;
    }

    public function getTotalRevenue(string $startDate = null, string $endDate = null): float {
        $conditions = [];
        $params = [];

        if ($startDate) {
            $conditions[] = "DATE(fecha_pago) >= ?";
            $params[] = $startDate;
        }

        if ($endDate) {
            $conditions[] = "DATE(fecha_pago) <= ?";
            $params[] = $endDate;
        }

        $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

        $stmt = $this->db->prepare("SELECT COALESCE(SUM(monto), 0) as total FROM pagos $whereClause");
        $stmt->execute($params);
        return (float)$stmt->fetch()['total'];
    }
}
?>
