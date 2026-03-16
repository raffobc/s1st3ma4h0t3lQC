<?php
class HotelAuthController {
    private PDO $db;

    public function __construct() {
        $this->db = MasterDatabase::getConnection();
    }
    
    public function login(): void {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $email = $_POST["email"] ?? "";
            $password = $_POST["password"] ?? "";

            // Autenticación con MySQL
            $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE email = ? AND activo = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user["password"])) {
                // Configurar sesión para el hotel local
                $_SESSION["hotel_user_id"] = $user["id"];
                $_SESSION["hotel_user_name"] = $user["nombre"];
                $_SESSION["hotel_user_email"] = $user["email"];
                $_SESSION["hotel_id"] = 1; // Hotel demo
                $_SESSION["hotel_name"] = "Hotel Demo";
                $_SESSION["hotel_db_name"] = "hotel_demo"; // Para compatibilidad
                $_SESSION["hotel_db_credentials"] = [
                    "host" => "localhost",
                    "user" => "root",
                    "password" => ""
                ];

                // Actualizar último acceso
                $stmt = $this->db->prepare("UPDATE usuarios SET updated_at = NOW() WHERE id = ?");
                $stmt->execute([$user["id"]]);

                header("Location: " . BASE_URL . "/hotel/dashboard");
                exit;
            } else {
                $error = "Credenciales inválidas";
            }
        }

        require_once BASE_PATH . "/views/hotel/login.php";
    }
    
    public function logout(): void {
        session_destroy();
        header("Location: " . BASE_URL . "/hotel/login");
        exit;
    }
}
?>
