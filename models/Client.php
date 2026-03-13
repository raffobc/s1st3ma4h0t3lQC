<?php

class Client {
    private PDO $db;

    public function __construct() {
        $this->db = MasterDatabase::getConnection();
    }

    public function getAllClients(): array {
        $stmt = $this->db->query("
            SELECT c.*,
                   COUNT(DISTINCT r.id) as total_reservas,
                   COALESCE(SUM(p.monto), 0) as total_gastado,
                   MAX(r.created_at) as ultima_reserva
            FROM clientes c
            LEFT JOIN reservas r ON c.id = r.cliente_id
            LEFT JOIN pagos p ON r.id = p.reserva_id
            GROUP BY c.id
            ORDER BY c.created_at DESC
        ");
        return $stmt->fetchAll();
    }

    public function getClientById(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT c.*,
                   COUNT(DISTINCT r.id) as total_reservas,
                   COALESCE(SUM(p.monto), 0) as total_gastado,
                   MAX(r.created_at) as ultima_reserva
            FROM clientes c
            LEFT JOIN reservas r ON c.id = r.cliente_id
            LEFT JOIN pagos p ON r.id = p.reserva_id
            WHERE c.id = ?
            GROUP BY c.id
        ");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function getClientByDocument(string $document): ?array {
        $stmt = $this->db->prepare("SELECT * FROM clientes WHERE documento = ?");
        $stmt->execute([$document]);
        return $stmt->fetch() ?: null;
    }

    public function searchClients(string $query): array {
        $stmt = $this->db->prepare("
            SELECT c.*,
                   COUNT(DISTINCT r.id) as total_reservas,
                   COALESCE(SUM(p.monto), 0) as total_gastado
            FROM clientes c
            LEFT JOIN reservas r ON c.id = r.cliente_id
            LEFT JOIN pagos p ON r.id = p.reserva_id
            WHERE (c.nombre LIKE ? OR c.documento LIKE ? OR c.email LIKE ?)
            GROUP BY c.id
            ORDER BY c.nombre ASC
            LIMIT 50
        ");
        $searchTerm = "%$query%";
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
        return $stmt->fetchAll();
    }

    public function createClient(array $data): int|false {
        $stmt = $this->db->prepare("
            INSERT INTO clientes (
                nombre, documento, telefono, email, direccion
            ) VALUES (?, ?, ?, ?, ?)
        ");

        try {
            $stmt->execute([
                $data['nombre'],
                $data['documento'],
                $data['telefono'] ?? null,
                $data['email'] ?? null,
                $data['direccion'] ?? null
            ]);
            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creando cliente: " . $e->getMessage());
            return false;
        }
    }

    public function updateClient(int $id, array $data): bool {
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
        if (isset($data['telefono'])) {
            $fields[] = "telefono = ?";
            $values[] = $data['telefono'];
        }
        if (isset($data['email'])) {
            $fields[] = "email = ?";
            $values[] = $data['email'];
        }
        if (isset($data['direccion'])) {
            $fields[] = "direccion = ?";
            $values[] = $data['direccion'];
        }

        if (empty($fields)) {
            return false;
        }

        $values[] = $id;
        $sql = "UPDATE clientes SET " . implode(", ", $fields) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ?";

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($values);
        } catch (PDOException $e) {
            error_log("Error actualizando cliente: " . $e->getMessage());
            return false;
        }
    }

    public function deleteClient(int $id): bool {
        // Verificar si el cliente tiene reservas activas
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count FROM reservas
            WHERE cliente_id = ? AND estado IN ('reservada', 'ocupada')
        ");
        $stmt->execute([$id]);
        $count = $stmt->fetch()['count'];

        if ($count > 0) {
            return false; // No se puede eliminar si tiene reservas activas
        }

        try {
            $stmt = $this->db->prepare("DELETE FROM clientes WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error eliminando cliente: " . $e->getMessage());
            return false;
        }
    }

    public function getClientReservations(int $clientId): array {
        $stmt = $this->db->prepare("
            SELECT r.*,
                   h.numero_habitacion, h.tipo as tipo_habitacion,
                   COALESCE(SUM(p.monto), 0) as total_pagado
            FROM reservas r
            JOIN habitaciones h ON r.habitacion_id = h.id
            LEFT JOIN pagos p ON r.id = p.reserva_id
            WHERE r.cliente_id = ?
            GROUP BY r.id
            ORDER BY r.fecha_entrada DESC
        ");
        $stmt->execute([$clientId]);
        return $stmt->fetchAll();
    }

    public function getTopClients(int $limit = 10): array {
        $stmt = $this->db->prepare("
            SELECT c.*,
                   COUNT(DISTINCT r.id) as total_reservas,
                   COALESCE(SUM(p.monto), 0) as total_gastado
            FROM clientes c
            LEFT JOIN reservas r ON c.id = r.cliente_id
            LEFT JOIN pagos p ON r.id = p.reserva_id
            GROUP BY c.id
            ORDER BY total_gastado DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    public function getClientStats(): array {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) as total_clientes,
                AVG(total_gastado) as gasto_promedio,
                COUNT(CASE WHEN ultima_reserva >= DATE('now', '-30 days') THEN 1 END) as clientes_activos_30_dias
            FROM (
                SELECT c.id,
                       COALESCE(SUM(p.monto), 0) as total_gastado,
                       MAX(r.created_at) as ultima_reserva
                FROM clientes c
                LEFT JOIN reservas r ON c.id = r.cliente_id
                LEFT JOIN pagos p ON r.id = p.reserva_id
                GROUP BY c.id
            ) as stats
        ");
        $stmt->execute();
        return $stmt->fetch();
    }
}
?>
