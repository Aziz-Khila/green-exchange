<?php
/**
 * Admin - Manage Users
 */

$page_title = 'Manage Users';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('/');
}

$admin_page = true; // Flag for admin styling
require_once __DIR__ . '/../includes/header.php';

$pdo = getDB();

// Get search/filter parameters
$search = trim($_GET['search'] ?? '');
$role_filter = $_GET['role'] ?? 'all';

// Build query
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(username LIKE ? OR email LIKE ? OR phone LIKE ? OR city LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

if ($role_filter !== 'all') {
    $where_conditions[] = "role = ?";
    $params[] = $role_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total users count
$total_users_query = "SELECT COUNT(*) FROM users $where_clause";
$stmt = $pdo->prepare($total_users_query);
$stmt->execute($params);
$total_users = $stmt->fetchColumn();

// Get users with pagination
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;
$total_pages = ceil($total_users / $per_page);

$users_query = "
    SELECT u.*,
           (SELECT COUNT(*) FROM posts WHERE user_id = u.id) as posts_count,
           (SELECT COUNT(*) FROM orders WHERE buyer_id = u.id OR seller_id = u.id) as orders_count
    FROM users u
    $where_clause
    ORDER BY u.created_at DESC
    LIMIT ? OFFSET ?
";
$params[] = $per_page;
$params[] = $offset;
$stmt = $pdo->prepare($users_query);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Get statistics
$total_all_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_admins = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
$total_regular_users = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$users_with_posts = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM posts")->fetchColumn();
?>

<div class="container">
    <div class="admin-header">
        <h1 class="page-title">Manage Users</h1>
        <p class="admin-subtitle">View, search, and manage all platform users</p>
    </div>
    
    <!-- Success/Error Messages -->
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success" style="margin-bottom: 24px;">
            <?php
            $messages = [
                'user_deleted' => 'User deleted successfully.'
            ];
            echo escape($messages[$_GET['success']] ?? 'Operation completed successfully.');
            ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error" style="margin-bottom: 24px;">
            <?php
            $errors = [
                'invalid' => 'Invalid user ID.',
                'cannot_delete_admin' => 'Cannot delete administrator accounts.'
            ];
            echo escape($errors[$_GET['error']] ?? 'An error occurred.');
            ?>
        </div>
    <?php endif; ?>
    
    <!-- Statistics Cards -->
    <div class="admin-stats-grid">
        <div class="admin-stat-card">
            <div class="stat-icon" style="background: #dbeafe; color: #1e40af;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($total_all_users); ?></h3>
                <p>Total Users</p>
            </div>
        </div>
        
        <div class="admin-stat-card">
            <div class="stat-icon" style="background: #fef3c7; color: #92400e;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                </svg>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($total_regular_users); ?></h3>
                <p>Regular Users</p>
            </div>
        </div>
        
        <div class="admin-stat-card">
            <div class="stat-icon" style="background: #e0e7ff; color: #3730a3;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                </svg>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($total_admins); ?></h3>
                <p>Administrators</p>
            </div>
        </div>
        
        <div class="admin-stat-card">
            <div class="stat-icon" style="background: #d1fae5; color: #065f46;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                </svg>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($users_with_posts); ?></h3>
                <p>Active Sellers</p>
            </div>
        </div>
    </div>
    
    <!-- Search and Filter -->
    <div class="admin-section">
        <form method="GET" action="/pages/admin-users.php" class="admin-filter-form">
            <div class="filter-group">
                <input type="text" name="search" placeholder="Search by username, email, phone, or city..." 
                       value="<?php echo escape($search); ?>" class="filter-input">
            </div>
            <div class="filter-group">
                <select name="role" class="filter-select">
                    <option value="all" <?php echo $role_filter === 'all' ? 'selected' : ''; ?>>All Roles</option>
                    <option value="user" <?php echo $role_filter === 'user' ? 'selected' : ''; ?>>Regular Users</option>
                    <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Administrators</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Search</button>
            <?php if (!empty($search) || $role_filter !== 'all'): ?>
                <a href="/pages/admin-users.php" class="btn btn-outline">Clear</a>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- Users Table -->
    <div class="admin-section">
        <h2 class="section-title">Users (<?php echo number_format($total_users); ?>)</h2>
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>City</th>
                        <th>Role</th>
                        <th>Listings</th>
                        <th>Orders</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 40px; color: var(--text-gray);">
                                No users found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr class="user-row" data-user-id="<?php echo $user['id']; ?>">
                                <td>
                                    <div class="user-info-cell">
                                        <div class="user-avatar-small">
                                            <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                        </div>
                                        <div class="user-name-info">
                                            <strong><?php echo escape($user['username']); ?></strong>
                                            <?php if ($user['account_fee_paid']): ?>
                                                <span class="status-badge status-paid">Paid</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo escape($user['email']); ?></td>
                                <td><?php echo escape($user['phone'] ?? '-'); ?></td>
                                <td><?php echo escape($user['city'] ?? '-'); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $user['role'] === 'admin' ? 'status-pending' : 'status-available'; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="count-badge count-listings"><?php echo number_format($user['posts_count']); ?></span>
                                </td>
                                <td>
                                    <span class="count-badge count-orders"><?php echo number_format($user['orders_count']); ?></span>
                                </td>
                                <td><?php echo formatDate($user['created_at']); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button onclick="showUserDetails(<?php echo $user['id']; ?>)" class="btn-action btn-view">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                                <circle cx="12" cy="12" r="3"/>
                                            </svg>
                                            View
                                        </button>
                                        <?php if ($user['role'] !== 'admin'): ?>
                                            <a href="/api/admin-delete-user.php?id=<?php echo $user['id']; ?>" 
                                               class="btn-action btn-delete" 
                                               onclick="return confirm('Are you sure you want to delete user <?php echo escape($user['username']); ?>? This action cannot be undone.')">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <polyline points="3 6 5 6 21 6"/>
                                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                                </svg>
                                                Delete
                                            </a>
                                        <?php else: ?>
                                            <span class="btn-action btn-disabled">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <circle cx="12" cy="12" r="10"/>
                                                    <line x1="12" y1="8" x2="12" y2="12"/>
                                                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                                                </svg>
                                                Protected
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <!-- User Details Row (hidden by default) -->
                            <tr class="user-details-row" id="details-<?php echo $user['id']; ?>" style="display: none;">
                                <td colspan="9">
                                    <div class="user-details-content">
                                        <div class="user-details-grid">
                                            <div class="detail-item">
                                                <label>User ID:</label>
                                                <span><?php echo $user['id']; ?></span>
                                            </div>
                                            <div class="detail-item">
                                                <label>Username:</label>
                                                <span><?php echo escape($user['username']); ?></span>
                                            </div>
                                            <div class="detail-item">
                                                <label>Email:</label>
                                                <span><?php echo escape($user['email']); ?></span>
                                            </div>
                                            <div class="detail-item">
                                                <label>Phone:</label>
                                                <span><?php echo escape($user['phone'] ?? 'Not provided'); ?></span>
                                            </div>
                                            <div class="detail-item">
                                                <label>Address:</label>
                                                <span><?php echo escape($user['address'] ?? 'Not provided'); ?></span>
                                            </div>
                                            <div class="detail-item">
                                                <label>City:</label>
                                                <span><?php echo escape($user['city'] ?? 'Not provided'); ?></span>
                                            </div>
                                            <div class="detail-item">
                                                <label>Role:</label>
                                                <span class="status-badge <?php echo $user['role'] === 'admin' ? 'status-pending' : 'status-available'; ?>">
                                                    <?php echo ucfirst($user['role']); ?>
                                                </span>
                                            </div>
                                            <div class="detail-item">
                                                <label>Account Fee Paid:</label>
                                                <span><?php echo $user['account_fee_paid'] ? 'Yes' : 'No'; ?></span>
                                            </div>
                                            <div class="detail-item">
                                                <label>Total Listings:</label>
                                                <span><?php echo number_format($user['posts_count']); ?></span>
                                            </div>
                                            <div class="detail-item">
                                                <label>Total Orders:</label>
                                                <span><?php echo number_format($user['orders_count']); ?></span>
                                            </div>
                                            <div class="detail-item">
                                                <label>Member Since:</label>
                                                <span><?php echo date('F j, Y \a\t g:i A', strtotime($user['created_at'])); ?></span>
                                            </div>
                                        </div>
                                        
                                        <?php
                                        // Get user's recent listings
                                        $stmt = $pdo->prepare("SELECT id, title, status, created_at FROM posts WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
                                        $stmt->execute([$user['id']]);
                                        $user_posts = $stmt->fetchAll();
                                        
                                        // Get user's recent orders
                                        $stmt = $pdo->prepare("
                                            SELECT o.*, p.title as post_title,
                                                   CASE 
                                                       WHEN o.buyer_id = ? THEN o.buyer_total
                                                       WHEN o.seller_id = ? THEN o.seller_total
                                                   END as display_total
                                            FROM orders o 
                                            JOIN posts p ON o.post_id = p.id 
                                            WHERE o.buyer_id = ? OR o.seller_id = ?
                                            ORDER BY o.created_at DESC 
                                            LIMIT 5
                                        ");
                                        $stmt->execute([$user['id'], $user['id'], $user['id'], $user['id']]);
                                        $user_orders = $stmt->fetchAll();
                                        ?>
                                        
                                        <?php if (!empty($user_posts)): ?>
                                            <div class="user-details-section">
                                                <h4><?php echo t('recent_listings'); ?></h4>
                                                <ul class="details-list">
                                                    <?php foreach ($user_posts as $post): ?>
                                                        <li>
                                                            <a href="/pages/listing.php?id=<?php echo $post['id']; ?>" target="_blank">
                                                                <?php echo escape($post['title']); ?>
                                                            </a>
                                                            <span class="status-badge status-<?php echo $post['status']; ?>" style="margin-left: 8px;">
                                                                <?php echo t($post['status']); ?>
                                                            </span>
                                                            <span style="color: var(--text-gray); font-size: 0.85rem; margin-left: 8px;">
                                                                - <?php echo formatDate($post['created_at']); ?>
                                                            </span>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($user_orders)): ?>
                                            <div class="user-details-section">
                                                <h4><?php echo t('recent_orders'); ?></h4>
                                                <ul class="details-list">
                                                    <?php foreach ($user_orders as $order): ?>
                                                        <li>
                                                            <a href="/pages/listing.php?id=<?php echo $order['post_id']; ?>" target="_blank">
                                                                <?php echo escape($order['post_title']); ?>
                                                            </a>
                                                            <span class="status-badge status-<?php echo $order['status']; ?>" style="margin-left: 8px;">
                                                                <?php echo t($order['status']); ?>
                                                            </span>
                                                            <span style="color: var(--text-gray); font-size: 0.85rem; margin-left: 8px;">
                                                                - <?php echo formatPrice($order['display_total']); ?> - <?php echo formatDate($order['created_at']); ?>
                                                            </span>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
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
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>" class="pagination-btn">Previous</a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>" 
                       class="pagination-btn <?php echo $i === $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>" class="pagination-btn">Next</a>
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

.admin-filter-form {
    display: flex;
    gap: 16px;
    align-items: flex-end;
    flex-wrap: wrap;
    background: white;
    padding: 24px;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    margin-bottom: 24px;
}

.filter-group {
    flex: 1;
    min-width: 200px;
}

.filter-input,
.filter-select {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.95rem;
    background: white;
    transition: border-color 0.2s;
}

.filter-input:focus,
.filter-select:focus {
    outline: none;
    border-color: #10b981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
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

.admin-table thead th {
    font-weight: 600;
    font-size: 0.875rem;
    color: #374151;
    padding: 16px;
    border-bottom: 1px solid #e5e7eb;
}

.admin-table td {
    vertical-align: middle;
    padding: 16px;
    color: #374151;
    border-bottom: 1px solid #f3f4f6;
}

.admin-table tbody tr {
    transition: background 0.2s;
}

.user-row:hover {
    background: #f9fafb;
}

.user-info-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}

.user-avatar-small {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-green) 0%, var(--primary-green-dark) 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.9rem;
    flex-shrink: 0;
}

.user-name-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.user-name-info strong {
    color: var(--text-dark);
    font-size: 0.95rem;
}

.count-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 32px;
    padding: 4px 10px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.85rem;
}

.count-listings {
    background: #dbeafe;
    color: #1e40af;
}

.count-orders {
    background: #fef3c7;
    color: #92400e;
}

.action-buttons {
    display: flex;
    gap: 8px;
    align-items: center;
}

.btn-action {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
}

.btn-view {
    background: #3b82f6;
    color: white;
}

.btn-view:hover {
    background: #2563eb;
}

.btn-delete {
    background: #ef4444;
    color: white;
}

.btn-delete:hover {
    background: #dc2626;
}

.btn-disabled {
    background: #9ca3af;
    color: white;
    cursor: not-allowed;
    opacity: 0.7;
}

.status-paid {
    background: #d1fae5;
    color: #065f46;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: 600;
    margin-top: 2px;
}

.user-details-row {
    background: linear-gradient(to bottom, rgba(249, 250, 251, 0.95), rgba(255, 255, 255, 0.98));
    border-left: 4px solid var(--primary-green);
}

.user-details-content {
    padding: 32px;
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.user-details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 24px;
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.detail-item label {
    font-weight: 600;
    color: var(--text-gray);
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.detail-item span {
    color: var(--text-dark);
    font-size: 1rem;
}

.user-details-section {
    margin-top: 24px;
    padding-top: 24px;
    border-top: 1px solid var(--border-light);
}

.user-details-section h4 {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 12px;
    color: var(--text-dark);
}

.details-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.details-list li {
    padding: 10px 0;
    border-bottom: 1px solid rgba(229, 231, 235, 0.5);
    display: flex;
    align-items: center;
    flex-wrap: wrap;
}

.details-list li:last-child {
    border-bottom: none;
}

.details-list a {
    color: var(--primary-green);
    text-decoration: none;
    font-weight: 500;
}

.details-list a:hover {
    text-decoration: underline;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-top: 24px;
    padding: 20px;
}

.pagination-btn {
    padding: 10px 16px;
    border: 1px solid var(--border-light);
    border-radius: 8px;
    text-decoration: none;
    color: var(--text-dark);
    background: white;
    transition: all 0.2s;
}

.pagination-btn:hover {
    background: var(--primary-green);
    color: white;
    border-color: var(--primary-green);
}

.pagination-btn.active {
    background: var(--primary-green);
    color: white;
    border-color: var(--primary-green);
}
</style>

<script>
function showUserDetails(userId) {
    const detailsRow = document.getElementById('details-' + userId);
    const isVisible = detailsRow.style.display !== 'none';
    
    // Hide all other detail rows
    document.querySelectorAll('.user-details-row').forEach(row => {
        row.style.display = 'none';
    });
    
    // Toggle current row
    if (!isVisible) {
        detailsRow.style.display = 'table-row';
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

