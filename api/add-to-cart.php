<?php
/**
 * Add to Cart API
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
$buyer_quantity = floatval($_POST['quantity'] ?? 0);
$buyer_unit = $_POST['cart_unit'] ?? 'kg';

if (!$post_id || $buyer_quantity <= 0) {
    redirect('/pages/browse.php?error=invalid');
}

$pdo = getDB();

// Check if post exists and is available
$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ? AND status = 'available'");
$stmt->execute([$post_id]);
$post = $stmt->fetch();

if (!$post) {
    redirect('/pages/browse.php?error=not_available');
}

// Convert buyer's quantity to post's unit for availability check
$quantity_in_post_unit = $buyer_quantity;
if ($buyer_unit !== $post['unit']) {
    if ($buyer_unit === 'kg' && $post['unit'] === 'tonnes') {
        // Buyer entered kg, post is in tonnes - convert kg to tonnes
        $quantity_in_post_unit = $buyer_quantity / 1000;
    } else if ($buyer_unit === 'tonnes' && $post['unit'] === 'kg') {
        // Buyer entered tonnes, post is in kg - convert tonnes to kg
        $quantity_in_post_unit = $buyer_quantity * 1000;
    }
}

// Check if quantity is available (using converted quantity)
if ($quantity_in_post_unit > $post['quantity']) {
    redirect("/pages/listing.php?id=$post_id&error=insufficient_quantity");
}

// Store in cart: We'll store the buyer's quantity and unit preference
// But we need to check if cart table has unit field. For now, store converted quantity.
// Note: We'll need to add a unit field to cart table or store buyer's preference differently.

// For now, let's store the buyer's quantity in their selected unit
// We'll need to modify the cart table to include buyer_unit, or we can store it differently
// Let me check the cart structure first and then decide

// Check if already in cart
$stmt = $pdo->prepare("SELECT id, quantity, buyer_unit FROM cart WHERE user_id = ? AND post_id = ?");
$stmt->execute([$user['id'], $post_id]);
$existing = $stmt->fetch();

// Store quantity in post's unit for consistency, but also store buyer's unit preference
$quantity_to_store = $quantity_in_post_unit;

if ($existing) {
    // If buyer unit matches existing, add quantities
    // If different unit, convert existing quantity to buyer's unit, add, then convert back
    if ($existing['buyer_unit'] === $buyer_unit) {
        // Same unit - add quantities
        $existing_quantity_in_buyer_unit = $existing['quantity'];
        if ($existing['buyer_unit'] !== $post['unit']) {
            // Convert existing quantity from post unit to buyer unit
            if ($existing['buyer_unit'] === 'kg' && $post['unit'] === 'tonnes') {
                $existing_quantity_in_buyer_unit = $existing['quantity'] * 1000;
            } else if ($existing['buyer_unit'] === 'tonnes' && $post['unit'] === 'kg') {
                $existing_quantity_in_buyer_unit = $existing['quantity'] / 1000;
            }
        }
        $new_quantity_in_buyer_unit = $existing_quantity_in_buyer_unit + $buyer_quantity;
        
        // Convert back to post unit
        $new_quantity_in_post_unit = $new_quantity_in_buyer_unit;
        if ($buyer_unit !== $post['unit']) {
            if ($buyer_unit === 'kg' && $post['unit'] === 'tonnes') {
                $new_quantity_in_post_unit = $new_quantity_in_buyer_unit / 1000;
            } else if ($buyer_unit === 'tonnes' && $post['unit'] === 'kg') {
                $new_quantity_in_post_unit = $new_quantity_in_buyer_unit * 1000;
            }
        }
        
        if ($new_quantity_in_post_unit > $post['quantity']) {
            redirect("/pages/listing.php?id=$post_id&error=insufficient_quantity");
        }
        
        $stmt = $pdo->prepare("UPDATE cart SET quantity = ?, buyer_unit = ? WHERE id = ?");
        $stmt->execute([$new_quantity_in_post_unit, $buyer_unit, $existing['id']]);
    } else {
        // Different unit - replace with new unit and quantity
        $stmt = $pdo->prepare("UPDATE cart SET quantity = ?, buyer_unit = ? WHERE id = ?");
        $stmt->execute([$quantity_to_store, $buyer_unit, $existing['id']]);
    }
} else {
    // Add to cart (store quantity in post's unit, buyer_unit for display)
    $stmt = $pdo->prepare("INSERT INTO cart (user_id, post_id, quantity, buyer_unit) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user['id'], $post_id, $quantity_to_store, $buyer_unit]);
}

redirect('/pages/cart.php?success=added');

