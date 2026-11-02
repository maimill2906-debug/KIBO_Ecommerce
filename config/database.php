<?php
// ====================================================================
// KIBO E-COMMERCE PLATFORM (kibo.vn)
// Config Database Connection (PostgreSQL Pooler via Environment Variables)
// ====================================================================

class Database {
    private static $conn = null;
    private static $lastError = null;

    public static function loadEnv($file = null, $override = false) {
        $envFile = $file ?: (getenv('KIBO_ENV_FILE') ?: __DIR__ . '/../.env');
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || strpos($line, '#') === 0) continue;
                $parts = explode('=', $line, 2);
                if (count($parts) === 2) {
                    $key = trim($parts[0]);
                    $val = trim($parts[1]);
                    $val = trim($val, "\"'");
                    if ($override || getenv($key) === false) {
                        putenv("{$key}={$val}");
                        $_ENV[$key] = $val;
                        $_SERVER[$key] = $val;
                    }
                }
            }
        }
    }

    public static function getConnection() {
        if (self::$conn !== null) {
            return self::$conn;
        }

        self::loadEnv();

        $required = ['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS'];
        $missing = [];
        $config = [];
        foreach ($required as $var) {
            $val = getenv($var);
            if ($val === false || $val === '') {
                $val = $_ENV[$var] ?? ($_SERVER[$var] ?? null);
            }
            if ($val === null || $val === false || $val === '') {
                $missing[] = $var;
            } else {
                $config[$var] = $val;
            }
        }

        if (!empty($missing)) {
            throw new RuntimeException("Thiếu biến môi trường DB: " . implode(', ', $missing));
        }

        $host = $config['DB_HOST'];
        $port = $config['DB_PORT'];
        $dbname = $config['DB_NAME'];
        $username = $config['DB_USER'];
        $password = $config['DB_PASS'];

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => true,
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_TIMEOUT => 5
        ];

        try {
            $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode=require";
            self::$conn = new PDO($dsn, $username, $password, $options);
            self::$lastError = null;
            return self::$conn;
        } catch (PDOException $e) {
            self::$lastError = $e->getMessage();
            self::$conn = null;
            throw $e;
        }
    }

    public static function getStatus() {
        self::loadEnv();
        try {
            $conn = self::getConnection();
            return [
                'connected' => ($conn !== null),
                'host' => getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? 'unknown'),
                'port' => getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? 'unknown'),
                'database' => getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? 'unknown'),
                'user' => getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? 'unknown'),
                'error' => self::$lastError
            ];
        } catch (Exception $e) {
            return [
                'connected' => false,
                'host' => getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? 'unknown'),
                'port' => getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? 'unknown'),
                'database' => getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? 'unknown'),
                'user' => getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? 'unknown'),
                'error' => $e->getMessage()
            ];
        }
    }
}
