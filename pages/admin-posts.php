<?php
/**
 * Admin - Manage Posts/Listings
 */

$page_title = 'Manage Listings';
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
$category_filter = $_GET['category'] ?? 'all';

// Build query
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(p.title LIKE ? OR p.description LIKE ? OR u.username LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
}

if ($status_filter !== 'all') {
    $where_conditions[] = "p.status = ?";
    $params[] = $status_filter;
}

if ($category_filter !== 'all') {
    $where_conditions[] = "p.category = ?";
    $params[] = $category_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total posts count
$total_posts_query = "SELECT COUNT(*) FROM posts p JOIN users u ON p.user_id = u.id $where_clause";
$stmt = $pdo->prepare($total_posts_query);
$stmt->execute($params);
$total_posts = $stmt->fetchColumn();

// Get posts with pagination
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;
$total_pages = ceil($total_posts / $per_page);

$posts_query = "
    SELECT p.*, u.username, u.email,
           (SELECT image_path FROM post_images WHERE post_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
    FROM posts p
    JOIN users u ON p.user_id = u.id
    $where_clause
    ORDER BY p.created_at DESC
    LIMIT ? OFFSET ?
";
$params[] = $per_page;
$params[] = $offset;
$stmt = $pdo->prepare($posts_query);
$stmt->execute($params);
$posts = $stmt->fetchAll();

// Get statistics
$stats = [
    'total' => $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn(),
    'available' => $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'available'")->fetchColumn(),
    'pending' => $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'pending'")->fetchColumn(),
    'sold' => $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'sold'")->fetchColumn(),
];
?>

<div class="container">
    <div class="admin-header">
        <div>
            <h1 class="page-title">Manage Listings</h1>
            <p class="admin-subtitle">Review and manage all product listings</p>
        </div>
        <a href="/pages/admin.php" class="btn btn-outline">
            <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
    
    <!-- Statistics -->
    <div class="admin-stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 32px;">
        <div class="admin-stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
                <i class="fa-solid fa-box-open"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($stats['total']); ?></h3>
                <p>Total Listings</p>
            </div>
        </div>
        <div class="admin-stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                <i class="fa-solid fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($stats['available']); ?></h3>
                <p>Available</p>
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
            <div class="stat-icon" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                <i class="fa-solid fa-check-double"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($stats['sold']); ?></h3>
                <p>Sold</p>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="admin-filters">
        <form method="GET" class="filter-form">
            <input type="text" name="search" placeholder="Search listings..." value="<?php echo escape($search); ?>" class="filter-input">
            <select name="status" class="filter-select">
                <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                <option value="available" <?php echo $status_filter === 'available' ? 'selected' : ''; ?>>Available</option>
                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="sold" <?php echo $status_filter === 'sold' ? 'selected' : ''; ?>>Sold</option>
                <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>
            <select name="category" class="filter-select">
                <option value="all" <?php echo $category_filter === 'all' ? 'selected' : ''; ?>>All Categories</option>
                <option value="plastics" <?php echo $category_filter === 'plastics' ? 'selected' : ''; ?>>Plastics</option>
                <option value="electronics" <?php echo $category_filter === 'electronics' ? 'selected' : ''; ?>>Electronics</option>
                <option value="cardboard" <?php echo $category_filter === 'cardboard' ? 'selected' : ''; ?>>Cardboard</option>
                <option value="glass" <?php echo $category_filter === 'glass' ? 'selected' : ''; ?>>Glass</option>
                <option value="metal" <?php echo $category_filter === 'metal' ? 'selected' : ''; ?>>Metal</option>
            </select>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-filter"></i> Filter
            </button>
            <a href="/pages/admin-posts.php" class="btn btn-outline">
                <i class="fa-solid fa-times"></i> Clear
            </a>
        </form>
    </div>
    
    <!-- Posts Table -->
    <div class="admin-section">
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Seller</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Status</th>
                        <th>Posted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($posts)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 40px;">
                                <p style="color: var(--text-gray);">No listings found.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($posts as $post): ?>
                        <tr>
                            <td>
                                <?php if ($post['primary_image']): ?>
                                    <img src="<?php echo escape($post['primary_image']); ?>" alt="<?php echo escape($post['title']); ?>" style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;">
                                <?php else: ?>
                                    <div style="width: 60px; height: 60px; background: #f3f4f6; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fa-solid fa-image" style="color: #9ca3af;"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo escape($post['title']); ?></strong>
                                <?php if ($post['description']): ?>
                                    <br><small style="color: var(--text-gray);"><?php echo escape(substr($post['description'], 0, 50)); ?>...</small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo t('category_' . $post['category']); ?></td>
                            <td>
                                <div><?php echo escape($post['username']); ?></div>
                                <small style="color: var(--text-gray);"><?php echo escape($post['email']); ?></small>
                            </td>
                            <td><?php echo formatPrice($post['price_per_kg']); ?> / kg</td>
                            <td><?php echo formatQuantity($post['quantity'], $post['unit']); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $post['status']; ?>">
                                    <?php echo t($post['status']); ?>
                                </span>
                            </td>
                            <td><?php echo formatDate($post['created_at']); ?></td>
                            <td>
                                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                    <a href="/pages/listing.php?id=<?php echo $post['id']; ?>" class="btn-small btn-primary" target="_blank">
                                        <i class="fa-solid fa-eye"></i> View
                                    </a>
                                    <a href="#" class="btn-small btn-danger" onclick="confirmDelete(<?php echo $post['id']; ?>, 'Delete this listing?', true); return false;">
                                        <i class="fa-solid fa-trash"></i> Delete
                                    </a>
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
                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&category=<?php echo urlencode($category_filter); ?>" class="btn btn-outline">
                    <i class="fa-solid fa-chevron-left"></i> Previous
                </a>
            <?php endif; ?>
            
            <span class="pagination-info">
                Page <?php echo $page; ?> of <?php echo $total_pages; ?> (<?php echo number_format($total_posts); ?> listings)
            </span>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&category=<?php echo urlencode($category_filter); ?>" class="btn btn-outline">
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

.admin-table img {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
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
        min-width: 900px;
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

