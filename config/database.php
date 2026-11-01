<?php
// ====================================================================
// KIBO E-COMMERCE PLATFORM (kibo.vn)
// Config Database Connection (Supabase PostgreSQL Pooler - IPv4)
// URI: postgresql://postgres.iurvrglxeucxukmvdasx:kiboproject004@aws-0-eu-west-1.pooler.supabase.com:6543/postgres
// ====================================================================

class Database {
    private static $host = "aws-0-eu-west-1.pooler.supabase.com";
    private static $primaryIp = "52.209.89.87"; // Direct IPv4 node (tránh timeout 9s khi AWS quay vòng vào IP chết)
    private static $port = "6543";
    private static $db_name = "postgres";
    private static $username = "postgres.iurvrglxeucxukmvdasx";
    private static $password = "kiboproject004";
    private static $conn = null;
    private static $lastError = null;

    public static function getConnection() {
        if (self::$conn !== null) {
            return self::$conn;
        }

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => true, // Tương thích Transaction Pooler (port 6543) của Supabase
            PDO::ATTR_PERSISTENT => true,       // Tái sử dụng kết nối SSL/TCP giúp chuyển tab tức thì
            PDO::ATTR_TIMEOUT => 3
        ];

        // 1. Thử kết nối nhanh qua node IP ổn định
        try {
            $dsn = "pgsql:host=" . self::$primaryIp . ";port=" . self::$port . ";dbname=" . self::$db_name . ";sslmode=require";
            self::$conn = new PDO($dsn, self::$username, self::$password, $options);
            self::$lastError = null;
            return self::$conn;
        } catch (PDOException $e) {
            self::$lastError = $e->getMessage();
        }

        // 2. Fallback qua hostname nếu IP thay đổi
        try {
            $dsn = "pgsql:host=" . self::$host . ";port=" . self::$port . ";dbname=" . self::$db_name . ";sslmode=require";
            self::$conn = new PDO($dsn, self::$username, self::$password, $options);
            self::$lastError = null;
            return self::$conn;
        } catch (PDOException $e) {
            self::$lastError = $e->getMessage();
            self::$conn = null;
        }

        return self::$conn;
    }

    public static function getStatus() {
        $conn = self::getConnection();
        return [
            'connected' => ($conn !== null),
            'host' => self::$host,
            'port' => self::$port,
            'database' => self::$db_name,
            'user' => self::$username,
            'error' => self::$lastError
        ];
    }
}
