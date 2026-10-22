<?php
/**
 * Migration: Add buyer_unit field to cart table
 */

require_once __DIR__ . '/../config/database.php';

$pdo = getDB();

try {
    // Add buyer_unit column to cart table
    $pdo->exec("ALTER TABLE cart ADD COLUMN buyer_unit VARCHAR(10) DEFAULT 'kg'");
    
    // Set default buyer_unit based on post unit for existing records
    $pdo->exec("
        UPDATE cart 
        SET buyer_unit = (
            SELECT unit FROM posts WHERE posts.id = cart.post_id
        )
    ");
    
    echo "✅ Migration successful: Added buyer_unit field to cart table\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'duplicate column') !== false) {
        echo "ℹ️  Column buyer_unit already exists. Migration skipped.\n";
    } else {
        echo "❌ Migration failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}



