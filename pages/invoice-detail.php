<?php
/**
 * Invoice Detail Page
 */

$page_title = 'Invoice Details';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('/pages/login.php');
}

$user = getCurrentUser();
$invoice_id = intval($_GET['id'] ?? 0);

if (!$invoice_id) {
    redirect('/pages/invoices.php');
}

require_once __DIR__ . '/../includes/header.php';

$pdo = getDB();

// Get invoice details
$stmt = $pdo->prepare("
    SELECT i.*, o.*, p.title as order_title, p.quantity as order_quantity, p.unit as order_unit,
           buyer.username as buyer_name, buyer.email as buyer_email, buyer.phone as buyer_phone, buyer.address as buyer_address, buyer.city as buyer_city,
           seller.username as seller_name, seller.email as seller_email, seller.phone as seller_phone, seller.address as seller_address, seller.city as seller_city
    FROM invoices i
    LEFT JOIN orders o ON i.order_id = o.id
    LEFT JOIN posts p ON o.post_id = p.id
    LEFT JOIN users buyer ON o.buyer_id = buyer.id
    LEFT JOIN users seller ON o.seller_id = seller.id
    WHERE i.id = ? AND i.user_id = ?
");
$stmt->execute([$invoice_id, $user['id']]);
$invoice = $stmt->fetch();

if (!$invoice) {
    redirect('/pages/invoices.php?error=not_found');
}
?>

<div class="container">
    <div class="invoice-detail-header">
        <div>
            <h1><?php echo t('invoice'); ?> #<?php echo $invoice['id']; ?></h1>
            <p class="invoice-date"><?php echo t('issued_on'); ?>: <?php echo formatDate($invoice['created_at']); ?></p>
        </div>
        <div class="invoice-actions">
            <a href="/api/download-invoice.php?id=<?php echo $invoice['id']; ?>" class="btn btn-primary" target="_blank"><?php echo t('download_pdf'); ?></a>
            <a href="/pages/invoices.php" class="btn btn-outline"><?php echo t('back_to_invoices'); ?></a>
        </div>
    </div>
    
    <div class="invoice-detail-content">
        <div class="invoice-section">
            <h2><?php echo t('invoice_details'); ?></h2>
            <div class="detail-grid">
                <div class="detail-item">
                    <label><?php echo t('invoice_number'); ?>:</label>
                    <span>#<?php echo $invoice['id']; ?></span>
                </div>
                <div class="detail-item">
                    <label><?php echo t('invoice_type'); ?>:</label>
                    <span>
                        <?php if ($invoice['invoice_type'] === 'order'): ?>
                            <?php if ($invoice['invoice_role'] === 'buyer'): ?>
                                <?php echo t('buyer_invoice'); ?>
                            <?php else: ?>
                                <?php echo t('seller_invoice'); ?>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php echo t('account_creation'); ?>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="detail-item">
                    <label><?php echo t('date'); ?>:</label>
                    <span><?php echo formatDate($invoice['created_at']); ?></span>
                </div>
                <?php if ($invoice['order_id']): ?>
                    <div class="detail-item">
                        <label><?php echo t('order_number'); ?>:</label>
                        <span>#<?php echo $invoice['order_id']; ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($invoice['invoice_type'] === 'order' && $invoice['order_id']): ?>
            <div class="invoice-section">
                <h2><?php echo t('order_details'); ?></h2>
                <div class="order-details">
                    <div class="order-item-detail">
                        <h3><?php echo escape($invoice['order_title']); ?></h3>
                        <p><?php echo t('quantity'); ?>: <?php echo $invoice['order_quantity']; ?> <?php echo $invoice['order_unit']; ?></p>
                        <p><?php echo t('unit_price'); ?>: <?php echo formatPrice($invoice['unit_price']); ?> / kg</p>
                    </div>
                </div>
            </div>
            
            <div class="invoice-section">
                <h2><?php echo t('parties'); ?></h2>
                <div class="parties-grid">
                    <div class="party-card">
                        <h3><?php echo t('buyer'); ?></h3>
                        <p><strong><?php echo escape($invoice['buyer_name']); ?></strong></p>
                        <p><?php echo escape($invoice['buyer_email']); ?></p>
                        <?php if ($invoice['buyer_phone']): ?>
                            <p><?php echo escape($invoice['buyer_phone']); ?></p>
                        <?php endif; ?>
                        <?php if ($invoice['buyer_address']): ?>
                            <p><?php echo escape($invoice['buyer_address']); ?></p>
                        <?php endif; ?>
                        <?php if ($invoice['buyer_city']): ?>
                            <p><?php echo escape($invoice['buyer_city']); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="party-card">
                        <h3><?php echo t('seller'); ?></h3>
                        <p><strong><?php echo escape($invoice['seller_name']); ?></strong></p>
                        <p><?php echo escape($invoice['seller_email']); ?></p>
                        <?php if ($invoice['seller_phone']): ?>
                            <p><?php echo escape($invoice['seller_phone']); ?></p>
                        <?php endif; ?>
                        <?php if ($invoice['seller_address']): ?>
                            <p><?php echo escape($invoice['seller_address']); ?></p>
                        <?php endif; ?>
                        <?php if ($invoice['seller_city']): ?>
                            <p><?php echo escape($invoice['seller_city']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="invoice-section">
            <h2><?php echo t('payment_summary'); ?></h2>
            <div class="payment-summary">
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
                        <span>
                            <?php if ($invoice['invoice_role'] === 'buyer'): ?>
                                +<?php echo formatPrice($invoice['commission']); ?>
                            <?php else: ?>
                                -<?php echo formatPrice($invoice['commission']); ?>
                            <?php endif; ?>
                        </span>
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
        
        <div class="invoice-section">
            <h2><?php echo t('payment_status'); ?></h2>
            <div class="payment-status">
                <span class="status-badge status-paid"><?php echo t('paid'); ?></span>
                <p class="payment-note"><?php echo t('virtual_payment_note'); ?></p>
            </div>
        </div>
    </div>
</div>

<style>
.invoice-detail-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 32px;
    padding-bottom: 24px;
    border-bottom: 2px solid var(--border-light);
}

.invoice-detail-header h1 {
    margin-bottom: 8px;
}

.invoice-date {
    color: var(--text-gray);
    font-size: 0.95rem;
}

.invoice-actions {
    display: flex;
    gap: 12px;
}

.invoice-detail-content {
    display: grid;
    gap: 32px;
}

.invoice-section {
    background: var(--white);
    padding: 24px;
    border-radius: 12px;
    border: 1px solid var(--border-light);
}

.invoice-section h2 {
    font-size: 1.25rem;
    margin-bottom: 20px;
    color: var(--text-dark);
}

.detail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 16px;
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.detail-item label {
    font-weight: 600;
    color: var(--text-gray);
    font-size: 0.9rem;
}

.detail-item span {
    color: var(--text-dark);
    font-size: 1rem;
}

.order-details {
    background: #f9fafb;
    padding: 16px;
    border-radius: 8px;
}

.order-item-detail h3 {
    margin-bottom: 8px;
}

.order-item-detail p {
    margin: 4px 0;
    color: var(--text-gray);
}

.parties-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 24px;
}

.party-card {
    background: #f9fafb;
    padding: 20px;
    border-radius: 8px;
}

.party-card h3 {
    font-size: 1.1rem;
    margin-bottom: 12px;
    color: var(--primary-green);
}

.party-card p {
    margin: 4px 0;
    font-size: 0.9rem;
    color: var(--text-dark);
}

.payment-summary {
    background: #f9fafb;
    padding: 20px;
    border-radius: 8px;
}

.payment-summary .summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 12px;
    font-size: 1rem;
}

.payment-summary .total {
    font-weight: 700;
    font-size: 1.25rem;
    color: var(--primary-green);
    margin-top: 12px;
    padding-top: 12px;
    border-top: 2px solid var(--border-light);
}

.payment-status {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.status-paid {
    background: #d1fae5;
    color: #065f46;
    padding: 8px 16px;
    border-radius: 6px;
    display: inline-block;
    width: fit-content;
    font-weight: 600;
}

.payment-note {
    color: var(--text-gray);
    font-size: 0.9rem;
    font-style: italic;
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>


