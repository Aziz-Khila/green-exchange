<?php
/**
 * Database Configuration
 * SQLite connection settings
 */

define('DB_PATH', __DIR__ . '/../database/green_exchange.db');

/**
 * Get database connection
 * @return PDO
 */
function getDB() {
    try {
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        // Enable foreign keys
        $pdo->exec('PRAGMA foreign_keys = ON;');
        return $pdo;
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

