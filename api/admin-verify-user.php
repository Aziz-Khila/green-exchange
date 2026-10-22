<?php
/**
 * Admin - Verify User
 */

require_once __DIR__ . '/../includes/functions.php';

// Check if user is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('/');
}

$user_id = $_GET['id'] ?? 0;

if (!$user_id) {
    redirect('/pages/admin.php?error=invalid');
}

$pdo = getDB();

// Verify user
$stmt = $pdo->prepare("UPDATE users SET verified = 1 WHERE id = ?");
$stmt->execute([$user_id]);

// Create notification for user
$stmt = $pdo->prepare("
    INSERT INTO notifications (user_id, type, message) 
    VALUES (?, 'account_verified', 'Your account has been verified! You can now start trading.')
");
$stmt->execute([$user_id]);

redirect('/pages/admin.php?success=user_verified');

