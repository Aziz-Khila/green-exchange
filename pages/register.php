<?php
/**
 * Register Page - Modern Minimal Design
 */

$page_title = 'Register';
require_once __DIR__ . '/../includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('/');
}
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>" dir="<?php echo getCurrentLanguage() === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escape($page_title); ?> - Green Exchange</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/auth.css">
</head>
<body class="auth-body">
    <div class="auth-wrapper">
        <div class="auth-card">
            <!-- Left Side - Form -->
            <div class="auth-form-panel">
                <div class="auth-brand">
                    <a href="/" class="brand-link">
                        <img src="/assets/images/logo.png" alt="Green Exchange" class="brand-logo">
                        <span class="brand-text">Green<span class="brand-highlight">Exchange</span></span>
                    </a>
                </div>
                
                <div class="auth-content">
                    <h1 class="auth-title"><?php echo t('create_account'); ?></h1>
                    <p class="auth-description"><?php echo t('register_description'); ?></p>
                    
                    <!-- Fee Notice -->
                    <div class="fee-banner">
                        <span class="fee-icon">💳</span>
                        <div class="fee-text">
                            <strong><?php echo t('account_fee'); ?>: <?php echo formatPrice(ACCOUNT_CREATION_FEE); ?></strong>
                            <span><?php echo t('one_time_payment'); ?></span>
                        </div>
                    </div>
                    
                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-error">
                            <?php 
                            $errors = [
                                'username_exists' => t('username_exists'),
                                'email_exists' => t('email_exists'),
                                'password_mismatch' => t('password_mismatch'),
                                'required' => t('fill_all_fields'),
                                'invalid_email' => t('invalid_email'),
                                'weak_password' => t('weak_password')
                            ];
                            echo escape($errors[$_GET['error']] ?? t('error'));
                            ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="/api/register.php" method="POST" class="modern-form">
                        <div class="form-scroll-area">
                            <div class="input-group">
                                <label for="username"><?php echo t('username'); ?></label>
                                <input type="text" id="username" name="username" placeholder="<?php echo t('choose_username'); ?>" required autofocus>
                            </div>
                            
                            <div class="input-group">
                                <label for="email"><?php echo t('email'); ?></label>
                                <input type="email" id="email" name="email" placeholder="<?php echo t('your_email'); ?>" required>
                            </div>
                            
                            <div class="input-group">
                                <label for="phone"><?php echo t('phone_number'); ?></label>
                                <input type="text" id="phone" name="phone" placeholder="+216 XX XXX XXX">
                            </div>
                            
                            <div class="input-group">
                                <label for="city"><?php echo t('city'); ?></label>
                                <input type="text" id="city" name="city" placeholder="<?php echo t('your_city'); ?>">
                            </div>
                            
                            <div class="input-group">
                                <label for="address"><?php echo t('address'); ?> (<?php echo t('optional'); ?>)</label>
                                <input type="text" id="address" name="address" placeholder="<?php echo t('your_address'); ?>">
                            </div>
                            
                            <div class="input-group">
                                <label for="password"><?php echo t('password'); ?></label>
                                <input type="password" id="password" name="password" placeholder="<?php echo t('min_password'); ?>" required minlength="6">
                            </div>
                            
                            <div class="input-group">
                                <label for="password_confirm"><?php echo t('password_confirm'); ?></label>
                                <input type="password" id="password_confirm" name="password_confirm" placeholder="<?php echo t('re_enter_password'); ?>" required>
                            </div>
                        </div>
                        
                        <button type="submit" class="submit-btn"><?php echo t('create_account'); ?></button>
                    </form>
                    
                    <p class="auth-footer-text">
                        <?php echo t('already_have_account'); ?> <a href="/pages/login.php" class="auth-link"><?php echo t('sign_in'); ?></a>
                    </p>
                </div>
            </div>
            
            <!-- Right Side - Visual -->
            <div class="auth-visual-panel">
                <div class="visual-overlay">
                    <h2><?php echo t('transform_waste_right'); ?></h2>
                    <p><?php echo t('join_tunisia'); ?></p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
