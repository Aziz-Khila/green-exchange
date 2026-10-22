<?php
/**
 * Delete Post API - User can delete their own posts
 */

require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('/pages/login.php');
}

$post_id = $_GET['id'] ?? 0;

if (!$post_id) {
    redirect('/pages/profile.php?error=invalid');
}

$user = getCurrentUser();
$pdo = getDB();

// Check if post belongs to user or if user is admin
$stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
$stmt->execute([$post_id]);
$post = $stmt->fetch();

if (!$post) {
    redirect('/pages/profile.php?error=not_found');
}

// Only owner or admin can delete
if ($post['user_id'] != $user['id'] && $user['role'] !== 'admin') {
    redirect('/pages/profile.php?error=unauthorized');
}

// Delete post (cascade will delete images and related data)
$stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
$stmt->execute([$post_id]);

// Redirect based on user role
if ($user['role'] === 'admin') {
    redirect('/pages/admin.php?success=post_deleted');
} else {
    redirect('/pages/profile.php?success=deleted');
}

