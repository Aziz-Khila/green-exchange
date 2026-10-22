<?php
/**
 * Listing Detail Page
 */

$page_title = 'Listing Details';
require_once __DIR__ . '/../includes/functions.php';

$post_id = $_GET['id'] ?? 0;

if (!$post_id) {
    redirect('/pages/browse.php');
}

$pdo = getDB();

// Get listing details
// Note: Post quantity is already updated when orders are created (in checkout.php),
// so p.quantity already represents the remaining quantity
$stmt = $pdo->prepare("
    SELECT p.*, u.username, u.email, u.phone, u.city 
    FROM posts p 
    JOIN users u ON p.user_id = u.id 
    WHERE p.id = ?
");
$stmt->execute([$post_id]);
$post = $stmt->fetch();

if (!$post) {
    redirect('/pages/browse.php');
}

// Post quantity is already the remaining quantity (updated in checkout.php)
$remaining_quantity = max(0, $post['quantity']);
$remaining_unit = $post['unit'];

// Get all images
$stmt = $pdo->prepare("SELECT * FROM post_images WHERE post_id = ? ORDER BY is_primary DESC");
$stmt->execute([$post_id]);
$images = $stmt->fetchAll();

$is_owner = isLoggedIn() && getCurrentUser()['id'] == $post['user_id'];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="listing-detail">
        <div class="listing-gallery">
            <?php if (!empty($images)): ?>
                <div class="main-image">
                    <img src="<?php echo escape($images[0]['image_path']); ?>" alt="<?php echo escape($post['title']); ?>" id="mainImage">
                </div>
                <?php if (count($images) > 1): ?>
                    <div class="thumbnail-gallery">
                        <?php foreach ($images as $index => $image): ?>
                            <img src="<?php echo escape($image['image_path']); ?>" 
                                 alt="Thumbnail <?php echo $index + 1; ?>"
                                 class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>"
                                 onclick="changeMainImage('<?php echo escape($image['image_path']); ?>', this)">
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="no-image-large"><?php echo t('no_image_available'); ?></div>
            <?php endif; ?>
        </div>
        
        <div class="listing-content">
            <div class="listing-header">
                <h1><?php echo escape($post['title']); ?></h1>
                <span class="status-badge status-<?php echo $post['status']; ?>">
                    <?php echo t($post['status']); ?>
                </span>
            </div>
            
            <div class="listing-price">
                <span class="price-amount"><?php echo formatPrice($post['price_per_kg']); ?></span>
                <span class="price-unit"><?php echo t('per_kg'); ?></span>
            </div>
            
            <div class="listing-info-grid">
                <div class="info-box">
                    <label><?php echo t('category'); ?></label>
                    <span><?php echo t('category_' . $post['category']); ?></span>
                </div>
                <div class="info-box">
                    <label><?php echo t('waste_type'); ?></label>
                    <span><?php echo escape($post['waste_type']); ?></span>
                </div>
                <div class="info-box">
                    <label><?php echo t('quantity'); ?></label>
                    <span><?php echo formatQuantity($remaining_quantity, $remaining_unit); ?></span>
                </div>
                <div class="info-box">
                    <label><?php echo t('transport_by'); ?></label>
                    <span><?php echo t($post['transport_by']); ?></span>
                </div>
                <div class="info-box">
                    <label><?php echo t('location'); ?></label>
                    <span><?php echo escape($post['location']); ?></span>
                </div>
                <div class="info-box">
                    <label><?php echo t('posted'); ?></label>
                    <span><?php echo formatDate($post['created_at']); ?></span>
                </div>
            </div>
            
            <?php if (!empty($post['description'])): ?>
                <div class="listing-description">
                    <h3><?php echo t('description'); ?></h3>
                    <p><?php echo nl2br(escape($post['description'])); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="seller-info">
                <h3><?php echo t('seller_information'); ?></h3>
                <div class="seller-details">
                    <div class="seller-avatar">
                        <?php echo strtoupper(substr($post['username'], 0, 2)); ?>
                    </div>
                    <div class="seller-content">
                        <h4><?php echo escape($post['username']); ?></h4>
                        <p><?php echo escape($post['city'] ?? 'Location not specified'); ?></p>
                    </div>
                </div>
                
                <?php if (!$is_owner && isLoggedIn()): ?>
                    <div class="contact-actions">
                        <a href="mailto:<?php echo escape($post['email']); ?>" class="btn btn-outline">
                            📧 <?php echo t('email_seller'); ?>
                        </a>
                        <?php if ($post['phone']): ?>
                            <a href="tel:<?php echo escape($post['phone']); ?>" class="btn btn-outline">
                                📱 <?php echo escape($post['phone']); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php elseif (!isLoggedIn()): ?>
                    <p class="login-prompt">
                        <a href="/pages/login.php">Login</a> to contact the seller
                    </p>
                <?php elseif ($is_owner): ?>
                    <div class="owner-actions">
                        <a href="/pages/edit-post.php?id=<?php echo $post['id']; ?>" class="btn btn-primary"><?php echo t('edit_listing'); ?></a>
                        <a href="#" class="btn btn-danger" onclick="confirmDelete(<?php echo $post['id']; ?>, '<?php echo addslashes(t('delete_listing')); ?>'); return false;"><?php echo t('delete_listing'); ?></a>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if ($post['status'] === 'available' && !$is_owner && isLoggedIn()): ?>
                <?php
                // Check if item is already in wishlist
                if (!isset($pdo)) {
                    $pdo = getDB();
                }
                if (!isset($user)) {
                    $user = getCurrentUser();
                }
                $stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND post_id = ?");
                $stmt->execute([$user['id'], $post['id']]);
                $in_wishlist = $stmt->fetch();
                ?>
                <div class="listing-actions">
                    <form action="/api/add-to-cart.php" method="POST" id="add-to-cart-form">
                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                        <div class="form-group">
                            <label for="cart_unit"><?php echo t('unit'); ?></label>
                            <select id="cart_unit" name="cart_unit" required onchange="updateQuantityLimits()">
                                <option value="kg"><?php echo t('kg'); ?></option>
                                <option value="tonnes"><?php echo t('tonnes'); ?></option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="cart_quantity" id="cart_quantity_label"><?php echo t('quantity'); ?> (kg)</label>
                            <input type="number" id="cart_quantity" name="quantity" min="0.1" step="0.1" placeholder="<?php echo t('enter_quantity'); ?>" required>
                            <small id="quantity_hint" class="form-hint"></small>
                        </div>
                        <button type="submit" class="btn btn-primary btn-cart"><?php echo t('add_to_cart'); ?></button>
                    </form>
                    <div class="wishlist-action">
                        <?php if ($in_wishlist): ?>
                            <form action="/api/remove-from-wishlist.php" method="POST">
                                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                <button type="submit" class="btn btn-outline btn-wishlist">
                                    ❤️ <?php echo t('remove_from_wishlist'); ?>
                                </button>
                            </form>
                        <?php else: ?>
                            <form action="/api/add-to-wishlist.php" method="POST">
                                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                <button type="submit" class="btn btn-outline btn-wishlist">
                                    🤍 <?php echo t('add_to_wishlist'); ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function changeMainImage(src, thumbnail) {
    document.getElementById('mainImage').src = src;
    document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
    thumbnail.classList.add('active');
}

// Store post data for unit conversion (using remaining quantity)
const postData = {
    quantity: <?php echo $remaining_quantity; ?>,
    unit: '<?php echo escape($remaining_unit); ?>'
};

function updateQuantityLimits() {
    const buyerUnit = document.getElementById('cart_unit').value;
    const quantityInput = document.getElementById('cart_quantity');
    const quantityLabel = document.getElementById('cart_quantity_label');
    const quantityHint = document.getElementById('quantity_hint');
    
    // Update label
    quantityLabel.textContent = 'Quantity (' + buyerUnit + ')';
    
    // Convert post quantity to buyer's unit for max limit
    let maxQuantity = postData.quantity;
    if (buyerUnit !== postData.unit) {
        if (buyerUnit === 'kg' && postData.unit === 'tonnes') {
            maxQuantity = postData.quantity * 1000; // Convert tonnes to kg
        } else if (buyerUnit === 'tonnes' && postData.unit === 'kg') {
            maxQuantity = postData.quantity / 1000; // Convert kg to tonnes
        }
    }
    
    // Update max attribute
    quantityInput.setAttribute('max', maxQuantity);
    quantityInput.setAttribute('step', buyerUnit === 'tonnes' ? '0.01' : '0.1');
    
    // Update hint - using static text since PHP can't be used in JavaScript
    const availableLabel = '<?php echo t('available_quantity'); ?>';
    const tonnesLabel = '<?php echo t('tonnes'); ?>';
    const availableText = buyerUnit === 'tonnes' 
        ? `${availableLabel}: ${(postData.quantity / 1000).toFixed(2)} ${tonnesLabel} (${postData.quantity} kg)`
        : `${availableLabel}: ${postData.quantity} kg (${(postData.quantity / 1000).toFixed(2)} ${tonnesLabel})`;
    quantityHint.textContent = availableText;
}

// Initialize on page load
if (document.getElementById('cart_unit')) {
    updateQuantityLimits();
}

// Custom delete confirmation modal
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

<style>
.listing-detail {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 48px;
    margin: 40px 0;
}

.listing-gallery {
    position: sticky;
    top: 100px;
    height: fit-content;
}

.main-image {
    width: 100%;
    height: 500px;
    border-radius: 16px;
    overflow: hidden;
    margin-bottom: 16px;
    background: var(--bg-light);
}

.main-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.thumbnail-gallery {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 12px;
}

.thumbnail {
    width: 100%;
    height: 100px;
    object-fit: cover;
    border-radius: 8px;
    cursor: pointer;
    border: 2px solid transparent;
    transition: all 0.2s;
}

.thumbnail:hover,
.thumbnail.active {
    border-color: var(--primary-green);
}

.form-hint {
    display: block;
    margin-top: 6px;
    font-size: 0.85rem;
    color: var(--text-gray);
    font-style: italic;
}

#add-to-cart-form .form-group {
    margin-bottom: 16px;
}

#add-to-cart-form select,
#add-to-cart-form input[type="number"] {
    width: 100%;
    padding: 12px;
    border: 2px solid var(--border-light);
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.2s;
}

#add-to-cart-form select:focus,
#add-to-cart-form input[type="number"]:focus {
    outline: none;
    border-color: var(--primary-green);
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

.listing-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 24px;
}

.listing-header h1 {
    font-size: 2rem;
    line-height: 1.2;
}

.listing-price {
    background: var(--primary-green-light);
    padding: 24px;
    border-radius: 12px;
    margin-bottom: 32px;
}

.price-amount {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--primary-green);
}

.price-unit {
    font-size: 1.2rem;
    color: var(--text-gray);
    margin-left: 8px;
}

.listing-info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin-bottom: 32px;
}

.info-box {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.info-box label {
    font-size: 0.9rem;
    color: var(--text-gray);
    font-weight: 600;
}

.info-box span {
    font-size: 1.05rem;
    color: var(--text-dark);
}

.listing-description {
    margin-bottom: 32px;
    padding: 24px;
    background: var(--bg-light);
    border-radius: 12px;
}

.listing-description h3 {
    margin-bottom: 12px;
}

.seller-info {
    background: var(--bg-white);
    border: 1px solid var(--border-light);
    border-radius: var(--radius-xl);
    padding: 28px;
    margin-bottom: 24px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06), 0 1px 4px rgba(0, 0, 0, 0.04);
}

.seller-info h3 {
    margin-bottom: 20px;
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--text-dark);
}

.seller-details {
    display: flex;
    gap: 16px;
    align-items: center;
    margin-bottom: 24px;
}

.seller-avatar {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: var(--green-gradient);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    font-weight: 700;
    flex-shrink: 0;
}

.seller-content h4 {
    margin-bottom: 4px;
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--text-dark);
}

.seller-content p {
    color: var(--text-gray);
    font-size: 0.95rem;
}

.contact-actions {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 0;
}

.contact-actions .btn {
    width: 100%;
    justify-content: center;
    transition: all var(--transition-base);
    position: relative;
    overflow: hidden;
}

.contact-actions .btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(209, 250, 229, 0.6);
    opacity: 0;
    transition: opacity var(--transition-base);
    z-index: 0;
}

.contact-actions .btn:hover::before {
    opacity: 1;
}

.contact-actions .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(5, 150, 105, 0.2);
    border-color: var(--primary-green);
}

.contact-actions .btn span,
.contact-actions .btn {
    position: relative;
    z-index: 1;
}

.listing-actions {
    margin-top: 24px;
}

#add-to-cart-form {
    margin-top: 0;
    margin-bottom: 0;
}

.btn-cart {
    width: 100%;
    padding: 14px 24px;
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 12px;
}

.wishlist-action {
    margin-top: 12px;
}

.wishlist-action form {
    width: 100%;
    margin: 0;
}

.btn-wishlist {
    width: 100%;
    padding: 14px 24px;
    font-size: 1rem;
    font-weight: 600;
    text-align: center;
    transition: all var(--transition-base);
    position: relative;
    overflow: hidden;
}

.btn-wishlist::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(209, 250, 229, 0.6);
    opacity: 0;
    transition: opacity var(--transition-base);
    z-index: 0;
}

.btn-wishlist:hover::before {
    opacity: 1;
}

.btn-wishlist:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(5, 150, 105, 0.2);
    border-color: var(--primary-green);
}

.btn-wishlist span,
.btn-wishlist {
    position: relative;
    z-index: 1;
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

@media (max-width: 968px) {
    .listing-detail {
        grid-template-columns: 1fr;
    }
    
    .listing-gallery {
        position: relative;
        top: 0;
    }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

