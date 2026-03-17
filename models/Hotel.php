<?php
class Hotel {
    private PDO $db;

    public function __construct() {
        $this->db = MasterDatabase::getConnection();
    }
    
    public function getAllHotels(): array {
        $stmt = $this->db->query("
            SELECT h.*, 
                   (SELECT COUNT(*) FROM hotel_administradores WHERE hotel_id = h.id) as total_admins
            FROM hoteles h
            ORDER BY h.created_at DESC
        ");
        return $stmt->fetchAll();
    }
    
    public function getHotelById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM hoteles WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
    
    public function createHotel(array $data) {
        try {
            $this->db->beginTransaction();

            $sql = "INSERT INTO hoteles (
                nombre, razon_social, ruc, direccion, telefono, email, ciudad, pais,
                db_name, db_host, db_user, db_password,
                estado, plan, max_habitaciones, fecha_registro, fecha_vencimiento
            ) VALUES (
                :nombre, :razon_social, :ruc, :direccion, :telefono, :email, :ciudad, :pais,
                :db_name, :db_host, :db_user, :db_password,
                :estado, :plan, :max_habitaciones, :fecha_registro, :fecha_vencimiento
            )";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                "nombre" => $data["nombre"],
                "razon_social" => $data["razon_social"],
                "ruc" => $data["ruc"],
                "direccion" => $data["direccion"] ?? null,
                "telefono" => $data["telefono"] ?? null,
                "email" => $data["email"] ?? null,
                "ciudad" => $data["ciudad"] ?? null,
                "pais" => "Perú",
                "db_name" => MASTER_DB_NAME,
                "db_host" => "localhost",
                "db_user" => MASTER_DB_USER,
                "db_password" => MASTER_DB_PASS,
                "estado" => "activo",
                "plan" => $data["plan"] ?? "basico",
                "max_habitaciones" => $data["max_habitaciones"] ?? 50,
                "fecha_registro" => date("Y-m-d"),
                "fecha_vencimiento" => date("Y-m-d", strtotime("+1 year"))
            ]);

            $hotelId = (int)$this->db->lastInsertId();

            // Crear administrador del hotel
            if (isset($data["admin_email"])) {
                $this->createHotelAdmin($hotelId, [
                    "nombre" => $data["admin_nombre"],
                    "email" => $data["admin_email"],
                    "password" => $data["admin_password"] ?? "Admin123!",
                    "telefono" => $data["admin_telefono"] ?? null
                ]);
            }

            $this->db->commit();
            return $hotelId;

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            error_log("Error creando hotel: " . $e->getMessage());
            return false;
        }
    }
    

    
    private function createHotelAdmin(int $hotelId, array $data): bool {
        $sql = "INSERT INTO hotel_administradores (hotel_id, nombre, email, password, telefono, activo)
                VALUES (?, ?, ?, ?, ?, 1)";
        
        $stmt = $this->db->prepare($sql);
        $hashedPassword = password_hash($data["password"], PASSWORD_DEFAULT);
        
        return $stmt->execute([
            $hotelId,
            $data["nombre"],
            $data["email"],
            $hashedPassword,
            $data["telefono"] ?? null
        ]);
    }
    
    public function updateHotelStatus(int $id, string $status): bool {
        $stmt = $this->db->prepare("UPDATE hoteles SET estado = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }
    
    public function deleteHotel(int $id): bool {
        try {
            $hotel = $this->getHotelById($id);
            if (!$hotel) {
                return false;
            }
            
            $this->db->beginTransaction();
            
            // Eliminar base de datos
            $this->db->exec("DROP DATABASE IF EXISTS `{$hotel['db_name']}`");
            
            // Eliminar de tabla maestra
            $stmt = $this->db->prepare("DELETE FROM hoteles WHERE id = ?");
            $stmt->execute([$id]);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error eliminando hotel: " . $e->getMessage());
            return false;
        }
    }
}
?>
