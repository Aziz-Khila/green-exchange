<?php
/**
 * Orders Page - User's Purchase History
 */

$page_title = 'My Orders';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('/pages/login.php');
}

$user = getCurrentUser();
require_once __DIR__ . '/../includes/header.php';

$pdo = getDB();

// Get user's orders (as buyer)
$stmt = $pdo->prepare("
    SELECT o.*, p.title, p.unit,
           u.username as seller_name,
           (SELECT image_path FROM post_images WHERE post_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
    FROM orders o 
    JOIN posts p ON o.post_id = p.id 
    JOIN users u ON o.seller_id = u.id
    WHERE o.buyer_id = ?
    ORDER BY o.created_at DESC
");
$stmt->execute([$user['id']]);
$orders = $stmt->fetchAll();
?>

<div class="container">
    <h1 class="page-title"><?php echo t('my_orders'); ?></h1>
    
    <?php if (isset($_GET['success']) && $_GET['success'] === 'checkout'): ?>
        <div class="alert alert-success">
            <?php echo t('order_placed_success'); ?>
        </div>
    <?php endif; ?>
    
    <!-- Order Actions for Buyers -->
    <?php if (!empty($orders)): ?>
        <?php 
        $has_pending = false;
        foreach ($orders as $order) {
            if ($order['status'] === 'pending' || $order['status'] === 'confirmed') {
                $has_pending = true;
                break;
            }
        }
        ?>
    <?php endif; ?>
    
    <?php if (empty($orders)): ?>
        <div class="empty-state">
            <p><?php echo t('no_orders_yet'); ?></p>
            <a href="/pages/browse.php" class="btn btn-primary"><?php echo t('browse'); ?></a>
        </div>
    <?php else: ?>
        <div class="orders-grid">
            <?php foreach ($orders as $order): ?>
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
                            <h4><?php echo escape($order['title']); ?></h4>
                            <p><?php echo t('seller'); ?>: <?php echo escape($order['seller_name']); ?></p>
                            <p><?php echo t('quantity'); ?>: <?php echo $order['quantity']; ?> <?php echo $order['unit']; ?></p>
                        </div>
                    </div>
                    
                    <div class="order-summary">
                        <div class="summary-row">
                            <span><?php echo t('subtotal'); ?></span>
                            <span><?php echo formatPrice($order['subtotal']); ?></span>
                        </div>
                        <div class="summary-row">
                            <span><?php echo t('commission'); ?> (+<?php echo (BUYER_COMMISSION_RATE * 100); ?>%)</span>
                            <span>+<?php echo formatPrice($order['buyer_commission']); ?></span>
                        </div>
                        <div class="summary-row total">
                            <span><?php echo t('cart_total'); ?></span>
                            <span><?php echo formatPrice($order['buyer_total']); ?></span>
                        </div>
                    </div>
                    
                    <!-- Buyer Actions -->
                    <?php if ($order['status'] === 'pending' || $order['status'] === 'confirmed'): ?>
                        <div class="order-actions">
                            <form action="/api/update-order-status.php" method="POST" style="display: inline;">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <input type="hidden" name="status" value="cancelled">
                                <button type="submit" class="btn btn-danger btn-small" onclick="return confirm('<?php echo t('cancel_order_confirm'); ?>')"><?php echo t('cancel_order'); ?></button>
                            </form>
                        </div>
                    <?php elseif ($order['status'] === 'cancelled' && $order['cancelled_by'] === 'buyer'): ?>
                        <div class="order-actions">
                            <form action="/api/update-order-status.php" method="POST" style="display: inline;">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <input type="hidden" name="status" value="pending">
                                <button type="submit" class="btn btn-outline btn-small" onclick="return confirm('<?php echo t('reactivate_order_confirm'); ?>')"><?php echo t('reactivate_order'); ?></button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
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

.order-item-info p {
    font-size: 0.9rem;
    color: var(--text-gray);
    margin: 4px 0;
}

.order-summary {
    padding-top: 16px;
    border-top: 1px solid var(--border-light);
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
    gap: 8px;
    flex-wrap: wrap;
    padding-top: 16px;
    border-top: 1px solid var(--border-light);
    margin-top: 16px;
}

.btn-small {
    padding: 8px 16px;
    font-size: 0.9rem;
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

