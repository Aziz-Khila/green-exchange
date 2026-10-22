<?php
/**
 * Admin - Delete User
 */

require_once __DIR__ . '/../includes/functions.php';

// Check if user is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('/');
}

$user_id = $_GET['id'] ?? 0;

if (!$user_id) {
    redirect('/pages/admin-users.php?error=invalid');
}

// Prevent deleting admin users
$pdo = getDB();
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($user && $user['role'] === 'admin') {
    redirect('/pages/admin-users.php?error=cannot_delete_admin');
}

// Delete user (cascade will delete related data)
$stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
$stmt->execute([$user_id]);

redirect('/pages/admin-users.php?success=user_deleted');

