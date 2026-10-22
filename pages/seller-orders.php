<?php
/**
 * Seller Orders Page - Orders for user's listings
 */

$page_title = 'My Sales';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('/pages/login.php');
}

$user = getCurrentUser();
require_once __DIR__ . '/../includes/header.php';

$pdo = getDB();

// Get user's orders (as seller)
$stmt = $pdo->prepare("
    SELECT o.*, p.title, p.unit,
           u.username as buyer_name, u.email as buyer_email, u.phone as buyer_phone,
           (SELECT image_path FROM post_images WHERE post_id = p.id AND is_primary = 1 LIMIT 1) as primary_image,
           COALESCE(o.seller_commission, o.subtotal * 0.05) as seller_commission,
           COALESCE(o.seller_total, o.subtotal * 0.95) as seller_total
    FROM orders o 
    JOIN posts p ON o.post_id = p.id 
    JOIN users u ON o.buyer_id = u.id
    WHERE o.seller_id = ?
    ORDER BY o.created_at DESC
");
$stmt->execute([$user['id']]);
$seller_orders = $stmt->fetchAll();

// Get statistics
$total_orders = count($seller_orders);
$pending_orders = count(array_filter($seller_orders, fn($o) => $o['status'] === 'pending'));
// Confirmed sales include: confirmed, pending_completion, and completed (all are confirmed sales)
$confirmed_orders = count(array_filter($seller_orders, fn($o) => in_array($o['status'], ['confirmed', 'pending_completion', 'completed'])));
$pending_completion_orders = count(array_filter($seller_orders, fn($o) => $o['status'] === 'pending_completion'));
$completed_orders = count(array_filter($seller_orders, fn($o) => $o['status'] === 'completed'));
$cancelled_orders = count(array_filter($seller_orders, fn($o) => $o['status'] === 'cancelled'));

// Calculate total revenue (seller receives subtotal - commission) - only for confirmed orders
$confirmed_orders_list = array_filter($seller_orders, fn($o) => in_array($o['status'], ['confirmed', 'pending_completion', 'completed']));
$total_revenue = array_sum(array_column($confirmed_orders_list, 'subtotal'));
$total_commission = 0;
$total_received = 0;
foreach ($confirmed_orders_list as $order) {
    $total_commission += $order['seller_commission'] ?? ($order['subtotal'] * SELLER_COMMISSION_RATE);
    $total_received += $order['seller_total'] ?? ($order['subtotal'] * (1 - SELLER_COMMISSION_RATE));
}
?>

<div class="container">
    <h1 class="page-title"><?php echo t('my_sales'); ?></h1>
    
    <?php if (isset($_GET['success']) && $_GET['success'] === 'updated'): ?>
        <div class="alert alert-success">
            <?php echo t('order_status_updated'); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error">
            <?php 
            $errors = [
                'update_failed' => t('order_update_failed'),
                'invalid' => t('invalid_input'),
                'unauthorized' => t('unauthorized_action')
            ];
            $error_message = $errors[$_GET['error']] ?? t('error');
            
            // Show custom error message if provided
            if (isset($_GET['message']) && !empty($_GET['message'])) {
                $error_message = escape(urldecode($_GET['message']));
            }
            
            echo $error_message;
            ?>
        </div>
    <?php endif; ?>
    
    <!-- Statistics -->
    <div class="orders-stats">
        <div class="stat-card">
            <div class="stat-icon" style="background: #dbeafe; color: #1e40af;">📦</div>
            <div class="stat-content">
                <h3><?php echo $total_orders; ?></h3>
                <p><?php echo t('total_orders'); ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: #fef3c7; color: #92400e;">⏳</div>
            <div class="stat-content">
                <h3><?php echo $pending_orders; ?></h3>
                <p><?php echo t('pending'); ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: #d1fae5; color: #065f46;">✓</div>
            <div class="stat-content">
                <h3><?php echo $confirmed_orders; ?></h3>
                <p><?php echo t('confirmed'); ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: #e0e7ff; color: #3730a3;">💰</div>
            <div class="stat-content">
                <h3><?php echo formatPrice($total_received); ?></h3>
                <p><?php echo t('total_received'); ?></p>
            </div>
        </div>
    </div>
    
    <?php if (empty($seller_orders)): ?>
        <div class="empty-state">
            <div class="empty-icon">📋</div>
            <h2><?php echo t('no_sales_yet'); ?></h2>
            <p><?php echo t('no_sales_description'); ?></p>
        </div>
    <?php else: ?>
        <div class="orders-grid">
            <?php foreach ($seller_orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <h3><?php echo t('order'); ?> #<?php echo $order['id']; ?></h3>
                            <p class="order-date"><?php echo formatDate($order['created_at']); ?></p>
                        </div>
                        <span class="status-badge status-<?php echo $order['status']; ?>">
                            <?php echo t($order['status']); ?>
                        </span>
                    </div>
                    
                    <div class="order-item">
                        <?php if ($order['primary_image']): ?>
                            <img src="<?php echo escape($order['primary_image']); ?>" alt="<?php echo escape($order['title']); ?>">
                        <?php endif; ?>
                        <div class="order-item-info">
                            <h4><a href="/pages/listing.php?id=<?php echo $order['post_id']; ?>"><?php echo escape($order['title']); ?></a></h4>
                            <p><strong><?php echo t('buyer'); ?>:</strong> <?php echo escape($order['buyer_name']); ?></p>
                            <p><strong><?php echo t('quantity'); ?>:</strong> <?php echo $order['quantity']; ?> <?php echo $order['unit']; ?></p>
                            <?php if ($order['buyer_phone']): ?>
                                <p><strong><?php echo t('phone'); ?>:</strong> <a href="tel:<?php echo escape($order['buyer_phone']); ?>"><?php echo escape($order['buyer_phone']); ?></a></p>
                            <?php endif; ?>
                            <p><strong><?php echo t('email'); ?>:</strong> <a href="mailto:<?php echo escape($order['buyer_email']); ?>"><?php echo escape($order['buyer_email']); ?></a></p>
                        </div>
                    </div>
                    
                    <div class="order-summary">
                        <div class="summary-row">
                            <span><?php echo t('subtotal'); ?></span>
                            <span><?php echo formatPrice($order['subtotal']); ?></span>
                        </div>
                        <div class="summary-row">
                            <span><?php echo t('commission'); ?> (-<?php echo (SELLER_COMMISSION_RATE * 100); ?>%)</span>
                            <span>-<?php echo formatPrice($order['seller_commission'] ?? ($order['subtotal'] * SELLER_COMMISSION_RATE)); ?></span>
                        </div>
                        <div class="summary-row total">
                            <span><?php echo t('you_receive'); ?></span>
                            <span><?php echo formatPrice($order['seller_total'] ?? ($order['subtotal'] * (1 - SELLER_COMMISSION_RATE))); ?></span>
                        </div>
                    </div>
                    
                    <!-- Order Actions -->
                    <div class="order-actions">
                        <?php if ($order['status'] === 'pending'): ?>
                            <a href="#" class="btn btn-success" onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'confirmed'); return false;"><?php echo t('confirm_order'); ?></a>
                            <a href="#" class="btn btn-danger" onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'cancelled'); return false;"><?php echo t('cancel_order'); ?></a>
                        <?php elseif ($order['status'] === 'confirmed'): ?>
                            <a href="#" class="btn btn-primary" onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'pending_completion'); return false;"><?php echo t('mark_completed'); ?></a>
                            <a href="#" class="btn btn-danger" onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'cancelled'); return false;"><?php echo t('cancel_order'); ?></a>
                        <?php elseif ($order['status'] === 'pending_completion'): ?>
                            <span class="status-badge status-pending_completion" style="padding: 8px 16px; border-radius: 6px; background: #fef3c7; color: #92400e;">
                                <?php echo t('pending_admin_approval'); ?>
                            </span>
                        <?php elseif ($order['status'] === 'cancelled' && $order['cancelled_by'] === 'seller'): ?>
                            <a href="#" class="btn btn-outline" onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'pending'); return false;"><?php echo t('reactivate_order'); ?></a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.orders-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 24px;
    margin-bottom: 40px;
}

.stat-card {
    background: var(--white);
    padding: 24px;
    border-radius: 12px;
    border: 1px solid var(--border-light);
    display: flex;
    align-items: center;
    gap: 16px;
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.stat-content h3 {
    font-size: 2rem;
    color: var(--text-dark);
    margin-bottom: 4px;
}

.stat-content p {
    color: var(--text-gray);
    font-size: 0.9rem;
}

.orders-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 24px;
    margin-top: 32px;
}

.order-card {
    background: var(--white);
    border: 1px solid var(--border-light);
    border-radius: 16px;
    padding: 24px;
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--border-light);
}

.order-header h3 {
    margin-bottom: 4px;
}

.order-date {
    font-size: 0.85rem;
    color: var(--text-gray);
}

.order-item {
    display: flex;
    gap: 16px;
    margin-bottom: 20px;
}

.order-item img {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 8px;
}

.order-item-info h4 {
    margin-bottom: 8px;
}

.order-item-info h4 a {
    color: var(--primary-green);
    text-decoration: none;
}

.order-item-info h4 a:hover {
    text-decoration: underline;
}

.order-item-info p {
    font-size: 0.9rem;
    color: var(--text-gray);
    margin: 4px 0;
}

.order-item-info a {
    color: var(--primary-green);
    text-decoration: none;
}

.order-summary {
    padding-top: 16px;
    border-top: 1px solid var(--border-light);
    margin-bottom: 16px;
}

.order-summary .summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    font-size: 0.95rem;
}

.order-summary .total {
    font-weight: 700;
    font-size: 1.1rem;
    color: var(--primary-green);
    margin-top: 8px;
}

.order-actions {
    display: flex;
    gap: 12px;
    padding-top: 16px;
    border-top: 1px solid var(--border-light);
}

.order-actions .btn {
    flex: 1;
    text-align: center;
    padding: 12px 20px;
    font-size: 0.95rem;
    font-weight: 600;
}

.order-actions .btn-primary {
    flex: 2;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: var(--white);
    border-radius: 16px;
    border: 1px solid var(--border-light);
    margin-top: 32px;
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: 24px;
}

.empty-state h2 {
    font-size: 1.5rem;
    margin-bottom: 12px;
    color: var(--text-dark);
}

.empty-state p {
    color: var(--text-gray);
    font-size: 1rem;
}
</style>

<script>
function updateOrderStatus(orderId, status) {
    if (status === 'cancelled') {
        if (!confirm('<?php echo addslashes(t('cancel_order_confirm')); ?>')) {
            return false;
        }
    } else if (status === 'pending') {
        // Reactivate order confirmation
        if (!confirm('<?php echo addslashes(t('reactivate_order_confirm')); ?>')) {
            return false;
        }
    }
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/api/update-order-status.php';
    
    const orderIdInput = document.createElement('input');
    orderIdInput.type = 'hidden';
    orderIdInput.name = 'order_id';
    orderIdInput.value = orderId;
    
    const statusInput = document.createElement('input');
    statusInput.type = 'hidden';
    statusInput.name = 'status';
    statusInput.value = status;
    
    form.appendChild(orderIdInput);
    form.appendChild(statusInput);
    document.body.appendChild(form);
    form.submit();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>


