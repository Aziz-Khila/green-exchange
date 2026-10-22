<?php
/**
 * Wishlist Page
 */

$page_title = 'My Wishlist';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('/pages/login.php');
}

$user = getCurrentUser();
require_once __DIR__ . '/../includes/header.php';

$pdo = getDB();

// Get wishlist items
$stmt = $pdo->prepare("
    SELECT p.*, 
           u.username as seller_name,
           (SELECT image_path FROM post_images WHERE post_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
    FROM wishlist w
    JOIN posts p ON w.post_id = p.id
    JOIN users u ON p.user_id = u.id
    WHERE w.user_id = ?
    ORDER BY w.created_at DESC
");
$stmt->execute([$user['id']]);
$wishlist_items = $stmt->fetchAll();
?>

<div class="container">
    <h1 class="page-title"><?php echo t('my_wishlist'); ?></h1>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <?php 
            if ($_GET['success'] === 'added') {
                echo t('item_added_to_wishlist');
            } elseif ($_GET['success'] === 'removed') {
                echo t('item_removed_from_wishlist');
            }
            ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error">
            <?php 
            $errors = [
                'already_in_wishlist' => t('already_in_wishlist'),
                'invalid' => t('invalid_input')
            ];
            echo escape($errors[$_GET['error']] ?? t('error'));
            ?>
        </div>
    <?php endif; ?>
    
    <?php if (empty($wishlist_items)): ?>
        <div class="empty-state">
            <div class="empty-icon">❤️</div>
            <h2><?php echo t('wishlist_empty'); ?></h2>
            <p><?php echo t('wishlist_empty_description'); ?></p>
            <a href="/pages/browse.php" class="btn btn-primary"><?php echo t('browse_listings'); ?></a>
        </div>
    <?php else: ?>
        <div class="wishlist-grid">
            <?php foreach ($wishlist_items as $item): ?>
                <div class="wishlist-card">
                    <?php if ($item['primary_image']): ?>
                        <img src="<?php echo escape($item['primary_image']); ?>" alt="<?php echo escape($item['title']); ?>">
                    <?php else: ?>
                        <div class="no-image"><?php echo t('no_image_available'); ?></div>
                    <?php endif; ?>
                    
                    <div class="wishlist-card-info">
                        <h3><a href="/pages/listing.php?id=<?php echo $item['id']; ?>"><?php echo escape($item['title']); ?></a></h3>
                        <p class="category"><?php echo t('category_' . $item['category']); ?> - <?php echo escape($item['waste_type']); ?></p>
                        <p class="seller"><?php echo t('seller'); ?>: <?php echo escape($item['seller_name']); ?></p>
                        <p class="quantity"><?php echo formatQuantity($item['quantity'], $item['unit']); ?></p>
                        <p class="price"><?php echo formatPrice($item['price_per_kg']); ?> <?php echo t('per_kg'); ?></p>
                        
                        <div class="wishlist-meta">
                            <span class="status-badge status-<?php echo $item['status']; ?>">
                                <?php echo t($item['status']); ?>
                            </span>
                            <span class="wishlist-date"><?php echo formatDate($item['created_at']); ?></span>
                        </div>
                        
                        <div class="wishlist-actions">
                            <a href="/pages/listing.php?id=<?php echo $item['id']; ?>" class="btn btn-primary"><?php echo t('view_details'); ?></a>
                            <a href="#" class="btn btn-danger" onclick="removeFromWishlist(<?php echo $item['id']; ?>); return false;"><?php echo t('remove'); ?></a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.wishlist-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 24px;
    margin-top: 32px;
}

.wishlist-card {
    background: var(--white);
    border: 1px solid var(--border-light);
    border-radius: 16px;
    overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
}

.wishlist-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
}

.wishlist-card img {
    width: 100%;
    height: 200px;
    object-fit: cover;
}

.no-image {
    width: 100%;
    height: 200px;
    background: #f3f4f6;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-gray);
}

.wishlist-card-info {
    padding: 20px;
}

.wishlist-card-info h3 {
    margin-bottom: 8px;
}

.wishlist-card-info h3 a {
    color: var(--text-dark);
    text-decoration: none;
}

.wishlist-card-info h3 a:hover {
    color: var(--primary-green);
}

.wishlist-card-info .category {
    color: var(--text-gray);
    font-size: 0.9rem;
    margin-bottom: 8px;
}

.wishlist-card-info .seller {
    color: var(--text-gray);
    font-size: 0.85rem;
    margin-bottom: 8px;
}

.wishlist-card-info .quantity {
    color: var(--text-dark);
    font-size: 0.9rem;
    margin-bottom: 8px;
}

.wishlist-card-info .price {
    color: var(--primary-green);
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 12px;
}

.wishlist-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid var(--border-light);
}

.wishlist-date {
    font-size: 0.85rem;
    color: var(--text-gray);
}

.wishlist-actions {
    display: flex;
    gap: 12px;
    margin-top: 16px;
}

.wishlist-actions .btn {
    flex: 1;
    text-align: center;
    padding: 12px 20px;
    font-size: 0.95rem;
    font-weight: 600;
}

.wishlist-actions .btn-primary {
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
    margin-bottom: 24px;
}
</style>

<script>
function removeFromWishlist(postId) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/api/remove-from-wishlist.php';
    
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'post_id';
    input.value = postId;
    
    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>


