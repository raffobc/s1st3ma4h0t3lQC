<?php

class Room {
    private PDO $db;

    public function __construct() {
        $this->db = MasterDatabase::getConnection();
    }

    public function getAllRooms(): array {
        $stmt = $this->db->prepare("
            SELECT h.*,
                   CASE
                       WHEN h.estado = 'ocupada' THEN 'ocupada'
                       WHEN EXISTS (
                           SELECT 1 FROM reservas r
                           WHERE r.habitacion_id = h.id
                           AND r.estado IN ('reservada', 'ocupada')
                           AND r.fecha_entrada <= DATE('now')
                           AND r.fecha_salida > DATE('now')
                       ) THEN 'reservada'
                       ELSE h.estado
                   END as estado_actual
            FROM habitaciones h
            ORDER BY h.numero_habitacion ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getRoomById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM habitaciones WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function getRoomByNumber(string $numero): ?array {
        $stmt = $this->db->prepare("SELECT * FROM habitaciones WHERE numero_habitacion = ?");
        $stmt->execute([$numero]);
        return $stmt->fetch() ?: null;
    }

    public function createRoom(array $data): int|false {
        $stmt = $this->db->prepare("
            INSERT INTO habitaciones (
                numero_habitacion, tipo, precio_noche, capacidad, descripcion, estado
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");

        try {
            $stmt->execute([
                $data['numero_habitacion'],
                $data['tipo'],
                $data['precio_noche'],
                $data['capacidad'] ?? 2,
                $data['descripcion'] ?? null,
                $data['estado'] ?? 'disponible'
            ]);
            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creando habitación: " . $e->getMessage());
            return false;
        }
    }

    public function updateRoom(int $id, array $data): bool {
        $fields = [];
        $values = [];

        if (isset($data['numero_habitacion'])) {
            $fields[] = "numero_habitacion = ?";
            $values[] = $data['numero_habitacion'];
        }
        if (isset($data['tipo'])) {
            $fields[] = "tipo = ?";
            $values[] = $data['tipo'];
        }
        if (isset($data['precio_noche'])) {
            $fields[] = "precio_noche = ?";
            $values[] = $data['precio_noche'];
        }
        if (isset($data['capacidad'])) {
            $fields[] = "capacidad = ?";
            $values[] = $data['capacidad'];
        }
        if (isset($data['descripcion'])) {
            $fields[] = "descripcion = ?";
            $values[] = $data['descripcion'];
        }
        if (isset($data['estado'])) {
            $fields[] = "estado = ?";
            $values[] = $data['estado'];
        }

        if (empty($fields)) {
            return false;
        }

        $values[] = $id;
        $sql = "UPDATE habitaciones SET " . implode(", ", $fields) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ?";

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($values);
        } catch (PDOException $e) {
            error_log("Error actualizando habitación: " . $e->getMessage());
            return false;
        }
    }

    public function deleteRoom(int $id): bool {
        // Verificar si la habitación tiene reservas activas
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count FROM reservas
            WHERE habitacion_id = ? AND estado IN ('reservada', 'ocupada')
        ");
        $stmt->execute([$id]);
        $count = $stmt->fetch()['count'];

        if ($count > 0) {
            return false; // No se puede eliminar si tiene reservas activas
        }

        try {
            $stmt = $this->db->prepare("DELETE FROM habitaciones WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error eliminando habitación: " . $e->getMessage());
            return false;
        }
    }

    public function getAvailableRooms(string $fechaEntrada, string $fechaSalida, int $capacidad = 1): array {
        $stmt = $this->db->prepare("
            SELECT h.* FROM habitaciones h
            WHERE h.capacidad >= ?
            AND h.estado = 'disponible'
            AND h.id NOT IN (
                SELECT r.habitacion_id FROM reservas r
                WHERE r.estado IN ('reservada', 'ocupada')
                AND (
                    (r.fecha_entrada <= ? AND r.fecha_salida >= ?)
                    OR (r.fecha_entrada <= ? AND r.fecha_salida >= ?)
                    OR (r.fecha_entrada >= ? AND r.fecha_salida <= ?)
                )
            )
            ORDER BY h.numero_habitacion ASC
        ");

        $stmt->execute([$capacidad, $fechaSalida, $fechaEntrada, $fechaEntrada, $fechaSalida, $fechaEntrada, $fechaSalida]);
        return $stmt->fetchAll();
    }

    public function getRoomsByStatus(string $status): array {
        $stmt = $this->db->prepare("SELECT * FROM habitaciones WHERE estado = ? ORDER BY numero_habitacion ASC");
        $stmt->execute([$status]);
        return $stmt->fetchAll();
    }

    public function getRoomStats(): array {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN estado = 'disponible' THEN 1 ELSE 0 END) as disponibles,
                SUM(CASE WHEN estado = 'ocupada' THEN 1 ELSE 0 END) as ocupadas,
                SUM(CASE WHEN estado = 'reservada' THEN 1 ELSE 0 END) as reservadas,
                SUM(CASE WHEN estado IN ('limpieza', 'mantenimiento') THEN 1 ELSE 0 END) as mantenimiento
            FROM habitaciones
        ");
        $stmt->execute();
        return $stmt->fetch();
    }

    public function updateRoomStatus(int $id, string $status): bool {
        try {
            $stmt = $this->db->prepare("UPDATE habitaciones SET estado = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            return $stmt->execute([$status, $id]);
        } catch (PDOException $e) {
            error_log("Error actualizando estado de habitación: " . $e->getMessage());
            return false;
        }
    }
}
?>
