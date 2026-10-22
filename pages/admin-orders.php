<?php
/**
 * Admin - View All Orders
 */

$page_title = 'View Orders';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('/');
}

$admin_page = true;
require_once __DIR__ . '/../includes/header.php';

$pdo = getDB();

// Get search/filter parameters
$search = trim($_GET['search'] ?? '');
$status_filter = $_GET['status'] ?? 'all';

// Build query
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(p.title LIKE ? OR buyer.username LIKE ? OR seller.username LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
}

if ($status_filter !== 'all') {
    $where_conditions[] = "o.status = ?";
    $params[] = $status_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total orders count
$total_orders_query = "
    SELECT COUNT(*) 
    FROM orders o
    JOIN posts p ON o.post_id = p.id
    JOIN users buyer ON o.buyer_id = buyer.id
    JOIN users seller ON o.seller_id = seller.id
    $where_clause
";
$stmt = $pdo->prepare($total_orders_query);
$stmt->execute($params);
$total_orders = $stmt->fetchColumn();

// Get orders with pagination
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;
$total_pages = ceil($total_orders / $per_page);

$orders_query = "
    SELECT o.*, p.title as post_title, 
           buyer.username as buyer_name, buyer.email as buyer_email,
           seller.username as seller_name, seller.email as seller_email,
           (SELECT image_path FROM post_images WHERE post_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
    FROM orders o
    JOIN posts p ON o.post_id = p.id
    JOIN users buyer ON o.buyer_id = buyer.id
    JOIN users seller ON o.seller_id = seller.id
    $where_clause
    ORDER BY o.created_at DESC
    LIMIT ? OFFSET ?
";
$params[] = $per_page;
$params[] = $offset;
$stmt = $pdo->prepare($orders_query);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Get statistics
$stats = [
    'total' => $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'pending' => $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn(),
    'confirmed' => $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'confirmed'")->fetchColumn(),
    'pending_completion' => $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending_completion'")->fetchColumn(),
    'completed' => $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'completed'")->fetchColumn(),
    'cancelled' => $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'cancelled'")->fetchColumn(),
];
?>

<div class="container">
    <div class="admin-header">
        <div>
            <h1 class="page-title">View Orders</h1>
            <p class="admin-subtitle">Monitor all transactions and orders</p>
        </div>
        <a href="/pages/admin.php" class="btn btn-outline">
            <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
    
    <!-- Statistics -->
    <div class="admin-stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: 32px;">
        <div class="admin-stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
                <i class="fa-solid fa-shopping-cart"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($stats['total']); ?></h3>
                <p>Total Orders</p>
            </div>
        </div>
        <div class="admin-stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                <i class="fa-solid fa-clock"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($stats['pending']); ?></h3>
                <p>Pending</p>
            </div>
        </div>
        <div class="admin-stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                <i class="fa-solid fa-check"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($stats['confirmed']); ?></h3>
                <p>Confirmed</p>
            </div>
        </div>
        <div class="admin-stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                <i class="fa-solid fa-hourglass-half"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($stats['pending_completion']); ?></h3>
                <p>Pending Completion</p>
            </div>
        </div>
        <div class="admin-stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                <i class="fa-solid fa-check-double"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($stats['completed']); ?></h3>
                <p>Completed</p>
            </div>
        </div>
        <div class="admin-stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                <i class="fa-solid fa-times-circle"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($stats['cancelled']); ?></h3>
                <p>Cancelled</p>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="admin-filters">
        <form method="GET" class="filter-form">
            <input type="text" name="search" placeholder="Search orders..." value="<?php echo escape($search); ?>" class="filter-input">
            <select name="status" class="filter-select">
                <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                <option value="pending_completion" <?php echo $status_filter === 'pending_completion' ? 'selected' : ''; ?>>Pending Completion</option>
                <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-filter"></i> Filter
            </button>
            <a href="/pages/admin-orders.php" class="btn btn-outline">
                <i class="fa-solid fa-times"></i> Clear
            </a>
        </form>
    </div>
    
    <!-- Orders Table -->
    <div class="admin-section">
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
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 40px;">
                                <p style="color: var(--text-gray);">No orders found.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><strong>#<?php echo $order['id']; ?></strong></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <?php if ($order['primary_image']): ?>
                                        <img src="<?php echo escape($order['primary_image']); ?>" alt="<?php echo escape($order['post_title']); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
                                    <?php endif; ?>
                                    <div>
                                        <strong><?php echo escape($order['post_title']); ?></strong>
                                        <br><small style="color: var(--text-gray);"><?php echo formatPrice($order['unit_price']); ?> / kg</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div><?php echo escape($order['buyer_name']); ?></div>
                                <small style="color: var(--text-gray);"><?php echo escape($order['buyer_email']); ?></small>
                            </td>
                            <td>
                                <div><?php echo escape($order['seller_name']); ?></div>
                                <small style="color: var(--text-gray);"><?php echo escape($order['seller_email']); ?></small>
                            </td>
                            <td><?php echo formatQuantity($order['quantity'], $order['unit'] ?? 'kg'); ?></td>
                            <td>
                                <strong><?php echo formatPrice($order['buyer_total'] ?? $order['subtotal']); ?></strong>
                                <br><small style="color: var(--text-gray);">Commission: <?php echo formatPrice(($order['buyer_commission'] ?? 0) + ($order['seller_commission'] ?? 0)); ?></small>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $order['status']; ?>">
                                    <?php echo t($order['status']); ?>
                                </span>
                            </td>
                            <td><?php echo formatDate($order['created_at']); ?></td>
                            <td>
                                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                    <a href="/pages/listing.php?id=<?php echo $order['post_id']; ?>" class="btn-small btn-primary" target="_blank">
                                        <i class="fa-solid fa-eye"></i> View
                                    </a>
                                    <?php if ($order['status'] === 'pending_completion'): ?>
                                        <form action="/api/update-order-status.php" method="POST" style="display: inline;">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <input type="hidden" name="status" value="completed">
                                            <button type="submit" class="btn-small btn-success" onclick="return confirm('Approve and complete this order?')">
                                                <i class="fa-solid fa-check"></i> Approve
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="admin-pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" class="btn btn-outline">
                    <i class="fa-solid fa-chevron-left"></i> Previous
                </a>
            <?php endif; ?>
            
            <span class="pagination-info">
                Page <?php echo $page; ?> of <?php echo $total_pages; ?> (<?php echo number_format($total_orders); ?> orders)
            </span>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" class="btn btn-outline">
                    Next <i class="fa-solid fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Admin Body Background */
body.admin-body {
    background: #f9fafb;
}

.admin-filters {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 24px;
}

.filter-form {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    align-items: center;
}

.filter-input, .filter-select {
    padding: 10px 14px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.95rem;
    flex: 1;
    min-width: 200px;
    background: white;
    transition: border-color 0.2s;
}

.filter-input:focus, .filter-select:focus {
    outline: none;
    border-color: #10b981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

.admin-pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 16px;
    margin-top: 24px;
    padding: 20px;
}

.pagination-info {
    color: var(--text-gray);
    font-size: 0.95rem;
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

.status-pending_completion {
    background: #fef3c7;
    color: #92400e;
}

/* Responsive Design */
@media (max-width: 768px) {
    .admin-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .admin-stats-grid {
        grid-template-columns: 1fr !important;
    }
    
    .filter-form {
        flex-direction: column;
    }
    
    .filter-input, .filter-select {
        width: 100%;
    }
    
    .admin-table-container {
        overflow-x: auto;
    }
    
    .admin-table {
        min-width: 1000px;
    }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

