<?php
/**
 * Utility Functions
 */

require_once __DIR__ . '/../config/config.php';

// Auto-detect database: Use MySQL if available (hosting), otherwise SQLite (local)
if (file_exists(__DIR__ . '/../config/database_mysql.php')) {
    require_once __DIR__ . '/../config/database_mysql.php';
} else {
    require_once __DIR__ . '/../config/database.php';
}

/**
 * Get commission rates from database with fallback to constants
 */
function getCommissionRates() {
    static $rates = null;
    
    if ($rates !== null) {
        return $rates; // Cache for performance
    }
    
    $pdo = getDB();
    
    try {
        // Check if settings table exists
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $isMySQL = ($driver === 'mysql');

        if ($isMySQL) {
            $tableExists = $pdo->query("SHOW TABLES LIKE 'settings'")->fetch();
        } else {
            $tableExists = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='settings'")->fetch();
        }
        
        if ($tableExists) {
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('buyer_commission_rate', 'seller_commission_rate', 'account_creation_fee')");
            $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            $rates = [
                'buyer' => isset($settings['buyer_commission_rate']) ? floatval($settings['buyer_commission_rate']) : BUYER_COMMISSION_RATE,
                'seller' => isset($settings['seller_commission_rate']) ? floatval($settings['seller_commission_rate']) : SELLER_COMMISSION_RATE,
                'account_fee' => isset($settings['account_creation_fee']) ? floatval($settings['account_creation_fee']) : ACCOUNT_CREATION_FEE
            ];
        } else {
            // Use constants if table doesn't exist
            $rates = [
                'buyer' => BUYER_COMMISSION_RATE,
                'seller' => SELLER_COMMISSION_RATE,
                'account_fee' => ACCOUNT_CREATION_FEE
            ];
        }
    } catch (Exception $e) {
        // Fallback to constants on error
        $rates = [
            'buyer' => BUYER_COMMISSION_RATE,
            'seller' => SELLER_COMMISSION_RATE,
            'account_fee' => ACCOUNT_CREATION_FEE
        ];
    }
    
    return $rates;
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Get current user data
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

/**
 * Check if user is admin
 */
function isAdmin() {
    $user = getCurrentUser();
    return $user && $user['role'] === 'admin';
}

/**
 * Redirect to a page
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Sanitize output
 */
function escape($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Generate random token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Format date
 */
function formatDate($date) {
    if ($date === null || $date === '' || empty($date)) {
        return 'N/A';
    }
    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return 'N/A';
    }
    return date('d/m/Y H:i', $timestamp);
}

/**
 * Format price
 */
function formatPrice($price) {
    if ($price === null || $price === '') {
        return '0.00 TND';
    }
    return number_format((float)$price, 2, '.', ' ') . ' TND';
}

/**
 * Format quantity - converts values less than 1 tonne to kgs, and values >= 1000 kg to tonnes
 */
function formatQuantity($quantity, $unit) {
    // If unit is kg and quantity >= 1000, convert to tonnes
    if ($unit === 'kg' && $quantity >= 1000) {
        $quantity_tonnes = $quantity / 1000;
        return number_format($quantity_tonnes, 2, '.', ' ') . ' tonnes';
    }
    // If unit is tonnes and quantity < 1, convert to kgs
    elseif ($unit === 'tonnes' && $quantity < 1) {
        $quantity_kg = $quantity * 1000;
        return number_format($quantity_kg, 0, '.', ' ') . ' kg';
    } else {
        // Display as is
        $formatted = number_format($quantity, 2, '.', ' ');
        return $formatted . ' ' . $unit;
    }
}

