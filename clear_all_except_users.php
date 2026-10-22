<?php
/**
 * Clear all database data except users
 * Rename "test user" to "buyer"
 */

require_once __DIR__ . '/includes/functions.php';

// Check if user is admin (optional - you can remove this check if running from command line)
if (php_sapi_name() !== 'cli') {
    if (!isLoggedIn() || !isAdmin()) {
        die('Unauthorized. Only admins can run this script.');
    }
}

$pdo = getDB();

try {
    $pdo->beginTransaction();
    
    echo "Starting database cleanup...\n\n";
    
    // Delete all data from tables (in correct order to respect foreign keys)
    $tables_to_clear = [
        'notifications',
        'password_resets',
        'invoices',
        'orders',
        'cart',
        'wishlist',
        'reviews',
        'messages',
        'post_images',
        'posts'
    ];
    
    foreach ($tables_to_clear as $table) {
        $stmt = $pdo->prepare("DELETE FROM $table");
        $stmt->execute();
        $count = $stmt->rowCount();
        echo "Cleared $table: $count rows deleted\n";
    }
    
    // Rename "test user" to "buyer"
    // First, check if "test" or "test user" exists
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE username LIKE 'test%' OR username = 'test user' OR username = 'testuser'");
    $stmt->execute();
    $test_users = $stmt->fetchAll();
    
    if (!empty($test_users)) {
        foreach ($test_users as $user) {
            // Check if "buyer" already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'buyer'");
            $stmt->execute();
            $buyer_exists = $stmt->fetch();
            
            if ($buyer_exists) {
                // If buyer exists, just delete the test user
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$user['id']]);
                echo "Deleted test user: {$user['username']} (buyer already exists)\n";
            } else {
                // Rename test user to buyer
                $stmt = $pdo->prepare("UPDATE users SET username = 'buyer' WHERE id = ?");
                $stmt->execute([$user['id']]);
                echo "Renamed user '{$user['username']}' to 'buyer'\n";
            }
        }
    } else {
        echo "No test user found to rename\n";
    }
    
    $pdo->commit();
    
    echo "\n✅ Database cleanup completed successfully!\n";
    echo "All data cleared except users.\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}


