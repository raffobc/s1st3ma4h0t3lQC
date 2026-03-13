<?php
class Guest {
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

    public function getGuestsByReservation(int $reservationId): array {
        $stmt = $this->db->prepare("
            SELECT * FROM huespedes
            WHERE reserva_id = ?
            ORDER BY created_at ASC
        ");
        $stmt->execute([$reservationId]);
        return $stmt->fetchAll();
    }

    public function createGuest(array $data): int|false {
        $stmt = $this->db->prepare("
            INSERT INTO huespedes (
                reserva_id, nombre, documento, fecha_nacimiento, telefono, email
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");

        try {
            $stmt->execute([
                $data['reserva_id'],
                $data['nombre'],
                $data['documento'],
                $data['fecha_nacimiento'] ?? null,
                $data['telefono'] ?? null,
                $data['email'] ?? null
            ]);
            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creando huésped: " . $e->getMessage());
            return false;
        }
    }

    public function updateGuest(int $id, array $data): bool {
        $fields = [];
        $values = [];

        if (isset($data['nombre'])) {
            $fields[] = "nombre = ?";
            $values[] = $data['nombre'];
        }
        if (isset($data['documento'])) {
            $fields[] = "documento = ?";
            $values[] = $data['documento'];
        }
        if (isset($data['fecha_nacimiento'])) {
            $fields[] = "fecha_nacimiento = ?";
            $values[] = $data['fecha_nacimiento'];
        }
        if (isset($data['telefono'])) {
            $fields[] = "telefono = ?";
            $values[] = $data['telefono'];
        }
        if (isset($data['email'])) {
            $fields[] = "email = ?";
            $values[] = $data['email'];
        }

        if (empty($fields)) {
            return false;
        }

        $values[] = $id;
        $sql = "UPDATE huespedes SET " . implode(", ", $fields) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ?";

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($values);
        } catch (PDOException $e) {
            error_log("Error actualizando huésped: " . $e->getMessage());
            return false;
        }
    }

    public function deleteGuest(int $id): bool {
        try {
            $stmt = $this->db->prepare("DELETE FROM huespedes WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error eliminando huésped: " . $e->getMessage());
            return false;
        }
    }

    public function saveGuestsForReservation(int $reservationId, array $guests): bool {
        $this->db->beginTransaction();

        try {
            // Eliminar huéspedes existentes
            $stmt = $this->db->prepare("DELETE FROM huespedes WHERE reserva_id = ?");
            $stmt->execute([$reservationId]);

            // Insertar nuevos huéspedes
            $stmt = $this->db->prepare("
                INSERT INTO huespedes (
                    reserva_id, nombre, documento, fecha_nacimiento, telefono, email
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");

            foreach ($guests as $guest) {
                $stmt->execute([
                    $reservationId,
                    $guest['nombre'],
                    $guest['documento'],
                    $guest['fecha_nacimiento'] ?? null,
                    $guest['telefono'] ?? null,
                    $guest['email'] ?? null
                ]);
            }

            $this->db->commit();
            return true;

        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error guardando huéspedes: " . $e->getMessage());
            return false;
        }
    }

    public function getGuestById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM huespedes WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function getCurrentGuests(): array {
        $stmt = $this->db->query("
            SELECT h.*,
                   r.fecha_entrada, r.fecha_salida,
                   hab.numero_habitacion,
                   c.nombre as cliente_reserva
            FROM huespedes h
            JOIN reservas r ON h.reserva_id = r.id
            JOIN habitaciones hab ON r.habitacion_id = hab.id
            JOIN clientes c ON r.cliente_id = c.id
            WHERE r.estado = 'ocupada'
            ORDER BY hab.numero_habitacion ASC, h.nombre ASC
        ");
        return $stmt->fetchAll();
    }

    public function getGuestHistory(string $document = null, string $name = null): array {
        $conditions = [];
        $params = [];

        if ($document) {
            $conditions[] = "h.documento LIKE ?";
            $params[] = "%$document%";
        }

        if ($name) {
            $conditions[] = "h.nombre LIKE ?";
            $params[] = "%$name%";
        }

        $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

        $sql = "
            SELECT h.*,
                   r.fecha_entrada, r.fecha_salida, r.estado as estado_reserva,
                   hab.numero_habitacion,
                   c.nombre as cliente_reserva
            FROM huespedes h
            JOIN reservas r ON h.reserva_id = r.id
            JOIN habitaciones hab ON r.habitacion_id = hab.id
            JOIN clientes c ON r.cliente_id = c.id
            $whereClause
            ORDER BY h.created_at DESC
            LIMIT 100
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getGuestStats(): array {
        $stmt = $this->db->query("
            SELECT
                COUNT(*) as total_huespedes,
                COUNT(DISTINCT reserva_id) as reservas_con_huespedes,
                AVG(TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE())) as edad_promedio
            FROM huespedes
            WHERE fecha_nacimiento IS NOT NULL
        ");
        return $stmt->fetch();
    }

    public function validateGuestData(array $guest): array {
        $errors = [];

        if (empty($guest['nombre'])) {
            $errors[] = "El nombre del huésped es obligatorio";
        }

        if (empty($guest['documento'])) {
            $errors[] = "El documento del huésped es obligatorio";
        } elseif (strlen($guest['documento']) < 8) {
            $errors[] = "El documento debe tener al menos 8 caracteres";
        }

        if (!empty($guest['email']) && !filter_var($guest['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "El email del huésped no es válido";
        }

        if (!empty($guest['fecha_nacimiento'])) {
            $birthDate = new DateTime($guest['fecha_nacimiento']);
            $today = new DateTime();
            $age = $today->diff($birthDate)->y;

            if ($age < 0 || $age > 120) {
                $errors[] = "La fecha de nacimiento no es válida";
            }
        }

        return $errors;
    }
}
?>
