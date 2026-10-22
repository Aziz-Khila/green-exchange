<?php
/**
 * User Dashboard
 */

$page_title = 'Dashboard';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('/pages/login.php');
}

$user = getCurrentUser();

// Redirect admin to admin dashboard
if ($user['role'] === 'admin') {
    redirect('/pages/admin.php');
}

require_once __DIR__ . '/../includes/header.php';

$pdo = getDB();

// Get user statistics
$total_posts = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
$total_posts->execute([$user['id']]);
$total_posts = $total_posts->fetchColumn();

$active_posts = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ? AND status = 'available'");
$active_posts->execute([$user['id']]);
$active_posts = $active_posts->fetchColumn();

$total_orders = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE buyer_id = ?");
$total_orders->execute([$user['id']]);
$total_orders = $total_orders->fetchColumn();

$total_sales = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE seller_id = ?");
$total_sales->execute([$user['id']]);
$total_sales = $total_sales->fetchColumn();

// Get recent listings
// Note: Post quantity is already updated when orders are created (in checkout.php),
// so p.quantity already represents the remaining quantity
$stmt = $pdo->prepare("
    SELECT p.*, 
           (SELECT image_path FROM post_images WHERE post_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
    FROM posts p 
    WHERE p.user_id = ? 
    ORDER BY p.created_at DESC 
    LIMIT 4
");
$stmt->execute([$user['id']]);
$recent_posts = $stmt->fetchAll();

// Format the remaining quantity for display (post quantity is already the remaining quantity)
foreach ($recent_posts as &$post) {
    // Post quantity is already the remaining quantity (updated in checkout.php)
    $post['remaining_quantity'] = max(0, $post['quantity']);
    $post['remaining_unit'] = $post['unit'];
    
    // Format the remaining quantity for display (this will handle conversion)
    $formatted_remaining = formatQuantity($post['remaining_quantity'], $post['remaining_unit']);
    $post['formatted_remaining'] = $formatted_remaining;
}
unset($post);

// Get recent orders
$stmt = $pdo->prepare("
    SELECT o.*, p.title, u.username as seller_name
    FROM orders o 
    JOIN posts p ON o.post_id = p.id 
    JOIN users u ON o.seller_id = u.id
    WHERE o.buyer_id = ?
    ORDER BY o.created_at DESC 
    LIMIT 3
");
$stmt->execute([$user['id']]);
$recent_orders = $stmt->fetchAll();
?>

<div class="container">
    <div class="dashboard-header">
        <div>
            <h1 class="page-title"><?php echo t('welcome'); ?>, <?php echo escape($user['username']); ?>!</h1>
            <p class="dashboard-subtitle"><?php echo t('manage_account'); ?></p>
        </div>
        <div class="quick-actions-header">
            <a href="/pages/post.php" class="btn btn-primary">+ <?php echo t('post_listing'); ?></a>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="dashboard-stats">
        <div class="stat-card">
            <div class="stat-icon" style="background: #dbeafe; color: #1e40af;">📦</div>
            <div class="stat-content">
                <h3><?php echo $total_posts; ?></h3>
                <p><?php echo t('my_listings'); ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: #d1fae5; color: #065f46;">✓</div>
            <div class="stat-content">
                <h3><?php echo $active_posts; ?></h3>
                <p><?php echo t('active_listings'); ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: #fef3c7; color: #92400e;">🛒</div>
            <div class="stat-content">
                <h3><?php echo $total_orders; ?></h3>
                <p><?php echo t('purchases'); ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: #e0e7ff; color: #3730a3;">💰</div>
            <div class="stat-content">
                <h3><?php echo $total_sales; ?></h3>
                <p><?php echo t('sales'); ?></p>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="dashboard-actions">
        <a href="/pages/post.php" class="action-card">
            <div class="action-icon">📝</div>
            <h3><?php echo t('post_listing'); ?></h3>
            <p><?php echo t('list_for_sale'); ?></p>
        </a>
        <a href="/pages/browse.php" class="action-card">
            <div class="action-icon">🔍</div>
            <h3><?php echo t('browse_materials'); ?></h3>
            <p><?php echo t('find_materials'); ?></p>
        </a>
        <a href="/pages/cart.php" class="action-card">
            <div class="action-icon">🛒</div>
            <h3><?php echo t('shopping_cart'); ?></h3>
            <p><?php echo t('view_cart'); ?></p>
        </a>
    </div>
    
    <!-- Recent Listings -->
    <?php if (!empty($recent_posts)): ?>
    <div class="dashboard-section">
        <div class="section-header-flex">
            <h2><?php echo t('recent_listings'); ?></h2>
            <a href="/pages/my-listings.php"><?php echo t('view'); ?> <?php echo t('all'); ?></a>
        </div>
        <div class="listings-grid">
            <?php foreach ($recent_posts as $post): ?>
                <div class="listing-card">
                    <?php if ($post['primary_image']): ?>
                        <img src="<?php echo escape($post['primary_image']); ?>" alt="<?php echo escape($post['title']); ?>">
                    <?php else: ?>
                        <div class="no-image">No Image</div>
                    <?php endif; ?>
                    <div class="listing-info">
                        <h3><a href="/pages/listing.php?id=<?php echo $post['id']; ?>"><?php echo escape($post['title']); ?></a></h3>
                        <p class="price"><?php echo formatPrice($post['price_per_kg']); ?> / kg</p>
                        <p class="quantity"><?php echo $post['formatted_remaining']; ?> <?php echo t('available'); ?></p>
                        <span class="status-badge status-<?php echo $post['status']; ?>">
                            <?php echo t($post['status']); ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Recent Orders -->
    <?php if (!empty($recent_orders)): ?>
    <div class="dashboard-section">
        <div class="section-header-flex">
            <h2><?php echo t('recent_orders'); ?></h2>
            <a href="/pages/orders.php"><?php echo t('view'); ?> <?php echo t('all'); ?></a>
        </div>
        <div class="orders-list">
            <?php foreach ($recent_orders as $order): ?>
                <div class="order-item-mini">
                    <div class="order-info">
                        <h4><?php echo escape($order['title']); ?></h4>
                        <p><?php echo t('seller'); ?>: <?php echo escape($order['seller_name']); ?></p>
                    </div>
                    <div class="order-meta">
                        <span class="status-badge status-<?php echo $order['status']; ?>">
                            <?php echo t($order['status']); ?>
                        </span>
                        <span class="order-total"><?php echo formatPrice($order['buyer_total']); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 32px;
}

.dashboard-subtitle {
    color: var(--text-gray);
    font-size: 1.1rem;
    margin-top: 8px;
}

.dashboard-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.stat-card {
    background: var(--white);
    border: 1px solid var(--border-light);
    border-radius: 12px;
    padding: 24px;
    display: flex;
    gap: 16px;
    align-items: center;
    transition: all 0.2s;
}

.stat-card.clickable {
    cursor: pointer;
}

.stat-card.clickable:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.stat-content h3 {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 4px;
}

.stat-content p {
    color: var(--text-gray);
    font-size: 0.9rem;
}

.dashboard-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 24px;
    margin-bottom: 48px;
}

.action-card {
    background: var(--white);
    border: 1px solid var(--border-light);
    border-radius: 12px;
    padding: 32px;
    text-decoration: none;
    text-align: center;
    transition: all 0.3s;
}

.action-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
    border-color: var(--primary-green);
}

.action-icon {
    font-size: 3rem;
    margin-bottom: 16px;
}

.action-card h3 {
    font-size: 1.25rem;
    color: var(--text-dark);
    margin-bottom: 8px;
}

.action-card p {
    color: var(--text-gray);
    font-size: 0.9rem;
}

.dashboard-section {
    margin-bottom: 48px;
}

.section-header-flex {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.section-header-flex h2 {
    font-size: 1.75rem;
}

.section-header-flex a {
    color: var(--primary-green);
    text-decoration: none;
    font-weight: 600;
}

.orders-list {
    background: var(--white);
    border: 1px solid var(--border-light);
    border-radius: 12px;
    overflow: hidden;
}

.order-item-mini {
    padding: 20px;
    border-bottom: 1px solid var(--border-light);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.order-item-mini:last-child {
    border-bottom: none;
}

.order-info h4 {
    margin-bottom: 4px;
}

.order-info p {
    color: var(--text-gray);
    font-size: 0.9rem;
}

.order-meta {
    display: flex;
    gap: 16px;
    align-items: center;
}

.order-total {
    font-weight: 700;
    font-size: 1.1rem;
    color: var(--primary-green);
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

