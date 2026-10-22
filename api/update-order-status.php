<?php
/**
 * Update Order Status API
 */

require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    redirect('/pages/login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/pages/orders.php');
}

$user = getCurrentUser();
$pdo = getDB();

$order_id = $_POST['order_id'] ?? 0;
$new_status = $_POST['status'] ?? '';

if (!$order_id || !in_array($new_status, ['pending', 'confirmed', 'pending_completion', 'completed', 'cancelled'])) {
    redirect('/pages/orders.php?error=invalid');
}

// Get order details
$stmt = $pdo->prepare("
    SELECT o.*, p.quantity as post_quantity, p.unit as post_unit, p.status as post_status
    FROM orders o
    JOIN posts p ON o.post_id = p.id
    WHERE o.id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    redirect('/pages/orders.php?error=not_found');
}

// Check if user is authorized (seller or buyer)
$is_seller = $order['seller_id'] == $user['id'];
$is_buyer = $order['buyer_id'] == $user['id'];

if (!$is_seller && !$is_buyer && $user['role'] !== 'admin') {
    redirect('/pages/orders.php?error=unauthorized');
}

// Start transaction
$pdo->beginTransaction();

try {
    $old_status = $order['status'];
    
    // Update order status (and clear cancelled_by if reactivating)
    if ($old_status === 'cancelled' && $new_status === 'pending') {
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET status = ?, cancelled_by = NULL, updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
    } else {
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET status = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
    }
    $stmt->execute([$new_status, $order_id]);
    
    // Handle quantity management based on status change
    if (($old_status === 'pending' || $old_status === 'confirmed') && $new_status === 'cancelled') {
        // Determine who cancelled
        $cancelled_by_role = $is_seller ? 'seller' : 'buyer';
        
        // Update order with cancelled_by information
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET cancelled_by = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        $stmt->execute([$cancelled_by_role, $order_id]);
        
        // Restore quantity when order is cancelled (from pending or confirmed)
        $stmt = $pdo->prepare("
            UPDATE posts 
            SET quantity = quantity + ?, 
                status = CASE 
                    WHEN status = 'sold' THEN 'available'
                    ELSE status
                END,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([$order['quantity'], $order['post_id']]);
        
        // Notify the other party (buyer if seller cancelled, seller if buyer cancelled)
        $notify_user_id = $is_seller ? $order['buyer_id'] : $order['seller_id'];
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, type, message, related_id) 
            VALUES (?, 'order_cancelled', ?, ?)
        ");
        $stmt->execute([
            $notify_user_id, 
            "Order #{$order_id} has been cancelled by the {$cancelled_by_role}. Quantity restored to listing.",
            $order_id
        ]);
    } elseif ($old_status === 'cancelled' && $new_status === 'pending') {
        // Only the person who cancelled can reactivate
        $cancelled_by = $order['cancelled_by'] ?? null;
        
        if (!$cancelled_by) {
            // Legacy orders without cancelled_by - allow seller to reactivate (backward compatibility)
            if (!$is_seller && $user['role'] !== 'admin') {
                throw new Exception('Only the person who cancelled the order can reactivate it');
            }
        } else {
            // Check if current user is the one who cancelled
            $can_reactivate = false;
            if ($cancelled_by === 'buyer' && $is_buyer) {
                $can_reactivate = true;
            } elseif ($cancelled_by === 'seller' && $is_seller) {
                $can_reactivate = true;
            }
            
            if (!$can_reactivate && $user['role'] !== 'admin') {
                throw new Exception('Only the person who cancelled the order can reactivate it');
            }
        }
        
        // Check if sufficient quantity is available
        $stmt = $pdo->prepare("SELECT quantity FROM posts WHERE id = ?");
        $stmt->execute([$order['post_id']]);
        $current_quantity = $stmt->fetchColumn();
        
        if ($current_quantity < $order['quantity']) {
            throw new Exception('Insufficient quantity available. Only ' . $current_quantity . ' ' . $order['post_unit'] . ' available, but order requires ' . $order['quantity'] . ' ' . $order['post_unit']);
        }
        
        // Deduct quantity
        $stmt = $pdo->prepare("
            UPDATE posts 
            SET quantity = quantity - ?, 
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([$order['quantity'], $order['post_id']]);
        
        // Update post status if quantity becomes 0
        $stmt = $pdo->prepare("SELECT quantity FROM posts WHERE id = ?");
        $stmt->execute([$order['post_id']]);
        $remaining_quantity = $stmt->fetchColumn();
        
        if ($remaining_quantity <= 0) {
            $stmt = $pdo->prepare("UPDATE posts SET status = 'sold', updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$order['post_id']]);
        }
        
        // Notify the other party that order has been reactivated
        $reactivated_by = $is_seller ? 'seller' : 'buyer';
        $notify_user_id = $is_seller ? $order['buyer_id'] : $order['seller_id'];
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, type, message, related_id) 
            VALUES (?, 'order_reactivated', ?, ?)
        ");
        $stmt->execute([
            $notify_user_id, 
            "Order #{$order_id} has been reactivated by the {$reactivated_by}.",
            $order_id
        ]);
    }
    
    // Send notifications and create invoices
    if ($new_status === 'confirmed') {
        // Create invoices when order is confirmed
        // Check if invoices already exist (to avoid duplicates)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE order_id = ?");
        $stmt->execute([$order_id]);
        $invoice_exists = $stmt->fetchColumn() > 0;
        
        if (!$invoice_exists) {
            // Get order details with commission info
            $stmt = $pdo->prepare("
                SELECT buyer_id, seller_id, subtotal, buyer_commission, seller_commission, buyer_total, seller_total
                FROM orders
                WHERE id = ?
            ");
            $stmt->execute([$order_id]);
            $order_details = $stmt->fetch();
            
            if ($order_details) {
                // Create buyer invoice (buyer pays subtotal + commission)
                $stmt = $pdo->prepare("
                    INSERT INTO invoices (order_id, user_id, invoice_type, invoice_role, amount, commission, total) 
                    VALUES (?, ?, 'order', 'buyer', ?, ?, ?)
                ");
                $stmt->execute([
                    $order_id, 
                    $order_details['buyer_id'], 
                    $order_details['subtotal'], 
                    $order_details['buyer_commission'], 
                    $order_details['buyer_total']
                ]);
                
                // Create seller invoice (seller receives subtotal - commission)
                $stmt = $pdo->prepare("
                    INSERT INTO invoices (order_id, user_id, invoice_type, invoice_role, amount, commission, total) 
                    VALUES (?, ?, 'order', 'seller', ?, ?, ?)
                ");
                $stmt->execute([
                    $order_id, 
                    $order_details['seller_id'], 
                    $order_details['subtotal'], 
                    $order_details['seller_commission'], 
                    $order_details['seller_total']
                ]);
            }
        }
        
        // Notify buyer
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, type, message, related_id) 
            VALUES (?, 'order_confirmed', ?, ?)
        ");
        $stmt->execute([
            $order['buyer_id'], 
            "Order #{$order_id} has been confirmed by the seller.",
            $order_id
        ]);
    } elseif ($new_status === 'pending_completion') {
        // Seller has marked order as ready for completion - notify admin
        $stmt = $pdo->prepare("
            SELECT id FROM users WHERE role = 'admin' LIMIT 1
        ");
        $stmt->execute();
        $admin = $stmt->fetch();
        
        if ($admin) {
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, type, message, related_id) 
                VALUES (?, 'order_pending_completion', ?, ?)
            ");
            $stmt->execute([
                $admin['id'], 
                "Order #{$order_id} is pending admin approval for completion.",
                $order_id
            ]);
        }
        
        // Notify buyer
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, type, message, related_id) 
            VALUES (?, 'order_pending_completion', ?, ?)
        ");
        $stmt->execute([
            $order['buyer_id'], 
            "Order #{$order_id} has been marked as completed by seller, awaiting admin approval.",
            $order_id
        ]);
    } elseif ($new_status === 'completed') {
        // Only admin can mark orders as completed
        if ($user['role'] !== 'admin') {
            throw new Exception('Only admin can mark orders as completed');
        }
        
        // Notify both buyer and seller
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, type, message, related_id) 
            VALUES (?, 'order_completed', ?, ?)
        ");
        $stmt->execute([
            $order['buyer_id'], 
            "Order #{$order_id} has been completed and approved by admin.",
            $order_id
        ]);
        $stmt->execute([
            $order['seller_id'], 
            "Order #{$order_id} has been completed and approved by admin.",
            $order_id
        ]);
    }
    
    $pdo->commit();
    
    // Redirect based on user role
    if ($is_seller) {
        redirect('/pages/seller-orders.php?success=updated');
    } else {
        redirect('/pages/orders.php?success=updated');
    }
    
} catch (Exception $e) {
    $pdo->rollBack();
    $error_message = urlencode($e->getMessage());
    
    // Redirect based on user role
    if ($is_seller) {
        redirect('/pages/seller-orders.php?error=update_failed&message=' . $error_message);
    } else {
        redirect('/pages/orders.php?error=update_failed&message=' . $error_message);
    }
}


