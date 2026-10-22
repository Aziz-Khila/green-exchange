<?php
/**
 * Update Commission Settings API
 */

require_once __DIR__ . '/../includes/functions.php';

// Check if user is admin
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$pdo = getDB();

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $input = $_POST;
    }
    
    // Validate input
    $buyer_rate = isset($input['buyer_commission_rate']) ? floatval($input['buyer_commission_rate']) : null;
    $seller_rate = isset($input['seller_commission_rate']) ? floatval($input['seller_commission_rate']) : null;
    $account_fee = isset($input['account_creation_fee']) ? floatval($input['account_creation_fee']) : null;
    
    // Validate ranges
    if ($buyer_rate !== null && ($buyer_rate < 0 || $buyer_rate > 100)) {
        throw new Exception('Buyer commission rate must be between 0 and 100');
    }
    
    if ($seller_rate !== null && ($seller_rate < 0 || $seller_rate > 100)) {
        throw new Exception('Seller commission rate must be between 0 and 100');
    }
    
    if ($account_fee !== null && $account_fee < 0) {
        throw new Exception('Account creation fee must be positive');
    }
    
    // Check if settings table exists, create if not
    // Detect database type
    $isMySQL = file_exists(__DIR__ . '/../config/database_mysql.php');
    
    try {
        if ($isMySQL) {
            // MySQL syntax
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS settings (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    setting_key VARCHAR(100) UNIQUE NOT NULL,
                    setting_value TEXT NOT NULL,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");
        } else {
            // SQLite syntax
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS settings (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    setting_key VARCHAR(100) UNIQUE NOT NULL,
                    setting_value TEXT NOT NULL,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");
        }
    } catch (PDOException $e) {
        // Table might already exist, continue
        if (strpos($e->getMessage(), 'already exists') === false && 
            strpos($e->getMessage(), 'Duplicate') === false) {
            throw $e;
        }
    }
    
    // Update or insert settings (SQLite compatible)
    if ($buyer_rate !== null) {
        $value = $buyer_rate / 100; // Store as decimal (0.05 for 5%)
        
        // Check if setting exists
        $stmt = $pdo->prepare("SELECT id FROM settings WHERE setting_key = 'buyer_commission_rate'");
        $stmt->execute();
        $exists = $stmt->fetch();
        
        if ($exists) {
            $stmt = $pdo->prepare("UPDATE settings SET setting_value = ?, updated_at = CURRENT_TIMESTAMP WHERE setting_key = 'buyer_commission_rate'");
            $stmt->execute([$value]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, updated_at) VALUES ('buyer_commission_rate', ?, CURRENT_TIMESTAMP)");
            $stmt->execute([$value]);
        }
    }
    
    if ($seller_rate !== null) {
        $value = $seller_rate / 100; // Store as decimal (0.05 for 5%)
        
        // Check if setting exists
        $stmt = $pdo->prepare("SELECT id FROM settings WHERE setting_key = 'seller_commission_rate'");
        $stmt->execute();
        $exists = $stmt->fetch();
        
        if ($exists) {
            $stmt = $pdo->prepare("UPDATE settings SET setting_value = ?, updated_at = CURRENT_TIMESTAMP WHERE setting_key = 'seller_commission_rate'");
            $stmt->execute([$value]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, updated_at) VALUES ('seller_commission_rate', ?, CURRENT_TIMESTAMP)");
            $stmt->execute([$value]);
        }
    }
    
    if ($account_fee !== null) {
        // Check if setting exists
        $stmt = $pdo->prepare("SELECT id FROM settings WHERE setting_key = 'account_creation_fee'");
        $stmt->execute();
        $exists = $stmt->fetch();
        
        if ($exists) {
            $stmt = $pdo->prepare("UPDATE settings SET setting_value = ?, updated_at = CURRENT_TIMESTAMP WHERE setting_key = 'account_creation_fee'");
            $stmt->execute([$account_fee]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, updated_at) VALUES ('account_creation_fee', ?, CURRENT_TIMESTAMP)");
            $stmt->execute([$account_fee]);
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Commission settings updated successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

