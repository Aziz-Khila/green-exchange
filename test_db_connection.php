<?php
/**
 * Test Database Connection Script
 * Use this to verify your local database credentials before running init_database_mysql.php
 */

// Test different connection methods
echo "<h2>Testing Database Connection</h2>";

// Method 1: Direct connection test
echo "<h3>Method 1: Direct Connection Test</h3>";
echo "<strong>Testing with local MySQL credentials...</strong><br><br>";

$host = '127.0.0.1';
$port = '3306';
$dbname = 'green_exchange';
$username = 'root';
$password = '';

echo "Host: $host<br>";
echo "Port: $port<br>";
echo "Database: $dbname<br>";
echo "Username: $username<br>";
echo "Password: " . (strlen($password) > 0 ? "***" . substr($password, -3) : "EMPTY") . "<br><br>";

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ <strong>SUCCESS: Connected to database!</strong><br>";
    
    // Test query
    $stmt = $pdo->query("SELECT DATABASE() as db");
    $result = $stmt->fetch();
    echo "Current database: " . $result['db'] . "<br>";
    
} catch (PDOException $e) {
    echo "❌ <strong>ERROR: " . $e->getMessage() . "</strong><br>";
    echo "<br><strong>Possible issues:</strong><br>";
    echo "1. MySQL service is not running locally<br>";
    echo "2. Database name is different (create/use 'green_exchange')<br>";
    echo "3. Local username/password differ from defaults<br>";
    echo "<br><strong>Action:</strong> Update local credentials in config/database_mysql.php<br>";
}

// Method 2: Test with config file
echo "<br><h3>Method 2: Using config/database_mysql.php</h3>";
if (file_exists(__DIR__ . '/config/database_mysql.php')) {
    require_once __DIR__ . '/config/database_mysql.php';
    try {
        $pdo = getDB();
        echo "✅ SUCCESS: Connection via config file works!<br>";
    } catch (Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ config/database_mysql.php not found<br>";
}

// Show current credentials
echo "<br><h3>Current Configuration:</h3>";
echo "Host: 127.0.0.1<br>";
echo "Port: 3306<br>";
echo "Database: green_exchange<br>";
echo "Username: root<br>";
echo "Password: (empty)<br>";

