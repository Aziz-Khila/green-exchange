<?php
/**
 * Database Initialization Script for MySQL (InfinityFree)
 * Run this file once to create the MySQL database and tables
 * 
 * IMPORTANT: 
 * 1. First, update config/database_mysql.php with your InfinityFree database credentials
 * 2. Then run this script via InfinityFree File Manager or upload and access via browser
 */

require_once __DIR__ . '/config/database_mysql.php';

$schema_file = __DIR__ . '/database_schema_mysql.sql';

// Check if PDO MySQL driver is available
if (!in_array('mysql', PDO::getAvailableDrivers())) {
    die("❌ ERROR: PDO MySQL driver is not available!");
}

try {
    // Get database connection
    $pdo = getDB();
    
    echo "Connected to MySQL database.\n";
    
    // Read and execute schema file
    if (!file_exists($schema_file)) {
        throw new Exception("Schema file not found: $schema_file");
    }
    
    $schema = file_get_contents($schema_file);
    
    // Remove single-line comments
    $schema = preg_replace('/--.*$/m', '', $schema);
    
    // Split by semicolon followed by optional whitespace/newline
    $statements = preg_split('/;\s*\n/', $schema);
    
    $executed = 0;
    foreach ($statements as $index => $statement) {
        $statement = trim($statement);
        // Skip empty statements
        if (empty($statement)) {
            continue;
        }
        
        // Add semicolon back if not present
        if (substr($statement, -1) !== ';') {
            $statement .= ';';
        }
        
        try {
            $pdo->exec($statement);
            $executed++;
        } catch (PDOException $e) {
            // Skip if table or index already exists
            if (strpos($e->getMessage(), 'already exists') !== false || 
                strpos($e->getMessage(), 'Duplicate key name') !== false ||
                strpos($e->getMessage(), 'Duplicate index') !== false) {
                echo "Already exists, skipping...\n";
                continue;
            }
            echo "\nError executing statement #" . ($index + 1) . ":\n";
            echo substr($statement, 0, 100) . (strlen($statement) > 100 ? '...' : '') . "\n";
            echo "Error: " . $e->getMessage() . "\n\n";
            throw $e;
        }
    }
    
    echo "Executed $executed SQL statements.\n";
    echo "Database schema created successfully!\n";
    
    // Check if admin user already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'admin'");
    $stmt->execute();
    $admin_exists = $stmt->fetch();
    
    if (!$admin_exists) {
        // Create a default admin user (password: admin123 - should be changed!)
        $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password, role, verified, account_fee_paid) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute(['admin', 'admin@greenexchange.com', $admin_password, 'admin', 1, 1]);
        
        echo "\nDefault admin user created:\n";
        echo "Username: admin\n";
        echo "Email: admin@greenexchange.com\n";
        echo "Password: admin123\n";
        echo "⚠️  IMPORTANT: Change this password after first login!\n";
    } else {
        echo "\nAdmin user already exists, skipping creation.\n";
    }
    
    echo "\n✅ Database initialization completed successfully!\n";
    
} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

