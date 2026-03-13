<?php
class SuperUser {
    private PDO $db;

    public function __construct() {
        $this->db = MasterDatabase::getConnection();
    }
    
    public function authenticate(string $email, string $password): ?array {
        $stmt = $this->db->prepare("SELECT * FROM super_usuarios WHERE email = ? AND activo = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            unset($user['password']);
            return $user;
        }
        
        return null;
    }
}
?>
