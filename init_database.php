<?php
/**
 * Database Initialization Script
 * Run this file once to create the SQLite database and tables
 * Usage: php init_database.php
 */

// Database configuration
$db_file = __DIR__ . '/database/green_exchange.db';
$db_dir = __DIR__ . '/database';
$schema_file = __DIR__ . '/database_schema.sql';

// Create database directory if it doesn't exist
if (!file_exists($db_dir)) {
    mkdir($db_dir, 0755, true);
    echo "Created database directory: $db_dir\n";
}

// Check if database already exists
if (file_exists($db_file)) {
    echo "Database already exists at: $db_file\n";
    echo "Deleting existing database to recreate...\n";
    unlink($db_file);
    echo "Deleted existing database.\n";
}

// Check if PDO SQLite driver is available
if (!in_array('sqlite', PDO::getAvailableDrivers())) {
    echo "❌ ERROR: PDO SQLite driver is not available!\n\n";
    echo "Please check your PHP configuration:\n";
    echo "1. Run: php check_php.php\n";
    echo "2. Enable PDO SQLite extension in php.ini\n";
    echo "3. Uncomment these lines in php.ini:\n";
    echo "   extension=pdo_sqlite\n";
    echo "   extension=sqlite3\n";
    echo "4. Restart your web server/PHP process\n\n";
    exit(1);
}

try {
    // Create database connection
    $pdo = new PDO('sqlite:' . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Enable foreign key constraints in SQLite
    $pdo->exec('PRAGMA foreign_keys = ON;');
    
    echo "Connected to SQLite database.\n";
    echo "Foreign keys enabled.\n";
    
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
            echo "\nError executing statement #" . ($index + 1) . ":\n";
            echo substr($statement, 0, 100) . (strlen($statement) > 100 ? '...' : '') . "\n";
            echo "Error: " . $e->getMessage() . "\n\n";
            throw $e;
        }
    }
    
    echo "Executed $executed SQL statements.\n";
    echo "Database schema created successfully!\n";
    echo "Database location: $db_file\n";
    
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
    
    echo "\n✅ Database initialization completed successfully!\n";
    
} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}


