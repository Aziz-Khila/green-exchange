<?php
/**
 * Update Cart Quantity API
 */

require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    redirect('/pages/login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/pages/cart.php');
}

$user = getCurrentUser();
$cart_id = intval($_POST['cart_id'] ?? 0);
$buyer_quantity = floatval($_POST['quantity'] ?? 0);
$buyer_unit = $_POST['buyer_unit'] ?? 'kg';

if (!$cart_id || $buyer_quantity <= 0) {
    redirect('/pages/cart.php?error=invalid');
}

$pdo = getDB();

// Get cart item with post info
$stmt = $pdo->prepare("
    SELECT c.*, p.quantity as post_quantity, p.unit as post_unit, p.status as post_status
    FROM cart c 
    JOIN posts p ON c.post_id = p.id 
    WHERE c.id = ? AND c.user_id = ?
");
$stmt->execute([$cart_id, $user['id']]);
$cart_item = $stmt->fetch();

if (!$cart_item) {
    redirect('/pages/cart.php?error=not_found');
}

// Check if post is still available
if ($cart_item['post_status'] !== 'available') {
    // Remove item from cart if post is no longer available
    $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $stmt->execute([$cart_id, $user['id']]);
    redirect('/pages/cart.php?error=not_available');
}

// Convert buyer's quantity to post's unit
$quantity_in_post_unit = $buyer_quantity;
if ($buyer_unit !== $cart_item['post_unit']) {
    if ($buyer_unit === 'kg' && $cart_item['post_unit'] === 'tonnes') {
        $quantity_in_post_unit = $buyer_quantity / 1000;
    } else if ($buyer_unit === 'tonnes' && $cart_item['post_unit'] === 'kg') {
        $quantity_in_post_unit = $buyer_quantity * 1000;
    }
}

// Check if quantity is available
if ($quantity_in_post_unit > $cart_item['post_quantity']) {
    redirect('/pages/cart.php?error=insufficient_quantity');
}

// Update cart quantity (store in post's unit) and buyer_unit
$stmt = $pdo->prepare("UPDATE cart SET quantity = ?, buyer_unit = ? WHERE id = ? AND user_id = ?");
$stmt->execute([$quantity_in_post_unit, $buyer_unit, $cart_id, $user['id']]);

redirect('/pages/cart.php');

