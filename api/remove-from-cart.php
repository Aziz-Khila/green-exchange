<?php
/**
 * Remove from Cart API
 */

require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    redirect('/pages/login.php');
}

$cart_id = intval($_GET['id'] ?? 0);

if (!$cart_id) {
    redirect('/pages/cart.php');
}

$user = getCurrentUser();
$pdo = getDB();

// Delete cart item (only if it belongs to the user)
$stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
$stmt->execute([$cart_id, $user['id']]);

redirect('/pages/cart.php?success=removed');

