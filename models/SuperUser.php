<?php
class SuperUser {
    private PDO $db;

    public function __construct() {
        $this->db = MasterDatabase::getConnection();
        $this->ensureSuperUsersTable();
    }

    private function ensureSuperUsersTable(): void {
        $this->db->exec("\n            CREATE TABLE IF NOT EXISTS super_usuarios (\n                id INT PRIMARY KEY AUTO_INCREMENT,\n                nombre VARCHAR(100) NOT NULL,\n                email VARCHAR(100) UNIQUE NOT NULL,\n                password VARCHAR(255) NOT NULL,\n                activo TINYINT DEFAULT 1,\n                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,\n                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP\n            )\n        ");

        $stmt = $this->db->query("SELECT COUNT(*) AS total FROM super_usuarios");
        $count = (int)($stmt->fetch()['total'] ?? 0);
        if ($count === 0) {
            $seed = $this->db->prepare("INSERT INTO super_usuarios (nombre, email, password, activo) VALUES (?, ?, ?, 1)");
            $seed->execute(['Super Admin', 'super@hotel.com', password_hash('Super123!', PASSWORD_BCRYPT)]);
        }
    }
    
    public function authenticate(string $email, string $password): ?array {
        try {
            $stmt = $this->db->prepare("SELECT * FROM super_usuarios WHERE email = ? AND activo = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                unset($user['password']);
                return $user;
            }
        } catch (PDOException $e) {
            error_log('SuperUser authenticate error: ' . $e->getMessage());
        }
        
        return null;
    }
}
?>
