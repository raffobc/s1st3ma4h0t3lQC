<?php
class Hotel {
    private PDO $db;

    public function __construct() {
        $this->db = MasterDatabase::getConnection();
        $this->ensureHotelTables();
    }

    private function ensureHotelTables(): void {
        $this->db->exec("\n            CREATE TABLE IF NOT EXISTS hoteles (\n                id INT PRIMARY KEY AUTO_INCREMENT,\n                nombre VARCHAR(150) NOT NULL,\n                razon_social VARCHAR(200) NOT NULL,\n                ruc VARCHAR(20) UNIQUE NOT NULL,\n                direccion VARCHAR(255),\n                telefono VARCHAR(30),\n                email VARCHAR(100),\n                ciudad VARCHAR(100),\n                pais VARCHAR(100) DEFAULT 'Peru',\n                db_name VARCHAR(100),\n                db_host VARCHAR(100),\n                db_user VARCHAR(100),\n                db_password VARCHAR(255),\n                estado VARCHAR(20) DEFAULT 'activo',\n                plan VARCHAR(30) DEFAULT 'basico',\n                max_habitaciones INT DEFAULT 50,\n                fecha_registro DATE,\n                fecha_vencimiento DATE,\n                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,\n                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP\n            )\n        ");

        $this->db->exec("\n            CREATE TABLE IF NOT EXISTS hotel_administradores (\n                id INT PRIMARY KEY AUTO_INCREMENT,\n                hotel_id INT NOT NULL,\n                nombre VARCHAR(120) NOT NULL,\n                email VARCHAR(100) UNIQUE NOT NULL,\n                password VARCHAR(255) NOT NULL,\n                telefono VARCHAR(30),\n                activo TINYINT DEFAULT 1,\n                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,\n                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n                FOREIGN KEY (hotel_id) REFERENCES hoteles(id) ON DELETE CASCADE\n            )\n        ");
    }
    
    public function getAllHotels(): array {
        try {
            $stmt = $this->db->query(" 
                SELECT h.*, 
                       (SELECT COUNT(*) FROM hotel_administradores WHERE hotel_id = h.id) as total_admins
                FROM hoteles h
                ORDER BY h.created_at DESC
            ");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Hotel getAllHotels error: ' . $e->getMessage());
            return [];
        }
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
