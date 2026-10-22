<?php
/**
 * Quick verification script to check database tables
 */

$db_file = __DIR__ . '/database/green_exchange.db';

try {
    $pdo = new PDO('sqlite:' . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "✅ Database verification:\n";
    echo "Total tables: " . count($tables) . "\n\n";
    echo "Tables list:\n";
    foreach ($tables as $table) {
        $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        echo "  - $table ($count rows)\n";
    }
    
    echo "\n✅ Database structure verified successfully!\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

