<?php
/**
 * Checkout API - Payment Simulation
 */

require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    redirect('/pages/login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/pages/cart.php');
}

$user = getCurrentUser();
$pdo = getDB();

// Get cart items with post unit information and current quantity
$stmt = $pdo->prepare("
    SELECT c.*, p.price_per_kg, p.unit as post_unit, p.user_id as seller_id, p.title, p.quantity as post_quantity, p.status as post_status
    FROM cart c 
    JOIN posts p ON c.post_id = p.id 
    WHERE c.user_id = ?
");
$stmt->execute([$user['id']]);
$cart_items = $stmt->fetchAll();

if (empty($cart_items)) {
    redirect('/pages/cart.php?error=empty');
}

// Validate quantities and availability before checkout
foreach ($cart_items as $item) {
    // Check if post is still available
    if ($item['post_status'] !== 'available') {
        redirect('/pages/cart.php?error=not_available&post_id=' . $item['post_id']);
    }
    
    // Cart quantity is stored in the post's unit
    if ($item['quantity'] > $item['post_quantity']) {
        redirect('/pages/cart.php?error=insufficient_quantity&post_id=' . $item['post_id']);
    }
    
    // Prevent buying from yourself
    if ($item['seller_id'] == $user['id']) {
        redirect('/pages/cart.php?error=cannot_buy_own');
    }
}

// Start transaction
$pdo->beginTransaction();

try {
    foreach ($cart_items as $item) {
        // Convert cart quantity to kg for price calculation
        // Cart quantity is in the same unit as the post (kg or tonnes)
        $quantity_in_kg = $item['quantity'];
        if ($item['post_unit'] === 'tonnes') {
            $quantity_in_kg = $item['quantity'] * 1000; // Convert tonnes to kg
        }
        
        // Calculate price: quantity (in kg) * price per kg
        $subtotal = $quantity_in_kg * $item['price_per_kg'];
        
        // Get commission rates from database (with fallback to constants)
        $commission_rates = getCommissionRates();
        
        // Calculate commissions
        $buyer_commission = $subtotal * $commission_rates['buyer'];
        $seller_commission = $subtotal * $commission_rates['seller'];
        
        // Buyer pays: subtotal + commission
        $buyer_total = $subtotal + $buyer_commission;
        
        // Seller receives: subtotal - commission
        $seller_total = $subtotal - $seller_commission;
        
        // Create order
        // Note: Including old 'commission' and 'total_price' columns for backward compatibility with existing databases
        $stmt = $pdo->prepare("
            INSERT INTO orders (buyer_id, seller_id, post_id, quantity, unit_price, subtotal, commission, buyer_commission, seller_commission, total_price, buyer_total, seller_total, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([
            $user['id'],
            $item['seller_id'],
            $item['post_id'],
            $item['quantity'],
            $item['price_per_kg'],
            $subtotal,
            $buyer_commission, // Old commission column (using buyer_commission for backward compatibility)
            $buyer_commission,
            $seller_commission,
            $buyer_total, // Old total_price column (using buyer_total for backward compatibility)
            $buyer_total,
            $seller_total
        ]);
        
        $order_id = $pdo->lastInsertId();
        
        // Invoices will be created when order is confirmed by seller
        // (moved to update-order-status.php)
        
        // Update post quantity (cart quantity is in same unit as post)
        $stmt = $pdo->prepare("UPDATE posts SET quantity = quantity - ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$item['quantity'], $item['post_id']]);
        
        // Check if quantity is now 0 or less, update status to 'sold'
        $stmt = $pdo->prepare("SELECT quantity FROM posts WHERE id = ?");
        $stmt->execute([$item['post_id']]);
        $remaining_quantity = $stmt->fetchColumn();
        
        if ($remaining_quantity <= 0) {
            $stmt = $pdo->prepare("UPDATE posts SET status = 'sold', updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$item['post_id']]);
        }
        
        // Notify seller
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, type, message, related_id) 
            VALUES (?, 'new_order', ?, ?)
        ");
        $stmt->execute([$item['seller_id'], "New order for: {$item['title']}", $order_id]);
    }
    
    // Clear cart
    $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    
    $pdo->commit();
    redirect('/pages/orders.php?success=checkout');
    
} catch (Exception $e) {
    $pdo->rollBack();
    $error_message = urlencode($e->getMessage());
    error_log("Checkout error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    redirect('/pages/cart.php?error=checkout_failed&message=' . $error_message);
}

