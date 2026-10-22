<?php
/**
 * Landing Page - Complete
 */

$page_title = 'Home';
$is_landing_page = true; // Flag for landing page footer
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';

$pdo = getDB();

// Get stats
$total_listings = $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'available'")->fetchColumn();
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="hero-content">
        <div class="hero-text">
            <h1 class="hero-title"><?php echo t('transform_waste'); ?> <span class="highlight"><?php echo t('value'); ?></span></h1>
            <p class="hero-subtitle"><?php echo t('join_tunisia'); ?></p>
            <div class="hero-cta">
                <?php if (!isLoggedIn()): ?>
                    <a href="/pages/register.php" class="btn btn-primary btn-large"><?php echo t('get_started_free'); ?></a>
                    <a href="/pages/login.php" class="btn btn-outline btn-large"><?php echo t('sign_in'); ?></a>
                <?php else: ?>
                    <a href="/pages/post.php" class="btn btn-primary btn-large"><?php echo t('post_listing'); ?></a>
                    <a href="/pages/browse.php" class="btn btn-outline btn-large"><?php echo t('browse_materials'); ?></a>
                <?php endif; ?>
            </div>
            <div class="hero-stats">
                <div class="stat-item">
                    <div class="stat-number"><?php echo number_format($total_listings); ?>+</div>
                    <div class="stat-label"><?php echo t('active_listings'); ?></div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo number_format($total_users); ?>+</div>
                    <div class="stat-label"><?php echo t('active_traders'); ?></div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">100%</div>
                    <div class="stat-label"><?php echo t('eco_friendly'); ?></div>
                </div>
            </div>
        </div>
        <div class="hero-visual">
            <div class="hero-image-container">
                <div class="floating-card card-1">
                    <div class="card-icon">♻️</div>
                    <div class="card-text"><?php echo t('recycle'); ?></div>
                </div>
                <div class="floating-card card-2 float-animation">
                    <div class="card-icon">💰</div>
                    <div class="card-text"><?php echo t('earn'); ?></div>
                </div>
                <div class="floating-card card-3">
                    <div class="card-icon">🌱</div>
                    <div class="card-text"><?php echo t('grow'); ?></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- About Us Section -->
<section class="about-section" id="about">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title"><?php echo t('about_green_exchange'); ?></h2>
            <p class="section-subtitle"><?php echo t('building_sustainable_future'); ?></p>
        </div>
        <div class="about-content">
            <div class="about-text">
                <p class="about-description">
                    <?php echo t('about_description_1'); ?>
                </p>
                <p class="about-description">
                    <?php echo t('about_description_2'); ?>
                </p>
                <div class="about-features">
                    <div class="about-feature-item">
                        <div class="about-feature-icon">✓</div>
                        <div class="about-feature-text"><?php echo t('secure_transactions'); ?></div>
                    </div>
                    <div class="about-feature-item">
                        <div class="about-feature-icon">✓</div>
                        <div class="about-feature-text"><?php echo t('easy_to_use'); ?></div>
                    </div>
                    <div class="about-feature-item">
                        <div class="about-feature-icon">✓</div>
                        <div class="about-feature-text"><?php echo t('supporting_green_economy'); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Fees Section -->
<section class="fees-section" id="fees">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title"><?php echo t('pricing_fees'); ?></h2>
            <p class="section-subtitle"><?php echo t('transparent_pricing'); ?></p>
        </div>
        <div class="fees-grid">
            <div class="fee-card">
                <div class="fee-icon">💳</div>
                <h3><?php echo t('account_creation'); ?></h3>
                <div class="fee-amount"><?php echo formatPrice(ACCOUNT_CREATION_FEE); ?></div>
                <p class="fee-description"><?php echo t('account_creation_description'); ?></p>
            </div>
            <div class="fee-card">
                <div class="fee-icon">📊</div>
                <h3><?php echo t('transaction_commission'); ?></h3>
                <div class="fee-amount"><?php echo (BUYER_COMMISSION_RATE * 100); ?>% <?php echo t('buyer'); ?> / <?php echo (SELLER_COMMISSION_RATE * 100); ?>% <?php echo t('seller'); ?></div>
                <p class="fee-description"><?php echo t('transaction_commission_description'); ?></p>
            </div>
            <div class="fee-card">
                <div class="fee-icon">🆓</div>
                <h3><?php echo t('listing_fee'); ?></h3>
                <div class="fee-amount"><?php echo t('free'); ?></div>
                <p class="fee-description"><?php echo t('listing_fee_description'); ?></p>
            </div>
        </div>
        <div class="fees-note">
            <p><?php echo t('fees_note'); ?></p>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title"><?php echo t('why_choose'); ?></h2>
            <p class="section-subtitle"><?php echo t('modern_platform'); ?></p>
        </div>
        <div class="features-grid">
            <div class="feature-card-modern">
                <div class="feature-icon-modern">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                    </svg>
                </div>
                <h3><?php echo t('circular_economy'); ?></h3>
                <p><?php echo t('circular_economy_desc'); ?></p>
            </div>
            <div class="feature-card-modern">
                <div class="feature-icon-modern">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                        <line x1="1" y1="10" x2="23" y2="10"/>
                    </svg>
                </div>
                <h3><?php echo t('secure_platform'); ?></h3>
                <p><?php echo t('secure_platform_desc'); ?></p>
            </div>
            <div class="feature-card-modern">
                <div class="feature-icon-modern">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                </div>
                <h3><?php echo t('local_connections'); ?></h3>
                <p><?php echo t('local_connections_desc'); ?></p>
            </div>
            <div class="feature-card-modern">
                <div class="feature-icon-modern">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                    </svg>
                </div>
                <h3><?php echo t('easy_communication'); ?></h3>
                <p><?php echo t('easy_communication_desc'); ?></p>
            </div>
            <div class="feature-card-modern">
                <div class="feature-icon-modern">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                    </svg>
                </div>
                <h3><?php echo t('fair_pricing'); ?></h3>
                <p><?php echo t('fair_pricing_desc'); ?></p>
            </div>
            <div class="feature-card-modern">
                <div class="feature-icon-modern">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </div>
                <h3><?php echo t('trusted_community'); ?></h3>
                <p><?php echo t('trusted_community_desc'); ?></p>
            </div>
        </div>
    </div>
</section>


<?php require_once __DIR__ . '/includes/footer-landing.php'; ?>
