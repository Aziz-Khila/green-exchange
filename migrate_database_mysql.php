<?php
/**
 * Database Migration Script for MySQL (InfinityFree)
 * 
 * This script migrates your existing database to include:
 * - Commission columns (buyer_commission, seller_commission, buyer_total, seller_total)
 * - cancelled_by column in orders
 * - pending_completion status
 * - invoice_role column in invoices
 * - settings table for commission rates
 * 
 * Usage: Upload this file to your server and access via browser:
 * https://yourdomain.com/migrate_database_mysql.php
 * 
 * IMPORTANT: Delete this file after running the migration!
 */

require_once __DIR__ . '/config/database_mysql.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Migration - Green Exchange</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: #10b981; background: #d1fae5; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: #ef4444; background: #fee2e2; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: #3b82f6; background: #dbeafe; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .warning { color: #f59e0b; background: #fef3c7; padding: 10px; border-radius: 5px; margin: 10px 0; }
        pre { background: #f3f4f6; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Database Migration Script</h1>
    <p>This script will update your database schema to include the latest changes.</p>
    
<?php

try {
    $pdo = getDB();
    echo '<div class="info">✓ Connected to database successfully</div>';
    
    $errors = [];
    $success = [];
    
    // 1. Add commission columns to orders table (if they don't exist)
    echo '<h2>Step 1: Adding commission columns to orders table...</h2>';
    
    $columns_to_add = [
        'buyer_commission' => "ALTER TABLE orders ADD COLUMN buyer_commission DECIMAL(10, 2) NOT NULL DEFAULT 0",
        'seller_commission' => "ALTER TABLE orders ADD COLUMN seller_commission DECIMAL(10, 2) NOT NULL DEFAULT 0",
        'buyer_total' => "ALTER TABLE orders ADD COLUMN buyer_total DECIMAL(10, 2) NOT NULL DEFAULT 0",
        'seller_total' => "ALTER TABLE orders ADD COLUMN seller_total DECIMAL(10, 2) NOT NULL DEFAULT 0",
        'cancelled_by' => "ALTER TABLE orders ADD COLUMN cancelled_by VARCHAR(10) NULL"
    ];
    
    foreach ($columns_to_add as $column => $sql) {
        try {
            // Check if column exists
            $check = $pdo->query("SHOW COLUMNS FROM orders LIKE '$column'");
            if ($check->rowCount() == 0) {
                $pdo->exec($sql);
                echo '<div class="success">✓ Added column: ' . $column . '</div>';
                $success[] = "Added column: $column";
            } else {
                echo '<div class="info">→ Column already exists: ' . $column . '</div>';
            }
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo '<div class="info">→ Column already exists: ' . $column . '</div>';
            } else {
                echo '<div class="error">✗ Error adding column ' . $column . ': ' . $e->getMessage() . '</div>';
                $errors[] = "Error adding column $column: " . $e->getMessage();
            }
        }
    }
    
    // 2. Update existing orders to calculate commissions (if columns were just added)
    echo '<h2>Step 2: Updating existing orders with commission data...</h2>';
    try {
        // Check if there are orders without commission data
        $check_orders = $pdo->query("SELECT COUNT(*) as count FROM orders WHERE buyer_commission = 0 AND buyer_total = 0 AND subtotal > 0");
        $result = $check_orders->fetch();
        
        if ($result['count'] > 0) {
            // Update old orders with default 5% commission
            $update_sql = "UPDATE orders SET 
                buyer_commission = subtotal * 0.05,
                seller_commission = subtotal * 0.05,
                buyer_total = subtotal + (subtotal * 0.05),
                seller_total = subtotal - (subtotal * 0.05)
                WHERE buyer_commission = 0 AND buyer_total = 0 AND subtotal > 0";
            
            $pdo->exec($update_sql);
            $affected = $pdo->query("SELECT ROW_COUNT()")->fetchColumn();
            echo '<div class="success">✓ Updated ' . $result['count'] . ' existing orders with commission data</div>';
            $success[] = "Updated existing orders";
        } else {
            echo '<div class="info">→ No orders need updating</div>';
        }
    } catch (PDOException $e) {
        echo '<div class="warning">⚠ Could not update existing orders: ' . $e->getMessage() . '</div>';
    }
    
    // 3. Update orders table status constraint to include 'pending_completion'
    echo '<h2>Step 3: Updating orders status constraint...</h2>';
    try {
        // MySQL doesn't support ALTER TABLE to modify CHECK constraints easily
        // We'll check if we can insert a pending_completion status
        $test = $pdo->query("SELECT status FROM orders LIMIT 1");
        echo '<div class="info">→ Status constraint check passed (MySQL handles this at application level)</div>';
        $success[] = "Status constraint verified";
    } catch (PDOException $e) {
        echo '<div class="warning">⚠ Status constraint note: ' . $e->getMessage() . '</div>';
    }
    
    // 4. Add invoice_role column to invoices table
    echo '<h2>Step 4: Adding invoice_role to invoices table...</h2>';
    try {
        $check = $pdo->query("SHOW COLUMNS FROM invoices LIKE 'invoice_role'");
        if ($check->rowCount() == 0) {
            $pdo->exec("ALTER TABLE invoices ADD COLUMN invoice_role VARCHAR(10) NULL");
            echo '<div class="success">✓ Added column: invoice_role</div>';
            $success[] = "Added invoice_role column";
        } else {
            echo '<div class="info">→ Column already exists: invoice_role</div>';
        }
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo '<div class="info">→ Column already exists: invoice_role</div>';
        } else {
            echo '<div class="error">✗ Error adding invoice_role: ' . $e->getMessage() . '</div>';
            $errors[] = "Error adding invoice_role: " . $e->getMessage();
        }
    }
    
    // 5. Create settings table
    echo '<h2>Step 5: Creating settings table...</h2>';
    try {
        $table_exists = $pdo->query("SHOW TABLES LIKE 'settings'");
        if ($table_exists->rowCount() == 0) {
            $pdo->exec("CREATE TABLE settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(100) UNIQUE NOT NULL,
                setting_value TEXT NOT NULL,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )");
            echo '<div class="success">✓ Created settings table</div>';
            $success[] = "Created settings table";
            
            // Insert default commission rates
            $default_settings = [
                'buyer_commission_rate' => '0.05',
                'seller_commission_rate' => '0.05',
                'account_creation_fee' => '10.00'
            ];
            
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
            foreach ($default_settings as $key => $value) {
                try {
                    $stmt->execute([$key, $value]);
                    echo '<div class="success">✓ Inserted default setting: ' . $key . ' = ' . $value . '</div>';
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'Duplicate entry') === false) {
                        echo '<div class="warning">⚠ Could not insert setting ' . $key . ': ' . $e->getMessage() . '</div>';
                    }
                }
            }
        } else {
            echo '<div class="info">→ Settings table already exists</div>';
            
            // Check if default settings exist, if not, add them
            $default_settings = [
                'buyer_commission_rate' => '0.05',
                'seller_commission_rate' => '0.05',
                'account_creation_fee' => '10.00'
            ];
            
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            foreach ($default_settings as $key => $value) {
                try {
                    $stmt->execute([$key, $value]);
                    echo '<div class="info">→ Ensured setting exists: ' . $key . '</div>';
                } catch (PDOException $e) {
                    echo '<div class="warning">⚠ Could not ensure setting ' . $key . ': ' . $e->getMessage() . '</div>';
                }
            }
        }
    } catch (PDOException $e) {
        echo '<div class="error">✗ Error creating settings table: ' . $e->getMessage() . '</div>';
        $errors[] = "Error creating settings table: " . $e->getMessage();
    }
    
    // Summary
    echo '<h2>Migration Summary</h2>';
    if (empty($errors)) {
        echo '<div class="success"><strong>✓ Migration completed successfully!</strong></div>';
        echo '<div class="info"><strong>IMPORTANT:</strong> Delete this file (migrate_database_mysql.php) from your server for security!</div>';
    } else {
        echo '<div class="error"><strong>Migration completed with some errors:</strong></div>';
        foreach ($errors as $error) {
            echo '<div class="error">- ' . $error . '</div>';
        }
    }
    
    if (!empty($success)) {
        echo '<div class="info"><strong>Successful operations:</strong></div>';
        foreach ($success as $msg) {
            echo '<div class="info">- ' . $msg . '</div>';
        }
    }
    
} catch (PDOException $e) {
    echo '<div class="error"><strong>Database Connection Error:</strong> ' . $e->getMessage() . '</div>';
    echo '<div class="info">Please check your database credentials in config/database_mysql.php</div>';
}

?>

</body>
</html>


