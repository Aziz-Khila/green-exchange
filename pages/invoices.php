<?php
/**
 * Invoices Page - User's Invoice History
 */

$page_title = 'My Invoices';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('/pages/login.php');
}

$user = getCurrentUser();
require_once __DIR__ . '/../includes/header.php';

$pdo = getDB();

// Get active tab from query parameter
$active_tab = $_GET['tab'] ?? 'buyer';
if (!in_array($active_tab, ['buyer', 'seller', 'all'])) {
    $active_tab = 'buyer';
}

// Get user's invoices
$stmt = $pdo->prepare("
    SELECT i.*, o.id as order_id, o.status as order_status, p.title as order_title
    FROM invoices i
    LEFT JOIN orders o ON i.order_id = o.id
    LEFT JOIN posts p ON o.post_id = p.id
    WHERE i.user_id = ?
    ORDER BY i.created_at DESC
");
$stmt->execute([$user['id']]);
$all_invoices = $stmt->fetchAll();

// Filter invoices by role
$buyer_invoices = array_filter($all_invoices, fn($i) => $i['invoice_role'] === 'buyer' || $i['invoice_type'] === 'account_creation');
$seller_invoices = array_filter($all_invoices, fn($i) => $i['invoice_role'] === 'seller');

// Get invoices for active tab
if ($active_tab === 'buyer') {
    $invoices = $buyer_invoices;
} elseif ($active_tab === 'seller') {
    $invoices = $seller_invoices;
} else {
    $invoices = $all_invoices;
}

// Get statistics
$total_invoices = count($all_invoices);
$total_paid = array_sum(array_column($all_invoices, 'total'));
$buyer_invoices_count = count($buyer_invoices);
$seller_invoices_count = count($seller_invoices);
$account_invoices = count(array_filter($all_invoices, fn($i) => $i['invoice_type'] === 'account_creation'));
?>

<div class="container">
    <h1 class="page-title"><?php echo t('my_invoices'); ?></h1>
    
    <!-- Tabs -->
    <div class="invoice-tabs">
        <a href="?tab=buyer" class="invoice-tab <?php echo $active_tab === 'buyer' ? 'active' : ''; ?>">
            <i class="fa-solid fa-shopping-cart"></i>
            <?php echo t('buyer_invoices'); ?> (<?php echo $buyer_invoices_count; ?>)
        </a>
        <a href="?tab=seller" class="invoice-tab <?php echo $active_tab === 'seller' ? 'active' : ''; ?>">
            <i class="fa-solid fa-store"></i>
            <?php echo t('seller_invoices'); ?> (<?php echo $seller_invoices_count; ?>)
        </a>
    </div>
    
    <!-- Statistics -->
    <div class="invoices-stats">
        <div class="stat-card">
            <div class="stat-icon" style="background: #dbeafe; color: #1e40af;">📄</div>
            <div class="stat-content">
                <h3><?php echo $total_invoices; ?></h3>
                <p><?php echo t('total_invoices'); ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: #d1fae5; color: #065f46;">💰</div>
            <div class="stat-content">
                <h3><?php echo formatPrice($total_paid); ?></h3>
                <p><?php echo t('total_paid'); ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: #dbeafe; color: #1e40af;">🛒</div>
            <div class="stat-content">
                <h3><?php echo $buyer_invoices_count; ?></h3>
                <p><?php echo t('buyer_invoices'); ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: #fef3c7; color: #92400e;">🏪</div>
            <div class="stat-content">
                <h3><?php echo $seller_invoices_count; ?></h3>
                <p><?php echo t('seller_invoices'); ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: #e0e7ff; color: #3730a3;">👤</div>
            <div class="stat-content">
                <h3><?php echo $account_invoices; ?></h3>
                <p><?php echo t('account_invoices'); ?></p>
            </div>
        </div>
    </div>
    
    <?php if (empty($invoices)): ?>
        <div class="empty-state">
            <div class="empty-icon">📋</div>
            <h2><?php echo t('no_invoices_yet'); ?></h2>
            <p><?php echo t('no_invoices_description'); ?></p>
        </div>
    <?php else: ?>
        <div class="invoices-list">
            <?php foreach ($invoices as $invoice): ?>
                <div class="invoice-card">
                    <div class="invoice-header">
                        <div>
                            <h3><?php echo t('invoice'); ?> #<?php echo $invoice['id']; ?></h3>
                            <p class="invoice-date"><?php echo formatDate($invoice['created_at']); ?></p>
                            <p class="invoice-type">
                                <?php if ($invoice['invoice_type'] === 'order'): ?>
                                    <?php if ($invoice['invoice_role'] === 'buyer'): ?>
                                        <span class="badge badge-buyer"><?php echo t('buyer_invoice'); ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-seller"><?php echo t('seller_invoice'); ?></span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge badge-account"><?php echo t('account_creation'); ?></span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="invoice-actions">
                            <a href="/pages/invoice-detail.php?id=<?php echo $invoice['id']; ?>" class="btn btn-outline btn-small"><?php echo t('view_invoice'); ?></a>
                            <a href="/api/download-invoice.php?id=<?php echo $invoice['id']; ?>" class="btn btn-primary btn-small" target="_blank"><?php echo t('download_pdf'); ?></a>
                        </div>
                    </div>
                    
                    <?php if ($invoice['invoice_type'] === 'order' && $invoice['order_title']): ?>
                        <div class="invoice-order-info">
                            <p><strong><?php echo t('order'); ?>:</strong> <?php echo escape($invoice['order_title']); ?></p>
                            <?php if ($invoice['order_id']): ?>
                                <p><strong><?php echo t('order_number'); ?>:</strong> #<?php echo $invoice['order_id']; ?></p>
                                <p><strong><?php echo t('order_status'); ?>:</strong> 
                                    <span class="status-badge status-<?php echo $invoice['order_status']; ?>">
                                        <?php echo t($invoice['order_status']); ?>
                                    </span>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="invoice-summary">
                        <div class="summary-row">
                            <span><?php echo t('amount'); ?>:</span>
                            <span><?php echo formatPrice($invoice['amount']); ?></span>
                        </div>
                        <?php if ($invoice['commission'] > 0): ?>
                            <div class="summary-row">
                                <span>
                                    <?php echo t('commission'); ?> 
                                    (<?php echo $invoice['invoice_role'] === 'buyer' ? '+' : '-'; ?><?php echo (BUYER_COMMISSION_RATE * 100); ?>%):
                                </span>
                                <span><?php echo formatPrice($invoice['commission']); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($invoice['fees'] > 0): ?>
                            <div class="summary-row">
                                <span><?php echo t('fees'); ?>:</span>
                                <span><?php echo formatPrice($invoice['fees']); ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="summary-row total">
                            <span><?php echo t('total'); ?>:</span>
                            <span><?php echo formatPrice($invoice['total']); ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.invoice-tabs {
    display: flex;
    gap: 12px;
    margin-bottom: 32px;
    border-bottom: 2px solid var(--border-light);
}

.invoice-tab {
    padding: 12px 24px;
    text-decoration: none;
    color: var(--text-gray);
    font-weight: 600;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
}

.invoice-tab:hover {
    color: var(--primary-green);
}

.invoice-tab.active {
    color: var(--primary-green);
    border-bottom-color: var(--primary-green);
}

.invoice-tab i {
    font-size: 1rem;
}

.invoices-stats {
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

.invoices-list {
    display: grid;
    gap: 24px;
    margin-top: 32px;
}

.invoice-card {
    background: var(--white);
    border: 1px solid var(--border-light);
    border-radius: 16px;
    padding: 24px;
}

.invoice-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--border-light);
}

.invoice-header h3 {
    margin-bottom: 4px;
}

.invoice-date {
    font-size: 0.85rem;
    color: var(--text-gray);
    margin-bottom: 8px;
}

.invoice-type {
    margin-top: 8px;
}

.badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 600;
}

.badge-buyer {
    background: #dbeafe;
    color: #1e40af;
}

.badge-seller {
    background: #fef3c7;
    color: #92400e;
}

.badge-account {
    background: #e0e7ff;
    color: #3730a3;
}

.invoice-actions {
    display: flex;
    gap: 8px;
}

.btn-small {
    padding: 8px 16px;
    font-size: 0.9rem;
}

.invoice-order-info {
    background: #f9fafb;
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.invoice-order-info p {
    margin: 4px 0;
    font-size: 0.9rem;
}

.invoice-summary {
    padding-top: 16px;
    border-top: 1px solid var(--border-light);
}

.invoice-summary .summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    font-size: 0.95rem;
}

.invoice-summary .total {
    font-weight: 700;
    font-size: 1.1rem;
    color: var(--primary-green);
    margin-top: 8px;
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
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>


