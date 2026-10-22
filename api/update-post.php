<?php
/**
 * Update Post API
 */

require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('/pages/login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/pages/profile.php');
}

$user = getCurrentUser();
$post_id = intval($_POST['post_id'] ?? 0);

if (!$post_id) {
    redirect('/pages/profile.php?error=invalid');
}

$pdo = getDB();

// Check if post belongs to user or if user is admin
$stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
$stmt->execute([$post_id]);
$post = $stmt->fetch();

if (!$post) {
    redirect('/pages/profile.php?error=not_found');
}

if ($post['user_id'] != $user['id'] && $user['role'] !== 'admin') {
    redirect('/pages/profile.php?error=unauthorized');
}

// Get form data
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$category = trim($_POST['category'] ?? '');
$waste_type = trim($_POST['waste_type'] ?? '');
$location = trim($_POST['location'] ?? '');
$address = trim($_POST['address'] ?? '');
$quantity = floatval($_POST['quantity'] ?? 0);
$unit = $_POST['unit'] ?? 'kg';
$price_per_kg = floatval($_POST['price_per_kg'] ?? 0);
$transport_by = $_POST['transport_by'] ?? 'buyer';
$status = $_POST['status'] ?? 'available';

// Validation
if (empty($title) || empty($category) || empty($waste_type) || empty($location) || 
    $quantity <= 0 || $price_per_kg <= 0) {
    redirect("/pages/edit-post.php?id=$post_id&error=required");
}

// Update post
$stmt = $pdo->prepare("
    UPDATE posts 
    SET title = ?, description = ?, category = ?, waste_type = ?, location = ?, 
        address = ?, quantity = ?, unit = ?, price_per_kg = ?, transport_by = ?, 
        status = ?, updated_at = CURRENT_TIMESTAMP
    WHERE id = ?
");
$stmt->execute([
    $title, $description, $category, $waste_type, $location, $address,
    $quantity, $unit, $price_per_kg, $transport_by, $status, $post_id
]);

// Handle image deletions
if (!empty($_POST['delete_images'])) {
    foreach ($_POST['delete_images'] as $image_id) {
        // Get image path to delete file
        $stmt = $pdo->prepare("SELECT image_path FROM post_images WHERE id = ? AND post_id = ?");
        $stmt->execute([$image_id, $post_id]);
        $image = $stmt->fetch();
        
        if ($image) {
            $file_path = ROOT_PATH . $image['image_path'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            
            // Delete from database
            $stmt = $pdo->prepare("DELETE FROM post_images WHERE id = ?");
            $stmt->execute([$image_id]);
        }
    }
}

// Function to get default image based on category
function getDefaultImageForCategory($category) {
    $defaultImages = [
        'plastics' => '/assets/images/plastic.jpg',
        'electronics' => '/assets/images/electronics.png',
        'cardboard' => '/assets/images/cardboard.png',
        'glass' => '/assets/images/glass.jpg',
        'metal' => '/assets/images/metal.jpeg'
    ];
    return $defaultImages[strtolower($category)] ?? '/assets/images/plastic.jpg'; // Default fallback
}

// Handle new image uploads
$uploaded_new_images = false;
if (!empty($_FILES['new_photos']['name'][0])) {
    $upload_dir = UPLOAD_PATH;
    
    foreach ($_FILES['new_photos']['name'] as $key => $filename) {
        if (empty($filename)) continue;
        
        $tmp_name = $_FILES['new_photos']['tmp_name'][$key];
        $error = $_FILES['new_photos']['error'][$key];
        
        if ($error !== UPLOAD_ERR_OK) continue;
        
        // Validate file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $tmp_name);
        finfo_close($finfo);
        
        if (!in_array($mime_type, ALLOWED_IMAGE_TYPES)) {
            redirect("/pages/edit-post.php?id=$post_id&error=invalid_image");
        }
        
        // Generate unique filename
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $new_filename = uniqid('post_') . '_' . time() . '.' . $extension;
        $file_path = $upload_dir . '/' . $new_filename;
        
        // Move uploaded file
        if (move_uploaded_file($tmp_name, $file_path)) {
            $relative_path = UPLOAD_URL . '/' . $new_filename;
            
            // Check if there are any images left
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM post_images WHERE post_id = ?");
            $stmt->execute([$post_id]);
            $image_count = $stmt->fetchColumn();
            
            // First image should be primary if no images exist
            $is_primary = $image_count === 0 ? 1 : 0;
            
            // Insert image record
            $stmt = $pdo->prepare("
                INSERT INTO post_images (post_id, image_path, is_primary) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$post_id, $relative_path, $is_primary]);
            $uploaded_new_images = true;
        }
    }
}

// Check if post has any images left after deletions and new uploads
$stmt = $pdo->prepare("SELECT COUNT(*) FROM post_images WHERE post_id = ?");
$stmt->execute([$post_id]);
$remaining_images = $stmt->fetchColumn();

// If no images remain, use default image based on category
if ($remaining_images === 0) {
    $default_image = getDefaultImageForCategory($category);
    
    $stmt = $pdo->prepare("
        INSERT INTO post_images (post_id, image_path, is_primary) 
        VALUES (?, ?, 1)
    ");
    $stmt->execute([$post_id, $default_image]);
}

redirect("/pages/edit-post.php?id=$post_id&success=1");



