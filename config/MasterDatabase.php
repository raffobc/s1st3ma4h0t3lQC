<?php
class MasterDatabase {
    private static ?PDO $instance = null;
    
    public static function getConnection(): PDO {
        if (self::$instance === null) {
            self::$instance = new PDO(
                "mysql:host=" . MASTER_DB_HOST . ";dbname=" . MASTER_DB_NAME . ";charset=utf8mb4",
                MASTER_DB_USER,
                MASTER_DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        }
        return self::$instance;
    }
}
