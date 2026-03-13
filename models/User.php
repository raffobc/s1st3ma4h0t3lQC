<?php
class User {
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

    public function getAllUsers(): array {
        $stmt = $this->db->query("
            SELECT u.*,
                   COUNT(DISTINCT p.id) as total_pagos_registrados,
                   MAX(u.ultimo_acceso) as ultimo_acceso
            FROM usuarios u
            LEFT JOIN pagos p ON u.id = p.usuario_id
            GROUP BY u.id
            ORDER BY u.nombre ASC
        ");
        return $stmt->fetchAll();
    }

    public function getUserById(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT u.*,
                   COUNT(DISTINCT p.id) as total_pagos_registrados
            FROM usuarios u
            LEFT JOIN pagos p ON u.id = p.usuario_id
            WHERE u.id = ?
            GROUP BY u.id
        ");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function getUserByEmail(string $email): ?array {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public function authenticate(string $email, string $password): ?array {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE email = ? AND activo = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Actualizar último acceso
            $stmt = $this->db->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);

            unset($user['password']);
            return $user;
        }

        return null;
    }

    public function createUser(array $data): int|false {
        // Verificar que el email no exista
        if ($this->getUserByEmail($data['email'])) {
            return false;
        }

        $stmt = $this->db->prepare("
            INSERT INTO usuarios (
                nombre, email, password, rol, activo
            ) VALUES (?, ?, ?, ?, ?)
        ");

        try {
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

            $stmt->execute([
                $data['nombre'],
                $data['email'],
                $hashedPassword,
                $data['rol'] ?? 'recepcionista',
                $data['activo'] ?? 1
            ]);
            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creando usuario: " . $e->getMessage());
            return false;
        }
    }

    public function updateUser(int $id, array $data): bool {
        $fields = [];
        $values = [];

        if (isset($data['nombre'])) {
            $fields[] = "nombre = ?";
            $values[] = $data['nombre'];
        }
        if (isset($data['email'])) {
            // Verificar que el email no exista en otro usuario
            $existingUser = $this->getUserByEmail($data['email']);
            if ($existingUser && $existingUser['id'] != $id) {
                return false;
            }
            $fields[] = "email = ?";
            $values[] = $data['email'];
        }
        if (isset($data['rol'])) {
            $fields[] = "rol = ?";
            $values[] = $data['rol'];
        }
        if (isset($data['activo'])) {
            $fields[] = "activo = ?";
            $values[] = $data['activo'];
        }
        if (isset($data['password']) && !empty($data['password'])) {
            $fields[] = "password = ?";
            $values[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        if (empty($fields)) {
            return false;
        }

        $values[] = $id;
        $sql = "UPDATE usuarios SET " . implode(", ", $fields) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ?";

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($values);
        } catch (PDOException $e) {
            error_log("Error actualizando usuario: " . $e->getMessage());
            return false;
        }
    }

    public function deleteUser(int $id): bool {
        // No permitir eliminar al usuario actual
        if (isset($_SESSION['hotel_user_id']) && $_SESSION['hotel_user_id'] == $id) {
            return false;
        }

        // Verificar si el usuario tiene pagos registrados
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM pagos WHERE usuario_id = ?");
        $stmt->execute([$id]);
        $count = $stmt->fetch()['count'];

        if ($count > 0) {
            // En lugar de eliminar, desactivar
            return $this->updateUser($id, ['activo' => 0]);
        }

        try {
            $stmt = $this->db->prepare("DELETE FROM usuarios WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error eliminando usuario: " . $e->getMessage());
            return false;
        }
    }

    public function toggleUserStatus(int $id): bool {
        $user = $this->getUserById($id);
        if (!$user) {
            return false;
        }

        $newStatus = $user['activo'] ? 0 : 1;
        return $this->updateUser($id, ['activo' => $newStatus]);
    }

    public function changePassword(int $userId, string $currentPassword, string $newPassword): bool {
        $user = $this->getUserById($userId);
        if (!$user) {
            return false;
        }

        // Verificar contraseña actual
        if (!password_verify($currentPassword, $user['password'])) {
            return false;
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        try {
            $stmt = $this->db->prepare("UPDATE usuarios SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            return $stmt->execute([$hashedPassword, $userId]);
        } catch (PDOException $e) {
            error_log("Error cambiando contraseña: " . $e->getMessage());
            return false;
        }
    }

    public function getUsersByRole(string $role): array {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE rol = ? AND activo = 1 ORDER BY nombre ASC");
        $stmt->execute([$role]);
        return $stmt->fetchAll();
    }

    public function getUserActivity(int $userId, int $limit = 50): array {
        $stmt = $this->db->prepare("
            SELECT p.*,
                   r.fecha_entrada, r.fecha_salida,
                   h.numero_habitacion,
                   c.nombre as cliente_nombre
            FROM pagos p
            JOIN reservas r ON p.reserva_id = r.id
            JOIN habitaciones h ON r.habitacion_id = h.id
            JOIN clientes c ON r.cliente_id = c.id
            WHERE p.usuario_id = ?
            ORDER BY p.fecha_pago DESC
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }

    public function validateUserData(array $data, bool $isUpdate = false): array {
        $errors = [];

        if (empty($data['nombre'])) {
            $errors[] = "El nombre es obligatorio";
        }

        if (empty($data['email'])) {
            $errors[] = "El email es obligatorio";
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "El email no es válido";
        }

        if (!$isUpdate || !empty($data['password'])) {
            if (empty($data['password'])) {
                $errors[] = "La contraseña es obligatoria";
            } elseif (strlen($data['password']) < 6) {
                $errors[] = "La contraseña debe tener al menos 6 caracteres";
            }
        }

        if (isset($data['rol'])) {
            $validRoles = ['admin', 'recepcionista', 'gerente'];
            if (!in_array($data['rol'], $validRoles)) {
                $errors[] = "Rol de usuario no válido";
            }
        }

        return $errors;
    }

    public function resetPassword(int $userId, string $newPassword): bool {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        try {
            $stmt = $this->db->prepare("UPDATE usuarios SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            return $stmt->execute([$hashedPassword, $userId]);
        } catch (PDOException $e) {
            error_log("Error reseteando contraseña: " . $e->getMessage());
            return false;
        }
    }

    public function getUserStats(): array {
        $stmt = $this->db->query("
            SELECT
                COUNT(*) as total_usuarios,
                SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END) as usuarios_activos,
                SUM(CASE WHEN rol = 'admin' THEN 1 ELSE 0 END) as administradores,
                SUM(CASE WHEN rol = 'gerente' THEN 1 ELSE 0 END) as gerentes,
                SUM(CASE WHEN rol = 'recepcionista' THEN 1 ELSE 0 END) as recepcionistas
            FROM usuarios
        ");
        return $stmt->fetch();
    }
}
?>
