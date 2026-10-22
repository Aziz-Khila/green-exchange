<?php
/**
 * Post Listing API Endpoint
 */

require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('/pages/login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/pages/post.php?error=required');
}

$user = getCurrentUser();

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

// Validation
if (empty($title) || empty($category) || empty($waste_type) || empty($location) || 
    $quantity <= 0 || $price_per_kg <= 0) {
    redirect('/pages/post.php?error=required');
}

$pdo = getDB();

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

// Insert post
$stmt = $pdo->prepare("
    INSERT INTO posts (user_id, title, description, category, waste_type, location, address, 
                       quantity, unit, price_per_kg, transport_by, status) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'available')
");
$stmt->execute([$user['id'], $title, $description, $category, $waste_type, $location, 
                $address, $quantity, $unit, $price_per_kg, $transport_by]);

$post_id = $pdo->lastInsertId();

// Handle image uploads
$upload_dir = UPLOAD_PATH;
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$uploaded_images = [];
$primary_set = false;
$has_uploaded_images = !empty($_FILES['photos']['name'][0]);

// Handle image uploads if provided
if ($has_uploaded_images) {
    foreach ($_FILES['photos']['name'] as $key => $filename) {
        if (empty($filename)) continue;
        
        $tmp_name = $_FILES['photos']['tmp_name'][$key];
        $error = $_FILES['photos']['error'][$key];
        
        if ($error !== UPLOAD_ERR_OK) {
            continue;
        }
        
        // Validate file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $tmp_name);
        finfo_close($finfo);
        
        if (!in_array($mime_type, ALLOWED_IMAGE_TYPES)) {
            redirect('/pages/post.php?error=invalid_image');
        }
        
        // Generate unique filename
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $new_filename = uniqid('post_') . '_' . time() . '.' . $extension;
        $file_path = $upload_dir . '/' . $new_filename;
        
        // Move uploaded file
        if (move_uploaded_file($tmp_name, $file_path)) {
            $relative_path = UPLOAD_URL . '/' . $new_filename;
            
            // Insert image record
            $is_primary = $primary_set ? 0 : 1;
            $primary_set = true;
            
            $stmt = $pdo->prepare("
                INSERT INTO post_images (post_id, image_path, is_primary) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$post_id, $relative_path, $is_primary]);
            
            $uploaded_images[] = $relative_path;
        }
    }
}

// If no images were uploaded, use default image based on category
if (empty($uploaded_images)) {
    $default_image = getDefaultImageForCategory($category);
    
    $stmt = $pdo->prepare("
        INSERT INTO post_images (post_id, image_path, is_primary) 
        VALUES (?, ?, 1)
    ");
    $stmt->execute([$post_id, $default_image]);
}

// Create notification for admin
$stmt = $pdo->prepare("
    INSERT INTO notifications (user_id, type, message, related_id) 
    SELECT id, 'new_listing', ?, ? FROM users WHERE role = 'admin'
");
$stmt->execute(["New listing posted: $title", $post_id]);

redirect('/pages/post.php?success=1');

