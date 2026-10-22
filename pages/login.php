<?php
/**
 * Login Page - Modern Minimal Design
 */

$page_title = 'Login';
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
                    <h1 class="auth-title"><?php echo t('welcome_back'); ?></h1>
                    <p class="auth-description"><?php echo t('login_description'); ?></p>
                    
                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-error">
                            <?php 
                            $errors = [
                                'invalid' => t('invalid_credentials'),
                                'required' => t('fill_all_fields')
                            ];
                            echo escape($errors[$_GET['error']] ?? t('error'));
                            ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success">
                            <?php
                            $messages = [
                                'registered' => t('registration_success'),
                                'logout' => t('logged_out')
                            ];
                            echo escape($messages[$_GET['success']] ?? '');
                            ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="/api/login.php" method="POST" class="modern-form">
                        <div class="input-group">
                            <label for="username"><?php echo t('email_or_username'); ?></label>
                            <input type="text" id="username" name="username" placeholder="<?php echo t('enter_email_username'); ?>" required autofocus>
                        </div>
                        
                        <div class="input-group">
                            <label for="password"><?php echo t('password'); ?></label>
                            <input type="password" id="password" name="password" placeholder="<?php echo t('enter_password'); ?>" required>
                        </div>
                        
                        <button type="submit" class="submit-btn"><?php echo t('sign_in'); ?></button>
                    </form>
                    
                    <p class="auth-footer-text">
                        <?php echo t('dont_have_account'); ?> <a href="/pages/register.php" class="auth-link"><?php echo t('create_account'); ?></a>
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
