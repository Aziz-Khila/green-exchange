<?php
/**
 * Remove from Wishlist API
 */

require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    redirect('/pages/login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/pages/wishlist.php');
}

$user = getCurrentUser();
$post_id = intval($_POST['post_id'] ?? 0);

if (!$post_id) {
    redirect('/pages/wishlist.php?error=invalid');
}

$pdo = getDB();

// Remove from wishlist
$stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND post_id = ?");
$stmt->execute([$user['id'], $post_id]);

redirect('/pages/wishlist.php?success=removed');


