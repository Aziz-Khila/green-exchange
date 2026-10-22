<?php
/**
 * My Listings Page
 */

$page_title = 'My Listings';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('/pages/login.php');
}

$user = getCurrentUser();
require_once __DIR__ . '/../includes/header.php';

$pdo = getDB();

// Get user's listings
$stmt = $pdo->prepare("
    SELECT p.*, 
           (SELECT image_path FROM post_images WHERE post_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
    FROM posts p 
    WHERE p.user_id = ? 
    ORDER BY p.created_at DESC
");
$stmt->execute([$user['id']]);
$my_posts = $stmt->fetchAll();

// Get statistics
$total_posts = count($my_posts);
$active_posts = count(array_filter($my_posts, fn($p) => $p['status'] === 'available'));
$sold_posts = count(array_filter($my_posts, fn($p) => $p['status'] === 'sold'));
$pending_posts = count(array_filter($my_posts, fn($p) => $p['status'] === 'pending'));
?>

<div class="container">
    <?php if (isset($_GET['success']) && $_GET['success'] === 'deleted'): ?>
        <div class="alert alert-success"><?php echo t('listing_deleted'); ?></div>
    <?php endif; ?>
    
    <div class="page-header">
        <div>
            <h1 class="page-title"><?php echo t('my_listings'); ?></h1>
            <p class="page-subtitle"><?php echo t('manage_your_listings'); ?></p>
        </div>
        <a href="/pages/post.php" class="btn btn-primary">+ <?php echo t('post_listing'); ?></a>
    </div>
    
    <!-- Statistics -->
    <div class="listings-stats">
        <div class="stat-card">
            <div class="stat-icon" style="background: #dbeafe; color: #1e40af;">📦</div>
            <div class="stat-content">
                <h3><?php echo $total_posts; ?></h3>
                <p><?php echo t('total_listings'); ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: #d1fae5; color: #065f46;">✓</div>
            <div class="stat-content">
                <h3><?php echo $active_posts; ?></h3>
                <p><?php echo t('active'); ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: #fef3c7; color: #92400e;">⏳</div>
            <div class="stat-content">
                <h3><?php echo $pending_posts; ?></h3>
                <p><?php echo t('pending'); ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: #e0e7ff; color: #3730a3;">💰</div>
            <div class="stat-content">
                <h3><?php echo $sold_posts; ?></h3>
                <p><?php echo t('sold'); ?></p>
            </div>
        </div>
    </div>
    
    <!-- My Listings -->
    <?php if (empty($my_posts)): ?>
        <div class="empty-state">
            <div class="empty-icon">📋</div>
            <h2><?php echo t('no_listings_yet'); ?></h2>
            <p><?php echo t('start_selling_description'); ?></p>
            <a href="/pages/post.php" class="btn btn-primary"><?php echo t('post_listing'); ?></a>
        </div>
    <?php else: ?>
        <div class="listings-grid">
            <?php foreach ($my_posts as $post): ?>
                <div class="listing-card">
                    <?php if ($post['primary_image']): ?>
                        <img src="<?php echo escape($post['primary_image']); ?>" alt="<?php echo escape($post['title']); ?>">
                    <?php else: ?>
                        <div class="no-image"><?php echo t('no_image_available'); ?></div>
                    <?php endif; ?>
                    <div class="listing-info">
                        <h3><a href="/pages/listing.php?id=<?php echo $post['id']; ?>"><?php echo escape($post['title']); ?></a></h3>
                        <p class="category"><?php echo t('category_' . $post['category']); ?> - <?php echo escape($post['waste_type']); ?></p>
                        <p class="quantity"><?php echo formatQuantity($post['quantity'], $post['unit']); ?></p>
                        <p class="price"><?php echo formatPrice($post['price_per_kg']); ?> <?php echo t('per_kg'); ?></p>
                        <div class="listing-meta">
                            <span class="status-badge status-<?php echo $post['status']; ?>">
                                <?php echo t($post['status']); ?>
                            </span>
                            <span class="listing-date"><?php echo formatDate($post['created_at']); ?></span>
                        </div>
                        <div class="listing-actions-owner">
                            <a href="/pages/edit-post.php?id=<?php echo $post['id']; ?>" class="btn-small btn-edit"><?php echo t('edit'); ?></a>
                            <a href="#" class="btn-small btn-danger" onclick="confirmDelete(<?php echo $post['id']; ?>, '<?php echo addslashes(t('delete_listing')); ?>'); return false;"><?php echo t('delete'); ?></a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 32px;
}

.page-subtitle {
    color: var(--text-gray);
    margin-top: 8px;
}

.listings-stats {
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

.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: var(--white);
    border-radius: 16px;
    border: 1px solid var(--border-light);
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
    margin-bottom: 24px;
}

.listing-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid var(--border-light);
}

.listing-date {
    font-size: 0.85rem;
    color: var(--text-gray);
}

.listing-actions-owner {
    display: flex;
    gap: 8px;
    margin-top: 12px;
}

.btn-small {
    padding: 6px 12px;
    font-size: 0.85rem;
    border-radius: 6px;
    text-decoration: none;
    display: inline-block;
    transition: all 0.2s;
}

.btn-edit {
    background: var(--primary-green);
    color: white;
}

.btn-edit:hover {
    background: var(--primary-green-dark);
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
            <h3><?php echo t('confirm_delete'); ?></h3>
            <p>${message}?</p>
            <div class="delete-confirm-actions">
                <button class="btn btn-outline" onclick="this.closest('.delete-confirm-modal').remove()"><?php echo t('cancel'); ?></button>
                <a href="${deleteUrl}" class="btn btn-danger"><?php echo t('delete'); ?></a>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
    setTimeout(() => modal.classList.add('show'), 10);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>


