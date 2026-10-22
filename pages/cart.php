<?php
/**
 * Shopping Cart Page
 */

$page_title = 'Shopping Cart';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('/pages/login.php');
}

$user = getCurrentUser();
require_once __DIR__ . '/../includes/header.php';

$pdo = getDB();

// Get cart items with current availability
$stmt = $pdo->prepare("
    SELECT c.*, p.title, p.price_per_kg, p.unit as post_unit, p.status, p.user_id as seller_id,
           p.quantity as post_quantity,
           u.username as seller_name,
           COALESCE(c.buyer_unit, p.unit) as buyer_unit,
           (SELECT image_path FROM post_images WHERE post_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
    FROM cart c 
    JOIN posts p ON c.post_id = p.id 
    JOIN users u ON p.user_id = u.id
    WHERE c.user_id = ?
");
$stmt->execute([$user['id']]);
$cart_items = $stmt->fetchAll();

// Filter out items that are no longer available or have insufficient quantity
$valid_items = [];
foreach ($cart_items as $item) {
    if ($item['status'] === 'available' && $item['quantity'] <= $item['post_quantity']) {
        $valid_items[] = $item;
    } else {
        // Remove invalid item from cart
        $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ?");
        $stmt->execute([$item['id']]);
    }
}
$cart_items = $valid_items;

// Calculate total
$subtotal = 0;
foreach ($cart_items as $item) {
    // Convert cart quantity (stored in post unit) to kg for price calculation
    $quantity_in_kg = $item['quantity'];
    if ($item['post_unit'] === 'tonnes') {
        $quantity_in_kg = $item['quantity'] * 1000; // Convert tonnes to kg
    }
    $subtotal += $quantity_in_kg * $item['price_per_kg'];
}
$commission = $subtotal * BUYER_COMMISSION_RATE; // Buyer commission (added to subtotal)
$total = $subtotal + $commission;
?>

<div class="container">
    <h1 class="page-title"><?php echo t('shopping_cart'); ?></h1>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error" style="margin-bottom: 24px;">
            <?php
            $errors = [
                'invalid' => t('invalid_input'),
                'not_found' => t('error'),
                'insufficient_quantity' => t('insufficient_quantity'),
                'not_available' => t('item_not_available'),
                'cannot_buy_own' => t('cannot_buy_own_listing'),
                'empty' => t('cart_empty_error'),
                'checkout_failed' => t('checkout_failed')
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
    
    <?php if (empty($cart_items)): ?>
        <div class="empty-cart">
            <div class="empty-icon">🛒</div>
            <h2><?php echo t('empty_cart'); ?></h2>
            <p><?php echo t('browse_listings'); ?></p>
            <a href="/pages/browse.php" class="btn btn-primary"><?php echo t('browse'); ?></a>
        </div>
    <?php else: ?>
        <div class="cart-layout">
            <!-- Cart Items -->
            <div class="cart-items">
                <?php foreach ($cart_items as $item): ?>
                    <div class="cart-item">
                        <div class="cart-item-image">
                            <?php if ($item['primary_image']): ?>
                                <img src="<?php echo escape($item['primary_image']); ?>" alt="<?php echo escape($item['title']); ?>">
                            <?php else: ?>
                                <div class="no-image-small">No Image</div>
                            <?php endif; ?>
                        </div>
                        <div class="cart-item-info">
                            <h3><a href="/pages/listing.php?id=<?php echo $item['post_id']; ?>"><?php echo escape($item['title']); ?></a></h3>
                            <p class="seller"><?php echo t('seller'); ?>: <?php echo escape($item['seller_name']); ?></p>
                            <p class="price"><?php echo formatPrice($item['price_per_kg']); ?> / kg</p>
                        </div>
                        <div class="cart-item-quantity">
                            <?php
                            // Convert cart quantity (stored in post unit) to buyer's unit for display
                            $display_quantity = $item['quantity'];
                            if ($item['buyer_unit'] !== $item['post_unit']) {
                                if ($item['buyer_unit'] === 'kg' && $item['post_unit'] === 'tonnes') {
                                    $display_quantity = $item['quantity'] * 1000;
                                } else if ($item['buyer_unit'] === 'tonnes' && $item['post_unit'] === 'kg') {
                                    $display_quantity = $item['quantity'] / 1000;
                                }
                            }
                            ?>
                            <?php
                            // Calculate max available in buyer's unit
                            $max_in_buyer_unit = $item['post_quantity'];
                            if ($item['buyer_unit'] !== $item['post_unit']) {
                                if ($item['buyer_unit'] === 'kg' && $item['post_unit'] === 'tonnes') {
                                    $max_in_buyer_unit = $item['post_quantity'] * 1000;
                                } else if ($item['buyer_unit'] === 'tonnes' && $item['post_unit'] === 'kg') {
                                    $max_in_buyer_unit = $item['post_quantity'] / 1000;
                                }
                            }
                            ?>
                            <form action="/api/update-cart.php" method="POST" class="quantity-form">
                                <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                <input type="hidden" name="buyer_unit" value="<?php echo escape($item['buyer_unit']); ?>">
                                <input type="number" name="quantity" value="<?php echo number_format($display_quantity, 2, '.', ''); ?>" 
                                       min="0.1" step="<?php echo $item['buyer_unit'] === 'tonnes' ? '0.01' : '0.1'; ?>" 
                                       max="<?php echo number_format($max_in_buyer_unit, 2, '.', ''); ?>"
                                       onchange="this.form.submit()">
                                <span><?php echo escape($item['buyer_unit']); ?></span>
                            </form>
                            <small class="available-hint"><?php echo t('available'); ?>: <?php echo number_format($max_in_buyer_unit, 2); ?> <?php echo escape($item['buyer_unit']); ?></small>
                        </div>
                        <div class="cart-item-total">
                            <?php
                            // Calculate item total: convert to kg first
                            $item_quantity_kg = $item['quantity'];
                            if ($item['post_unit'] === 'tonnes') {
                                $item_quantity_kg = $item['quantity'] * 1000;
                            }
                            $item_total = $item_quantity_kg * $item['price_per_kg'];
                            ?>
                            <p class="item-total"><?php echo formatPrice($item_total); ?></p>
                        </div>
                        <div class="cart-item-actions">
                            <a href="/api/remove-from-cart.php?id=<?php echo $item['id']; ?>" class="remove-btn" onclick="return confirm('<?php echo t('remove_item'); ?>')">✕</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Cart Summary -->
            <div class="cart-summary">
                <h2><?php echo t('cart_total'); ?></h2>
                <div class="summary-row">
                    <span><?php echo t('subtotal'); ?></span>
                    <span><?php echo formatPrice($subtotal); ?></span>
                </div>
                <div class="summary-row">
                    <span><?php echo t('commission'); ?> (<?php echo (BUYER_COMMISSION_RATE * 100); ?>%)</span>
                    <span><?php echo formatPrice($commission); ?></span>
                </div>
                <div class="summary-divider"></div>
                <div class="summary-row summary-total">
                    <span><?php echo t('cart_total'); ?></span>
                    <span><?php echo formatPrice($total); ?></span>
                </div>
                <form action="/api/checkout.php" method="POST">
                    <button type="submit" class="btn btn-primary btn-block"><?php echo t('proceed_to_checkout'); ?></button>
                </form>
                <a href="/pages/browse.php" class="continue-shopping">← <?php echo t('continue_shopping'); ?></a>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.empty-cart {
    text-align: center;
    padding: 100px 20px;
}

.empty-icon {
    font-size: 5rem;
    margin-bottom: 24px;
    opacity: 0.5;
}

.empty-cart h2 {
    font-size: 2rem;
    margin-bottom: 12px;
}

.empty-cart p {
    color: var(--text-gray);
    margin-bottom: 32px;
}

.cart-layout {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 32px;
    margin-top: 32px;
}

.cart-items {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.cart-item {
    background: var(--white);
    border: 1px solid var(--border-light);
    border-radius: 12px;
    padding: 20px;
    display: grid;
    grid-template-columns: 100px 1fr 150px 120px 40px;
    gap: 20px;
    align-items: center;
}

.cart-item-image img,
.no-image-small {
    width: 100px;
    height: 100px;
    object-fit: cover;
    border-radius: 8px;
}

.no-image-small {
    background: var(--bg-light);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-gray);
    font-size: 0.85rem;
}

.cart-item-info h3 {
    font-size: 1.1rem;
    margin-bottom: 8px;
}

.cart-item-info h3 a {
    color: var(--text-dark);
    text-decoration: none;
}

.cart-item-info h3 a:hover {
    color: var(--primary-green);
}

.cart-item-info .seller,
.cart-item-info .price {
    font-size: 0.9rem;
    color: var(--text-gray);
    margin: 4px 0;
}

.quantity-form {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.quantity-form input {
    padding: 8px;
    border: 2px solid var(--border-light);
    border-radius: 6px;
    width: 100%;
}

.item-total {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--primary-green);
}

.remove-btn {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--bg-light);
    color: var(--text-gray);
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    font-size: 1.2rem;
    transition: all 0.2s;
}

.remove-btn:hover {
    background: #fee2e2;
    color: #ef4444;
}

.cart-summary {
    background: var(--white);
    border: 1px solid var(--border-light);
    border-radius: 16px;
    padding: 24px;
    height: fit-content;
    position: sticky;
    top: 100px;
}

.cart-summary h2 {
    font-size: 1.5rem;
    margin-bottom: 24px;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 16px;
    font-size: 1rem;
}

.summary-divider {
    border-top: 1px solid var(--border-light);
    margin: 20px 0;
}

.summary-total {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--text-dark);
}

.cart-summary form {
    margin: 24px 0;
}

.continue-shopping {
    display: block;
    text-align: center;
    color: var(--primary-green);
    text-decoration: none;
    font-weight: 500;
}

@media (max-width: 968px) {
    .cart-layout {
        grid-template-columns: 1fr;
    }
    
    .cart-item {
        grid-template-columns: 80px 1fr;
        gap: 12px;
    }
    
    .cart-item-quantity,
    .cart-item-total {
        grid-column: 2;
    }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

