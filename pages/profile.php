<?php
/**
 * User Profile Page
 */

$page_title = 'My Profile';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('/pages/login.php');
}

$user = getCurrentUser();
require_once __DIR__ . '/../includes/header.php';

$pdo = getDB();

?>

<div class="container">
    <?php if (isset($_GET['success']) && $_GET['success'] === 'deleted'): ?>
        <div class="alert alert-success"><?php echo t('listing_deleted'); ?></div>
    <?php endif; ?>
    
    <div class="profile-header">
        <div class="profile-info">
            <div class="profile-avatar">
                <?php echo strtoupper(substr($user['username'], 0, 2)); ?>
            </div>
            <div class="profile-details">
                <h1><?php echo escape($user['username']); ?></h1>
                <p class="profile-email"><?php echo escape($user['email']); ?></p>
            </div>
        </div>
        <div class="profile-actions">
            <a href="/pages/edit-profile.php" class="btn btn-outline"><?php echo t('edit_profile'); ?></a>
        </div>
    </div>
    
    <!-- User Information -->
    <div class="profile-section">
        <h2><?php echo t('contact_information'); ?></h2>
        <div class="info-grid">
            <div class="info-item">
                <label><?php echo t('phone'); ?>:</label>
                <span><?php echo escape($user['phone'] ?? t('not_provided')); ?></span>
            </div>
            <div class="info-item">
                <label><?php echo t('city'); ?>:</label>
                <span><?php echo escape($user['city'] ?? t('not_provided')); ?></span>
            </div>
            <div class="info-item">
                <label><?php echo t('address'); ?>:</label>
                <span><?php echo escape($user['address'] ?? t('not_provided')); ?></span>
            </div>
            <div class="info-item">
                <label><?php echo t('member_since'); ?>:</label>
                <span><?php echo date('F Y', strtotime($user['created_at'])); ?></span>
            </div>
        </div>
    </div>
</div>

<style>
.profile-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 40px;
    padding: 32px;
    background: var(--white);
    border-radius: 16px;
    border: 1px solid var(--border-light);
}

.profile-info {
    display: flex;
    gap: 24px;
    align-items: center;
}

.profile-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-green) 0%, var(--primary-green-dark) 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    font-weight: 700;
}

.profile-details h1 {
    font-size: 2rem;
    margin-bottom: 8px;
}

.profile-email {
    color: var(--text-gray);
    margin-bottom: 12px;
}

.status-verified {
    background: #d1fae5;
    color: #065f46;
}

.profile-section {
    background: var(--white);
    padding: 32px;
    border-radius: 16px;
    border: 1px solid var(--border-light);
    margin-bottom: 32px;
}

.profile-section h2 {
    font-size: 1.5rem;
    margin-bottom: 24px;
    color: var(--text-dark);
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 24px;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.info-item label {
    font-weight: 600;
    color: var(--text-gray);
    font-size: 0.9rem;
}

.info-item span {
    color: var(--text-dark);
    font-size: 1rem;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
}

.empty-state p {
    color: var(--text-gray);
    font-size: 1.1rem;
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

.btn-edit {
    background: var(--primary-green);
    color: white;
}

.btn-edit:hover {
    background: var(--primary-green-dark);
}

.owner-actions {
    display: flex;
    gap: 12px;
    margin-top: 20px;
}

</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

