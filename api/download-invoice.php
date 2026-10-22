<?php
/**
 * Download Invoice as PDF (Simple HTML version for now)
 */

require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    redirect('/pages/login.php');
}

$user = getCurrentUser();
$invoice_id = intval($_GET['id'] ?? 0);

if (!$invoice_id) {
    redirect('/pages/invoices.php');
}

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

// Set headers for PDF download (for now, we'll output HTML that can be printed)
header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: attachment; filename="invoice-' . $invoice_id . '.html"');
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('invoice'); ?> #<?php echo $invoice_id; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            padding: 40px;
            color: #333;
        }
        .invoice-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 2px solid #ddd;
        }
        .invoice-title {
            font-size: 2rem;
            color: #10b981;
        }
        .invoice-info {
            text-align: right;
        }
        .section {
            margin-bottom: 30px;
        }
        .section h2 {
            font-size: 1.25rem;
            margin-bottom: 15px;
            color: #10b981;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table th, table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        table th {
            background: #f9fafb;
            font-weight: 600;
        }
        .total-row {
            font-weight: 700;
            font-size: 1.1rem;
            background: #f0fdf4;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #ddd;
            text-align: center;
            color: #666;
            font-size: 0.9rem;
        }
        @media print {
            body {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-header">
        <div>
            <h1 class="invoice-title"><?php echo t('invoice'); ?></h1>
            <p><strong><?php echo t('invoice_number'); ?>:</strong> #<?php echo $invoice['id']; ?></p>
            <p><strong><?php echo t('date'); ?>:</strong> <?php echo formatDate($invoice['created_at']); ?></p>
        </div>
        <div class="invoice-info">
            <h2>GREEN EXCHANGE</h2>
            <p><?php echo t('waste_trading_marketplace'); ?></p>
        </div>
    </div>
    
    <?php if ($invoice['invoice_type'] === 'order' && $invoice['order_id']): ?>
        <div class="section">
            <h2><?php echo t('order_details'); ?></h2>
            <table>
                <tr>
                    <th><?php echo t('description'); ?></th>
                    <th><?php echo t('quantity'); ?></th>
                    <th><?php echo t('unit_price'); ?></th>
                    <th><?php echo t('amount'); ?></th>
                </tr>
                <tr>
                    <td><?php echo escape($invoice['order_title']); ?></td>
                    <td><?php echo $invoice['order_quantity']; ?> <?php echo $invoice['order_unit']; ?></td>
                    <td><?php echo formatPrice($invoice['unit_price']); ?> / kg</td>
                    <td><?php echo formatPrice($invoice['amount']); ?></td>
                </tr>
            </table>
        </div>
        
        <div class="section">
            <h2><?php echo t('parties'); ?></h2>
            <table>
                <tr>
                    <th><?php echo t('buyer'); ?></th>
                    <th><?php echo t('seller'); ?></th>
                </tr>
                <tr>
                    <td>
                        <strong><?php echo escape($invoice['buyer_name']); ?></strong><br>
                        <?php echo escape($invoice['buyer_email']); ?><br>
                        <?php if ($invoice['buyer_phone']): ?>
                            <?php echo escape($invoice['buyer_phone']); ?><br>
                        <?php endif; ?>
                        <?php if ($invoice['buyer_address']): ?>
                            <?php echo escape($invoice['buyer_address']); ?><br>
                        <?php endif; ?>
                        <?php if ($invoice['buyer_city']): ?>
                            <?php echo escape($invoice['buyer_city']); ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?php echo escape($invoice['seller_name']); ?></strong><br>
                        <?php echo escape($invoice['seller_email']); ?><br>
                        <?php if ($invoice['seller_phone']): ?>
                            <?php echo escape($invoice['seller_phone']); ?><br>
                        <?php endif; ?>
                        <?php if ($invoice['seller_address']): ?>
                            <?php echo escape($invoice['seller_address']); ?><br>
                        <?php endif; ?>
                        <?php if ($invoice['seller_city']): ?>
                            <?php echo escape($invoice['seller_city']); ?>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>
    <?php endif; ?>
    
    <div class="section">
        <h2><?php echo t('payment_summary'); ?></h2>
        <table>
            <tr>
                <th><?php echo t('description'); ?></th>
                <th style="text-align: right;"><?php echo t('amount'); ?></th>
            </tr>
            <tr>
                <td><?php echo t('amount'); ?></td>
                <td style="text-align: right;"><?php echo formatPrice($invoice['amount']); ?></td>
            </tr>
            <?php if ($invoice['commission'] > 0): ?>
                <tr>
                    <td><?php echo t('commission'); ?></td>
                    <td style="text-align: right;"><?php echo formatPrice($invoice['commission']); ?></td>
                </tr>
            <?php endif; ?>
            <?php if ($invoice['fees'] > 0): ?>
                <tr>
                    <td><?php echo t('fees'); ?></td>
                    <td style="text-align: right;"><?php echo formatPrice($invoice['fees']); ?></td>
                </tr>
            <?php endif; ?>
            <tr class="total-row">
                <td><strong><?php echo t('total'); ?></strong></td>
                <td style="text-align: right;"><strong><?php echo formatPrice($invoice['total']); ?></strong></td>
            </tr>
        </table>
    </div>
    
    <div class="footer">
        <p><strong><?php echo t('payment_status'); ?>:</strong> <?php echo t('paid'); ?></p>
        <p><?php echo t('virtual_payment_note'); ?></p>
        <p style="margin-top: 20px;"><?php echo t('thank_you'); ?></p>
    </div>
</body>
</html>


