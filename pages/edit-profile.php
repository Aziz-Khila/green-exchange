<?php
/**
 * Edit Profile Page
 */

$page_title = 'Edit Profile';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('/pages/login.php');
}

$user = getCurrentUser();
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <h1 class="page-title"><?php echo t('edit_profile'); ?></h1>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success"><?php echo t('profile_updated'); ?></div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error">
            <?php 
            $errors = [
                'email_exists' => t('email_exists'),
                'invalid_email' => t('invalid_email'),
                'password_mismatch' => t('password_mismatch'),
                'weak_password' => t('weak_password')
            ];
            echo escape($errors[$_GET['error']] ?? t('error'));
            ?>
        </div>
    <?php endif; ?>
    
    <form action="/api/update-profile.php" method="POST" class="profile-form">
        <div class="form-section">
            <h2><?php echo t('basic_information'); ?></h2>
            <div class="form-row">
                <div class="form-group">
                    <label for="username"><?php echo t('username'); ?></label>
                    <input type="text" id="username" value="<?php echo escape($user['username']); ?>" disabled>
                    <small><?php echo t('username_cannot_change'); ?></small>
                </div>
                
                <div class="form-group">
                    <label for="email"><?php echo t('email'); ?> *</label>
                    <input type="email" id="email" name="email" value="<?php echo escape($user['email']); ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="phone"><?php echo t('phone_number'); ?></label>
                    <input type="text" id="phone" name="phone" value="<?php echo escape($user['phone'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="city"><?php echo t('city'); ?></label>
                    <input type="text" id="city" name="city" value="<?php echo escape($user['city'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="address"><?php echo t('address'); ?></label>
                <textarea id="address" name="address" rows="3"><?php echo escape($user['address'] ?? ''); ?></textarea>
            </div>
        </div>
        
        <div class="form-section">
            <h2><?php echo t('change_password'); ?></h2>
            <p class="section-description"><?php echo t('leave_blank_password'); ?></p>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="new_password"><?php echo t('new_password'); ?></label>
                    <input type="password" id="new_password" name="new_password" minlength="6">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password"><?php echo t('confirm_new_password'); ?></label>
                    <input type="password" id="confirm_password" name="confirm_password">
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?php echo t('save_changes'); ?></button>
            <a href="/pages/profile.php" class="btn btn-outline"><?php echo t('cancel'); ?></a>
        </div>
    </form>
</div>

<style>
.profile-form {
    max-width: 800px;
    margin: 32px auto;
    padding: 0;
    box-shadow: none;
}

.form-section {
    background: var(--white);
    border: 1px solid var(--border-light);
    border-radius: 16px;
    padding: 32px;
    margin-bottom: 24px;
}

.form-section h2 {
    font-size: 1.5rem;
    margin-bottom: 8px;
}

.section-description {
    color: var(--text-gray);
    font-size: 0.9rem;
    margin-bottom: 24px;
}

.profile-form .form-actions {
    display: flex;
    gap: 16px;
    justify-content: flex-end;
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

