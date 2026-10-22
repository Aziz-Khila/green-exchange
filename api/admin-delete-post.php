<?php
/**
 * Admin - Delete Post
 */

require_once __DIR__ . '/../includes/functions.php';

// Check if user is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('/');
}

$post_id = $_GET['id'] ?? 0;

if (!$post_id) {
    redirect('/pages/admin.php?error=invalid');
}

$pdo = getDB();

// Delete post (cascade will delete related images, etc.)
$stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
$stmt->execute([$post_id]);

redirect('/pages/admin.php?success=post_deleted');

