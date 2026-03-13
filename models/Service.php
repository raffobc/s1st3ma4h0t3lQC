<?php
class Service {
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

    public function getAllServices(): array {
        $stmt = $this->db->query("SELECT * FROM servicios ORDER BY nombre ASC");
        return $stmt->fetchAll();
    }

    public function getActiveServices(): array {
        $stmt = $this->db->query("SELECT * FROM servicios WHERE activo = 1 ORDER BY nombre ASC");
        return $stmt->fetchAll();
    }

    public function getServiceById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM servicios WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function createService(array $data): int|false {
        $stmt = $this->db->prepare("
            INSERT INTO servicios (
                nombre, descripcion, precio, activo
            ) VALUES (?, ?, ?, ?)
        ");

        try {
            $stmt->execute([
                $data['nombre'],
                $data['descripcion'] ?? null,
                $data['precio'],
                $data['activo'] ?? 1
            ]);
            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creando servicio: " . $e->getMessage());
            return false;
        }
    }

    public function updateService(int $id, array $data): bool {
        $fields = [];
        $values = [];

        if (isset($data['nombre'])) {
            $fields[] = "nombre = ?";
            $values[] = $data['nombre'];
        }
        if (isset($data['descripcion'])) {
            $fields[] = "descripcion = ?";
            $values[] = $data['descripcion'];
        }
        if (isset($data['precio'])) {
            $fields[] = "precio = ?";
            $values[] = $data['precio'];
        }
        if (isset($data['activo'])) {
            $fields[] = "activo = ?";
            $values[] = $data['activo'];
        }

        if (empty($fields)) {
            return false;
        }

        $values[] = $id;
        $sql = "UPDATE servicios SET " . implode(", ", $fields) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ?";

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($values);
        } catch (PDOException $e) {
            error_log("Error actualizando servicio: " . $e->getMessage());
            return false;
        }
    }

    public function deleteService(int $id): bool {
        // Verificar si el servicio está siendo usado en reservas activas
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count FROM reserva_servicios
            WHERE servicio_id = ?
        ");
        $stmt->execute([$id]);
        $count = $stmt->fetch()['count'];

        if ($count > 0) {
            // En lugar de eliminar, desactivar
            return $this->updateService($id, ['activo' => 0]);
        }

        try {
            $stmt = $this->db->prepare("DELETE FROM servicios WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error eliminando servicio: " . $e->getMessage());
            return false;
        }
    }

    public function toggleServiceStatus(int $id): bool {
        $service = $this->getServiceById($id);
        if (!$service) {
            return false;
        }

        $newStatus = $service['activo'] ? 0 : 1;
        return $this->updateService($id, ['activo' => $newStatus]);
    }

    public function addServiceToReservation(int $reservationId, int $serviceId, int $quantity = 1, string $notes = null): int|false {
        // Verificar que la reserva existe y está activa
        $stmt = $this->db->prepare("SELECT id FROM reservas WHERE id = ? AND estado IN ('reservada', 'ocupada')");
        $stmt->execute([$reservationId]);
        if (!$stmt->fetch()) {
            return false;
        }

        $stmt = $this->db->prepare("
            INSERT INTO reserva_servicios (
                reserva_id, servicio_id, cantidad, precio_unitario, notas
            ) VALUES (?, ?, ?, (SELECT precio FROM servicios WHERE id = ?), ?)
        ");

        try {
            $stmt->execute([$reservationId, $serviceId, $quantity, $serviceId, $notes]);
            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error agregando servicio a reserva: " . $e->getMessage());
            return false;
        }
    }

    public function getServicesByReservation(int $reservationId): array {
        $stmt = $this->db->prepare("
            SELECT rs.*,
                   s.nombre, s.descripcion,
                   (rs.cantidad * rs.precio_unitario) as total
            FROM reserva_servicios rs
            JOIN servicios s ON rs.servicio_id = s.id
            WHERE rs.reserva_id = ?
            ORDER BY rs.created_at DESC
        ");
        $stmt->execute([$reservationId]);
        return $stmt->fetchAll();
    }

    public function removeServiceFromReservation(int $reservationServiceId): bool {
        try {
            $stmt = $this->db->prepare("DELETE FROM reserva_servicios WHERE id = ?");
            return $stmt->execute([$reservationServiceId]);
        } catch (PDOException $e) {
            error_log("Error removiendo servicio de reserva: " . $e->getMessage());
            return false;
        }
    }

    public function getServiceUsageStats(string $period = 'month'): array {
        $dateCondition = match($period) {
            'day' => "DATE(rs.created_at) = CURDATE()",
            'week' => "YEARWEEK(rs.created_at) = YEARWEEK(CURDATE())",
            'month' => "YEAR(rs.created_at) = YEAR(CURDATE()) AND MONTH(rs.created_at) = MONTH(CURDATE())",
            'year' => "YEAR(rs.created_at) = YEAR(CURDATE())",
            default => "YEAR(rs.created_at) = YEAR(CURDATE()) AND MONTH(rs.created_at) = MONTH(CURDATE())"
        };

        $stmt = $this->db->prepare("
            SELECT s.nombre,
                   SUM(rs.cantidad) as total_cantidad,
                   SUM(rs.cantidad * rs.precio_unitario) as total_ingresos,
                   COUNT(DISTINCT rs.reserva_id) as reservas_que_lo_usaron
            FROM reserva_servicios rs
            JOIN servicios s ON rs.servicio_id = s.id
            WHERE $dateCondition
            GROUP BY s.id, s.nombre
            ORDER BY total_ingresos DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getPopularServices(int $limit = 10): array {
        $stmt = $this->db->prepare("
            SELECT s.*,
                   SUM(rs.cantidad) as veces_usado,
                   AVG(rs.cantidad) as cantidad_promedio
            FROM servicios s
            LEFT JOIN reserva_servicios rs ON s.id = rs.servicio_id
            GROUP BY s.id
            ORDER BY veces_usado DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    public function validateServiceData(array $data): array {
        $errors = [];

        if (empty($data['nombre'])) {
            $errors[] = "El nombre del servicio es obligatorio";
        }

        if (!isset($data['precio']) || $data['precio'] < 0) {
            $errors[] = "El precio debe ser mayor o igual a 0";
        }

        return $errors;
    }

    public function searchServices(string $query): array {
        $stmt = $this->db->prepare("
            SELECT * FROM servicios
            WHERE (nombre LIKE ? OR descripcion LIKE ?) AND activo = 1
            ORDER BY nombre ASC
        ");
        $searchTerm = "%$query%";
        $stmt->execute([$searchTerm, $searchTerm]);
        return $stmt->fetchAll();
    }
}
?>
