<?php
/**
 * Admin Dashboard
 */

$page_title = 'Admin Dashboard';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('/');
}

$admin_page = true; // Flag for admin styling
require_once __DIR__ . '/../includes/header.php';

$pdo = getDB();

// Get statistics
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_posts = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
// Admin revenue is the total commissions earned
$total_revenue = $pdo->query("SELECT SUM(buyer_commission + seller_commission) FROM orders WHERE status IN ('confirmed', 'pending_completion', 'completed')")->fetchColumn() ?? 0;

// Get recent posts
$stmt = $pdo->query("SELECT p.*, u.username FROM posts p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC LIMIT 10");
$recent_posts = $stmt->fetchAll();

// Get pending completion orders
$stmt = $pdo->prepare("
    SELECT o.*, p.title as post_title, 
           buyer.username as buyer_name, seller.username as seller_name
    FROM orders o
    JOIN posts p ON o.post_id = p.id
    JOIN users buyer ON o.buyer_id = buyer.id
    JOIN users seller ON o.seller_id = seller.id
    WHERE o.status = 'pending_completion'
    ORDER BY o.updated_at DESC
");
$stmt->execute();
$pending_completions = $stmt->fetchAll();
?>

<div class="container">
    <div class="admin-header">
        <h1 class="page-title">Admin Dashboard</h1>
        <p class="admin-subtitle">Manage users, listings, and platform settings</p>
    </div>
    
    <!-- Statistics Cards -->
    <div class="admin-stats-grid">
        <div class="admin-stat-card stat-card-primary">
            <div class="stat-icon-wrapper">
                <div class="stat-icon" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($total_users); ?></h3>
                <p>Total Users</p>
            </div>
        </div>
        
        <div class="admin-stat-card stat-card-success">
            <div class="stat-icon-wrapper">
                <div class="stat-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                    <i class="fa-solid fa-box-open"></i>
                </div>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($total_posts); ?></h3>
                <p>Total Listings</p>
            </div>
        </div>
        
        <div class="admin-stat-card stat-card-warning">
            <div class="stat-icon-wrapper">
                <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                    <i class="fa-solid fa-shopping-cart"></i>
                </div>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($total_orders); ?></h3>
                <p>Total Orders</p>
            </div>
        </div>
        
        <div class="admin-stat-card stat-card-info">
            <div class="stat-icon-wrapper">
                <div class="stat-icon" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">
                    <i class="fa-solid fa-dollar-sign"></i>
                </div>
            </div>
            <div class="stat-content">
                <h3><?php echo formatPrice($total_revenue); ?></h3>
                <p>Total Revenue</p>
            </div>
        </div>
    </div>
    
    <!-- Pending Completion Orders -->
    <?php if (!empty($pending_completions)): ?>
    <div class="admin-section">
        <h2 class="section-title">Pending Completion Approvals</h2>
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Product</th>
                        <th>Buyer</th>
                        <th>Seller</th>
                        <th>Quantity</th>
                        <th>Total</th>
                        <th>Requested</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending_completions as $order): ?>
                    <tr>
                        <td>#<?php echo $order['id']; ?></td>
                        <td><?php echo escape($order['post_title']); ?></td>
                        <td><?php echo escape($order['buyer_name']); ?></td>
                        <td><?php echo escape($order['seller_name']); ?></td>
                        <td><?php echo formatQuantity($order['quantity'], $order['unit'] ?? 'kg'); ?></td>
                        <td><?php echo formatPrice($order['buyer_total'] ?? $order['subtotal']); ?></td>
                        <td><?php echo formatDate($order['updated_at']); ?></td>
                        <td>
                            <a href="/pages/listing.php?id=<?php echo $order['post_id']; ?>" class="btn-small btn-primary" target="_blank">View</a>
                            <form action="/api/update-order-status.php" method="POST" class="inline-form">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <input type="hidden" name="status" value="completed">
                                <button type="submit" class="btn-small btn-success" onclick="return confirm('Approve and complete this order?')">Approve</button>
                            </form>
                            <form action="/api/update-order-status.php" method="POST" class="inline-form">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <input type="hidden" name="status" value="cancelled">
                                <button type="submit" class="btn-small btn-danger" onclick="return confirm('Reject this completion request?')">Reject</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Recent Posts -->
    <div class="admin-section">
        <h2 class="section-title">Recent Listings</h2>
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th><?php echo t('title'); ?></th>
                        <th><?php echo t('posted_by'); ?></th>
                        <th><?php echo t('category'); ?></th>
                        <th><?php echo t('price_per_kg'); ?></th>
                        <th><?php echo t('order_status'); ?></th>
                        <th><?php echo t('posted'); ?></th>
                        <th><?php echo t('actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_posts as $post): ?>
                    <tr>
                        <td><?php echo escape($post['title']); ?></td>
                        <td><?php echo escape($post['username']); ?></td>
                        <td><?php echo t('category_' . $post['category']); ?></td>
                        <td><?php echo formatPrice($post['price_per_kg']); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $post['status']; ?>">
                                <?php echo t($post['status']); ?>
                            </span>
                        </td>
                        <td><?php echo formatDate($post['created_at']); ?></td>
                        <td>
                            <a href="/pages/listing.php?id=<?php echo $post['id']; ?>" class="btn-small btn-primary">View</a>
                            <a href="#" class="btn-small btn-danger" onclick="confirmDelete(<?php echo $post['id']; ?>, 'Delete this post?', true); return false;">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
/* Admin Body Background */
body.admin-body {
    background: #f9fafb;
}

.admin-header {
    margin-bottom: 32px;
    background: white;
    padding: 32px;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

.admin-header .page-title {
    margin: 0 0 8px 0;
}

.admin-subtitle {
    color: var(--text-gray);
    font-size: 1.1rem;
    margin-top: 8px;
}

.admin-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 32px;
}

.admin-stat-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 24px;
    transition: all 0.2s;
}

.admin-stat-card:hover {
    border-color: #10b981;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.stat-card-primary::before { color: #3b82f6; }
.stat-card-success::before { color: #10b981; }
.stat-card-warning::before { color: #f59e0b; }
.stat-card-info::before { color: #8b5cf6; }
.stat-card-secondary::before { color: #06b6d4; }

.stat-icon-wrapper {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 16px;
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.stat-icon i {
    font-size: 20px;
    color: white;
}

.stat-trend {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: rgba(16, 185, 129, 0.1);
    color: #10b981;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
}

.stat-content {
    flex: 1;
}

.stat-content h3 {
    font-size: 2rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 4px;
    line-height: 1.2;
}

.stat-content p {
    color: #6b7280;
    font-size: 0.9rem;
    font-weight: 500;
    margin: 0;
}

.stat-subtitle {
    display: block;
    color: #6b7280;
    font-size: 0.85rem;
    font-weight: 500;
    margin-top: 4px;
}

.stat-badge {
    display: inline-block;
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    color: #92400e;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 600;
    margin-top: 8px;
    box-shadow: 0 2px 4px rgba(146, 64, 14, 0.1);
}

.stat-badge-warning {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    color: #92400e;
}


.admin-section {
    margin-bottom: 48px;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding: 20px 24px;
    background: white;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
}

.admin-section .section-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 4px;
    color: #1f2937;
}

.section-subtitle {
    color: var(--text-gray);
    font-size: 0.9rem;
    margin: 0;
}

.pending-badge {
    background: #fef3c7;
    color: #92400e;
    padding: 6px 12px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    gap: 6px;
}

.empty-state {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 60px 40px;
    text-align: center;
}

.empty-icon {
    width: 64px;
    height: 64px;
    margin: 0 auto 24px;
    background: #d1fae5;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: #059669;
}

.empty-state h2 {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 8px;
}

.empty-state p {
    color: var(--text-gray);
    font-size: 1rem;
}

.admin-table-container {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
}

.admin-table thead {
    background: #f9fafb;
}

.admin-table th {
    padding: 16px;
    text-align: left;
    font-weight: 600;
    color: #374151;
    font-size: 0.875rem;
    border-bottom: 1px solid #e5e7eb;
}

.admin-table tbody tr {
    border-bottom: 1px solid #f3f4f6;
    transition: background 0.2s;
}

.admin-table tbody tr:hover {
    background: #f9fafb;
}

.admin-table td {
    padding: 16px;
    font-size: 0.9rem;
    color: #374151;
    vertical-align: middle;
}


.btn-small {
    padding: 6px 12px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 500;
    display: inline-block;
    margin-right: 8px;
}


.status-badge {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 0.85rem;
    font-weight: 600;
}

.status-available {
    background: #d1fae5;
    color: #065f46;
}

.status-pending {
    background: #fef3c7;
    color: #92400e;
}

.status-sold {
    background: #fee2e2;
    color: #991b1b;
}

/* Delete Confirmation Modal */
.delete-confirm-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.3s ease;
}

.delete-confirm-modal.show {
    opacity: 1;
    pointer-events: all;
}

.delete-confirm-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
}

.delete-confirm-dialog {
    position: relative;
    background: white;
    border-radius: var(--radius-xl);
    padding: 32px;
    max-width: 420px;
    width: 90%;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    text-align: center;
    transform: scale(0.9);
    transition: transform 0.3s ease;
    z-index: 1;
}

.delete-confirm-modal.show .delete-confirm-dialog {
    transform: scale(1);
}

.delete-confirm-icon {
    font-size: 3.5rem;
    margin-bottom: 16px;
}

.delete-confirm-dialog h3 {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 12px;
}

.delete-confirm-dialog p {
    color: var(--text-gray);
    font-size: 1rem;
    margin-bottom: 24px;
    line-height: 1.6;
}

.delete-confirm-actions {
    display: flex;
    gap: 12px;
    justify-content: center;
}

.delete-confirm-actions .btn {
    min-width: 120px;
}

.inline-form {
    display: inline;
    margin: 0;
    padding: 0;
    vertical-align: middle;
}

.inline-form::before,
.inline-form::after {
    display: none;
    content: none;
}

.inline-form input[type="hidden"] {
    display: none;
}

.inline-form button.btn-small {
    margin: 0;
    vertical-align: middle;
    padding: 6px 14px;
    font-size: 0.85rem;
    border-radius: var(--radius-md);
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all var(--transition-base);
    text-decoration: none;
    line-height: inherit;
    font-family: inherit;
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    outline: none;
    box-shadow: none;
}

.inline-form button.btn-small:focus {
    outline: none;
    box-shadow: none;
}

.inline-form button.btn-small::before,
.inline-form button.btn-small::after {
    display: none;
    content: none;
}

.admin-table td .btn-small {
    margin-right: 8px;
    vertical-align: middle;
}

/* Responsive Design */
@media (max-width: 768px) {
    .admin-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .admin-stats-grid {
        grid-template-columns: 1fr;
    }
    
    
    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
    }
    
    .admin-table-container {
        overflow-x: auto;
    }
    
    .admin-table {
        min-width: 800px;
    }
    
    .filter-form {
        flex-direction: column;
    }
    
    .filter-input, .filter-select {
        width: 100%;
    }
}
</style>

<script>
function confirmDelete(postId, message, isAdmin = false) {
    const deleteUrl = isAdmin ? `/api/admin-delete-post.php?id=${postId}` : `/api/delete-post.php?id=${postId}`;
    const modal = document.createElement('div');
    modal.className = 'delete-confirm-modal';
    modal.innerHTML = `
        <div class="delete-confirm-overlay"></div>
        <div class="delete-confirm-dialog">
            <div class="delete-confirm-icon">⚠️</div>
            <h3>Confirm Delete</h3>
            <p>${message}?</p>
            <div class="delete-confirm-actions">
                <button class="btn btn-outline" onclick="this.closest('.delete-confirm-modal').remove()">Cancel</button>
                <a href="${deleteUrl}" class="btn btn-danger">Delete</a>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
    setTimeout(() => modal.classList.add('show'), 10);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

