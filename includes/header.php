<?php
// Ensure config and language system are loaded
if (!function_exists('t')) {
    require_once __DIR__ . '/../config/config.php';
}

if (!isset($page_title)) {
    $page_title = 'Green Exchange';
}
$current_user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>" dir="<?php echo getCurrentLanguage() === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escape($page_title); ?> - Green Exchange</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <script src="/assets/js/main.js" defer></script>
</head>
<body<?php echo isset($admin_page) && $admin_page ? ' class="admin-body"' : ''; ?>>
    <header>
        <div class="header-top">
            <div class="container">
                <div class="header-left">
                    <?php 
                    $logo_link = '/';
                    if (isLoggedIn()) {
                        $logo_link = isAdmin() ? '/pages/admin.php' : '/pages/dashboard.php';
                    }
                    ?>
                    <a href="<?php echo $logo_link; ?>" class="logo-link">
                        <img src="/assets/images/logo.png" alt="Green Exchange" class="logo-image">
                        <h1 class="site-title">GREEN EXCHANGE</h1>
                    </a>
                </div>
                <div class="header-center">
                    <?php if (!isLoggedIn() && (basename($_SERVER['PHP_SELF']) == 'index.php' || $_SERVER['REQUEST_URI'] == '/')): ?>
                    <nav class="header-nav">
                        <a href="/#about"><?php echo t('about'); ?></a>
                        <a href="/#fees"><?php echo t('fees'); ?></a>
                    </nav>
                    <?php elseif (isLoggedIn()): ?>
                    <nav class="header-nav">
                        <?php if (!isAdmin()): ?>
                            <a href="/pages/dashboard.php"><?php echo t('dashboard'); ?></a>
                            <a href="/pages/post.php"><?php echo t('sell_waste'); ?></a>
                            <a href="/pages/browse.php"><?php echo t('buy_materials'); ?></a>
                        <?php else: ?>
                            <a href="/pages/admin.php"><?php echo t('dashboard'); ?></a>
                            <a href="/pages/admin-users.php"><?php echo t('manage_users'); ?></a>
                            <a href="/pages/admin-posts.php"><?php echo t('manage_listings'); ?></a>
                            <a href="/pages/admin-orders.php"><?php echo t('view_orders'); ?></a>
                            <a href="/pages/admin-settings.php"><?php echo t('settings'); ?></a>
                        <?php endif; ?>
                    </nav>
                    <?php endif; ?>
                </div>
                <div class="header-right">
                    <!-- Language Selector -->
                    <div class="language-selector">
                        <button class="lang-toggle" onclick="toggleLanguageMenu()">
                            <span class="lang-icon">🌐</span>
                        </button>
                        <div class="lang-menu" id="langMenu">
                            <?php foreach (AVAILABLE_LANGUAGES as $lang): ?>
                                <a href="/api/set-language.php?lang=<?php echo $lang; ?>" 
                                   class="lang-option <?php echo getCurrentLanguage() === $lang ? 'active' : ''; ?>">
                                    <?php echo getLanguageName($lang); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <?php if (isLoggedIn()): ?>
                        <?php if (!isAdmin()): ?>
                            <!-- Quick Actions -->
                            <a href="/pages/cart.php" class="header-icon-btn" title="<?php echo t('cart'); ?>">
                                🛒
                            </a>
                            
                            <!-- User Menu -->
                            <div class="user-menu">
                                <button class="user-menu-toggle" onclick="toggleUserMenu()">
                                    <div class="user-avatar">
                                        <?php echo strtoupper(substr($current_user['username'], 0, 2)); ?>
                                    </div>
                                    <span class="user-name"><?php echo escape($current_user['username']); ?></span>
                                    <span class="user-arrow">▼</span>
                                </button>
                                <div class="user-menu-dropdown" id="userMenu">
                                    <a href="/pages/profile.php" class="user-menu-item">
                                        <span class="menu-icon">👤</span>
                                        <span><?php echo t('profile'); ?></span>
                                    </a>
                                    <a href="/pages/my-listings.php" class="user-menu-item">
                                        <span class="menu-icon">📋</span>
                                        <span><?php echo t('my_listings'); ?></span>
                                    </a>
                                    <a href="/pages/seller-orders.php" class="user-menu-item">
                                        <span class="menu-icon">💰</span>
                                        <span><?php echo t('my_sales'); ?></span>
                                    </a>
                                    <a href="/pages/orders.php" class="user-menu-item">
                                        <span class="menu-icon">🛒</span>
                                        <span><?php echo t('my_orders'); ?></span>
                                    </a>
                                    <a href="/pages/wishlist.php" class="user-menu-item">
                                        <span class="menu-icon">❤️</span>
                                        <span><?php echo t('wishlist'); ?></span>
                                    </a>
                                    <a href="/pages/invoices.php" class="user-menu-item">
                                        <span class="menu-icon">📄</span>
                                        <span><?php echo t('invoices'); ?></span>
                                    </a>
                                    <a href="/api/logout.php" class="user-menu-item user-menu-logout">
                                        <span class="menu-icon">🚪</span>
                                        <span><?php echo t('logout'); ?></span>
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <a href="/api/logout.php" class="btn-header btn-small"><?php echo t('logout'); ?></a>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="/pages/login.php" class="header-link"><?php echo t('login'); ?></a>
                        <a href="/pages/register.php" class="btn-header"><?php echo t('register'); ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>
    <main>

