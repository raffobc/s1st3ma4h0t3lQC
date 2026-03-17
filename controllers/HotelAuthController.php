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
                session_regenerate_id(true);
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
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool)$params['secure'], (bool)$params['httponly']);
        }
        session_destroy();
        header("Location: " . BASE_URL . "/hotel/login");
        exit;
    }

    public function changePassword(): void {
        $this->checkAuth();

        $error = null;
        $success = null;

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $currentPassword = (string)($_POST["current_password"] ?? "");
            $newPassword = (string)($_POST["new_password"] ?? "");
            $confirmPassword = (string)($_POST["confirm_password"] ?? "");
            $userId = (int)($_SESSION["hotel_user_id"] ?? 0);

            if ($currentPassword === "" || $newPassword === "" || $confirmPassword === "") {
                $error = "Completa todos los campos.";
            } elseif (strlen($newPassword) < 6) {
                $error = "La nueva contraseña debe tener al menos 6 caracteres.";
            } elseif ($newPassword !== $confirmPassword) {
                $error = "La confirmación no coincide con la nueva contraseña.";
            } else {
                $stmt = $this->db->prepare("SELECT id, password FROM usuarios WHERE id = ? AND activo = 1 LIMIT 1");
                $stmt->execute([$userId]);
                $user = $stmt->fetch();

                if (!$user || !password_verify($currentPassword, (string)$user["password"])) {
                    $error = "La contraseña actual es incorrecta.";
                } else {
                    $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
                    $update = $this->db->prepare("UPDATE usuarios SET password = ?, updated_at = NOW() WHERE id = ?");
                    $update->execute([$newHash, $userId]);
                    $success = "Contraseña actualizada correctamente.";
                }
            }
        }

        require_once BASE_PATH . "/views/hotel/change_password.php";
    }

    private function checkAuth(): void {
        if (!isset($_SESSION["hotel_user_id"])) {
            header("Location: " . BASE_URL . "/hotel/login");
            exit;
        }
    }
}
?>
