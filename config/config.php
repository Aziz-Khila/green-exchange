<?php
/**
 * Application Configuration
 */

// Site settings
define('SITE_NAME', 'Green Exchange');
define('SITE_URL', 'http://localhost');

// Paths
define('ROOT_PATH', __DIR__ . '/..');
define('UPLOAD_PATH', ROOT_PATH . '/uploads/posts');
define('UPLOAD_URL', '/uploads/posts');

// File upload settings
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// Commission and fees - Default values (can be overridden by database settings)
$default_buyer_rate = 0.05; // 5% commission on buyer side (added to subtotal)
$default_seller_rate = 0.05; // 5% commission on seller side (deducted from subtotal)
$default_account_fee = 10.00; // 10 TND

// Try to load from database, fallback to defaults
// Note: We load database connection directly to avoid circular dependency
$buyer_rate = $default_buyer_rate;
$seller_rate = $default_seller_rate;
$account_fee = $default_account_fee;

try {
    // Try to load database connection
    if (file_exists(__DIR__ . '/database_mysql.php')) {
        require_once __DIR__ . '/database_mysql.php';
        $pdo = getDB();
    } elseif (file_exists(__DIR__ . '/database.php')) {
        require_once __DIR__ . '/database.php';
        $db_file = __DIR__ . '/../database/green_exchange.db';
        if (file_exists($db_file)) {
            $pdo = new PDO('sqlite:' . $db_file);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } else {
            throw new Exception('Database file not found');
        }
    } else {
        throw new Exception('Database config not found');
    }

    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $isMySQL = ($driver === 'mysql');

    // Check if settings table exists
    if ($isMySQL) {
        $tableExists = $pdo->query("SHOW TABLES LIKE 'settings'")->fetch();
    } else {
        $tableExists = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='settings'")->fetch();
    }
    
    if ($tableExists) {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('buyer_commission_rate', 'seller_commission_rate', 'account_creation_fee')");
        $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        if (isset($settings['buyer_commission_rate'])) {
            $buyer_rate = floatval($settings['buyer_commission_rate']);
        }
        if (isset($settings['seller_commission_rate'])) {
            $seller_rate = floatval($settings['seller_commission_rate']);
        }
        if (isset($settings['account_creation_fee'])) {
            $account_fee = floatval($settings['account_creation_fee']);
        }
    }
} catch (Exception $e) {
    // Fallback to defaults if database read fails
    // Silently use defaults
}

define('COMMISSION_RATE', $buyer_rate); // Legacy, kept for backward compatibility
define('BUYER_COMMISSION_RATE', $buyer_rate);
define('SELLER_COMMISSION_RATE', $seller_rate);
define('ACCOUNT_CREATION_FEE', $account_fee);

// Session settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();

// Timezone
date_default_timezone_set('Africa/Tunis');

// Load language system
require_once __DIR__ . '/languages.php';

