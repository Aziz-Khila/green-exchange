<?php
/**
 * Add to Wishlist API
 */

require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    redirect('/pages/login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/pages/browse.php');
}

$user = getCurrentUser();
$post_id = intval($_POST['post_id'] ?? 0);

if (!$post_id) {
    redirect('/pages/browse.php?error=invalid');
}

$pdo = getDB();

// Check if post exists
$stmt = $pdo->prepare("SELECT id FROM posts WHERE id = ?");
$stmt->execute([$post_id]);
$post = $stmt->fetch();

if (!$post) {
    redirect('/pages/browse.php?error=not_found');
}

// Check if already in wishlist
$stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND post_id = ?");
$stmt->execute([$user['id'], $post_id]);
$existing = $stmt->fetch();

if ($existing) {
    redirect('/pages/wishlist.php?error=already_in_wishlist');
}

// Add to wishlist
$stmt = $pdo->prepare("INSERT INTO wishlist (user_id, post_id) VALUES (?, ?)");
$stmt->execute([$user['id'], $post_id]);

redirect('/pages/wishlist.php?success=added');


