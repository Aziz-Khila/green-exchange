<?php
/**
 * Database Configuration for local MySQL
 *
 * Update these values if your local environment uses different credentials.
 */

// Local database credentials (XAMPP/WAMP defaults)
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'green_exchange');
define('DB_USER', 'root');
define('DB_PASS', '');

/**
 * Get database connection (MySQL with SQLite fallback for local development)
 * @return PDO
 */
function getDB() {
    $sqlite_db = __DIR__ . '/../database/green_exchange.db';
    $host = defined('DB_HOST') ? strtolower(DB_HOST) : '';
    $isLocalHost = in_array($host, ['127.0.0.1', 'localhost', '::1'], true);

    if ($isLocalHost && file_exists($sqlite_db)) {
        $pdo = new PDO('sqlite:' . $sqlite_db);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = ON;');
        return $pdo;
    }

    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        return $pdo;
    } catch (PDOException $e) {
        if (file_exists($sqlite_db)) {
            $pdo = new PDO('sqlite:' . $sqlite_db);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->exec('PRAGMA foreign_keys = ON;');
            return $pdo;
        }

        throw $e;
    }
}
