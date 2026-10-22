<?php
/**
 * Browse Listings Page
 */

$page_title = 'Browse Listings';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/header.php';

$pdo = getDB();

// Get filter parameters
$category = $_GET['category'] ?? '';
$location = $_GET['location'] ?? '';
$keyword = $_GET['keyword'] ?? '';

// Build query
$query = "SELECT p.*, u.username, u.city, 
          (SELECT image_path FROM post_images WHERE post_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
          FROM posts p 
          JOIN users u ON p.user_id = u.id 
          WHERE p.status = 'available'";

$params = [];

// Exclude current user's listings if logged in
if (isLoggedIn()) {
    $user = getCurrentUser();
    $query .= " AND p.user_id != ?";
    $params[] = $user['id'];
}

if (!empty($category)) {
    $query .= " AND p.category = ?";
    $params[] = $category;
}

if (!empty($location)) {
    $query .= " AND (p.location LIKE ? OR u.city LIKE ?)";
    $params[] = "%$location%";
    $params[] = "%$location%";
}

if (!empty($keyword)) {
    $query .= " AND (p.title LIKE ? OR p.description LIKE ? OR p.waste_type LIKE ?)";
    $keyword_param = "%$keyword%";
    $params[] = $keyword_param;
    $params[] = $keyword_param;
    $params[] = $keyword_param;
}

$query .= " ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$posts = $stmt->fetchAll();

// Get unique categories and locations for filters
$categories = $pdo->query("SELECT DISTINCT category FROM posts WHERE status = 'available' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
$locations = $pdo->query("SELECT DISTINCT location FROM posts WHERE status = 'available' ORDER BY location")->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="container">
    <h1><?php echo t('browse'); ?></h1>
    
    <!-- Filters -->
    <div class="filters">
        <form method="GET" action="">
            <input type="text" name="keyword" placeholder="<?php echo t('search'); ?>..." value="<?php echo escape($keyword); ?>">
            <select name="category">
                <option value=""><?php echo t('category'); ?></option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo escape($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                        <?php echo t('category_' . $cat); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="location">
                <option value=""><?php echo t('location'); ?></option>
                <?php foreach ($locations as $loc): ?>
                    <option value="<?php echo escape($loc); ?>" <?php echo $location === $loc ? 'selected' : ''; ?>>
                        <?php echo escape($loc); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary"><?php echo t('filter'); ?></button>
            <a href="/pages/browse.php" class="btn btn-outline"><?php echo t('clear'); ?></a>
        </form>
    </div>
    
    <!-- Listings Grid -->
    <div class="listings-grid">
        <?php if (empty($posts)): ?>
            <p><?php echo t('no_listings'); ?></p>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <div class="listing-card">
                    <?php if ($post['primary_image']): ?>
                        <img src="<?php echo escape($post['primary_image']); ?>" alt="<?php echo escape($post['title']); ?>">
                    <?php endif; ?>
                    <div class="listing-info">
                        <h3><a href="/pages/listing.php?id=<?php echo $post['id']; ?>"><?php echo escape($post['title']); ?></a></h3>
                        <p class="category"><?php echo t('category_' . $post['category']); ?> - <?php echo escape($post['waste_type']); ?></p>
                        <p class="location">📍 <?php echo escape($post['location']); ?></p>
                        <p class="quantity"><?php echo t('quantity'); ?>: <?php echo formatQuantity($post['quantity'], $post['unit']); ?></p>
                        <p class="price"><?php echo formatPrice($post['price_per_kg']); ?> / kg</p>
                        <p class="seller"><?php echo t('seller'); ?>: <?php echo escape($post['username']); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

