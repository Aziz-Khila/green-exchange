<?php
/**
 * Edit Listing Page
 */

$page_title = 'Edit Listing';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('/pages/login.php');
}

$user = getCurrentUser();
$post_id = $_GET['id'] ?? 0;

if (!$post_id) {
    redirect('/pages/profile.php');
}

$pdo = getDB();

// Get post details
$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->execute([$post_id]);
$post = $stmt->fetch();

if (!$post) {
    redirect('/pages/profile.php?error=not_found');
}

// Check if user owns this post or is admin
if ($post['user_id'] != $user['id'] && $user['role'] !== 'admin') {
    redirect('/pages/profile.php?error=unauthorized');
}

// Get existing images
$stmt = $pdo->prepare("SELECT * FROM post_images WHERE post_id = ? ORDER BY is_primary DESC");
$stmt->execute([$post_id]);
$existing_images = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <h1 class="page-title"><?php echo t('edit_listing'); ?></h1>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success"><?php echo t('listing_updated'); ?></div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error">
            <?php 
            $errors = [
                'required' => 'Please fill in all required fields.',
                'invalid_image' => 'Invalid image file.',
                'upload_failed' => 'Image upload failed.'
            ];
            echo escape($errors[$_GET['error']] ?? 'An error occurred.');
            ?>
        </div>
    <?php endif; ?>
    
    <form action="/api/update-post.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
        
        <!-- Waste Type Selection -->
        <div class="form-group">
            <label><?php echo t('waste_type'); ?></label>
            <div class="waste-type-selection">
                <div class="waste-type-btn <?php echo $post['category'] === 'plastics' ? 'active' : ''; ?>" data-type="plastics">
                    <div class="waste-type-icon waste-type-icon-plastics">
                        <i class="fa-solid fa-bottle-water"></i>
                    </div>
                    <span><?php echo t('category_plastics'); ?></span>
                </div>
                <div class="waste-type-btn <?php echo $post['category'] === 'electronics' ? 'active' : ''; ?>" data-type="electronics">
                    <div class="waste-type-icon waste-type-icon-electronics">
                        <i class="fa-solid fa-microchip"></i>
                    </div>
                    <span><?php echo t('category_electronics'); ?></span>
                </div>
                <div class="waste-type-btn <?php echo $post['category'] === 'cardboard' ? 'active' : ''; ?>" data-type="cardboard">
                    <div class="waste-type-icon waste-type-icon-cardboard">
                        <i class="fa-solid fa-box-open"></i>
                    </div>
                    <span><?php echo t('category_cardboard'); ?></span>
                </div>
                <div class="waste-type-btn <?php echo $post['category'] === 'glass' ? 'active' : ''; ?>" data-type="glass">
                    <div class="waste-type-icon waste-type-icon-glass">
                        <i class="fa-solid fa-wine-bottle"></i>
                    </div>
                    <span><?php echo t('category_glass'); ?></span>
                </div>
                <div class="waste-type-btn <?php echo $post['category'] === 'metal' ? 'active' : ''; ?>" data-type="metal">
                    <div class="waste-type-icon waste-type-icon-metal">
                        <i class="fa-solid fa-industry"></i>
                    </div>
                    <span><?php echo t('category_metal'); ?></span>
                </div>
            </div>
            <input type="hidden" name="category" id="category" value="<?php echo escape($post['category']); ?>" required>
        </div>
        
        <!-- Waste Type Detail -->
        <div class="form-group">
            <label for="waste_type"><?php echo t('waste_type_detail'); ?></label>
            <input type="text" id="waste_type" name="waste_type" value="<?php echo escape($post['waste_type']); ?>" placeholder="e.g., Bulk clear PET plastic bottles" required>
        </div>
        
        <!-- Title -->
        <div class="form-group">
            <label for="title">Title *</label>
            <input type="text" id="title" name="title" value="<?php echo escape($post['title']); ?>" required>
        </div>
        
        <!-- Description -->
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="4"><?php echo escape($post['description']); ?></textarea>
        </div>
        
        <!-- Unit -->
        <div class="form-group">
            <label for="unit">Unit *</label>
            <select id="unit" name="unit" required onchange="updateQuantityLabel()">
                <option value="kg" <?php echo $post['unit'] === 'kg' ? 'selected' : ''; ?>>Kilograms (kg)</option>
                <option value="tonnes" <?php echo $post['unit'] === 'tonnes' ? 'selected' : ''; ?>>Tonnes</option>
            </select>
        </div>
        
        <!-- Quantity -->
        <div class="form-group">
            <label for="quantity" id="quantity-label">Quantity (<?php echo escape($post['unit']); ?>) *</label>
            <input type="number" id="quantity" name="quantity" step="0.01" min="0" value="<?php echo escape($post['quantity']); ?>" required>
        </div>
        
        <!-- Price -->
        <div class="form-group">
            <label for="price_per_kg">Price per kg (TND) *</label>
            <input type="number" id="price_per_kg" name="price_per_kg" step="0.01" min="0" value="<?php echo escape($post['price_per_kg']); ?>" required>
        </div>
        
        <!-- Location and Address -->
        <div class="form-group">
            <label for="location">Location *</label>
            <input type="text" id="location" name="location" value="<?php echo escape($post['location']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="address">Full Address</label>
            <textarea id="address" name="address" rows="2"><?php echo escape($post['address']); ?></textarea>
        </div>
        
        <!-- Transport -->
        <div class="form-group">
            <label for="transport_by">Transport Assured By *</label>
            <select id="transport_by" name="transport_by" required>
                <option value="buyer" <?php echo $post['transport_by'] === 'buyer' ? 'selected' : ''; ?>>Buyer</option>
                <option value="seller" <?php echo $post['transport_by'] === 'seller' ? 'selected' : ''; ?>>Seller</option>
            </select>
        </div>
        
        <!-- Status -->
        <div class="form-group">
            <label for="status">Status *</label>
            <select id="status" name="status" required>
                <option value="available" <?php echo $post['status'] === 'available' ? 'selected' : ''; ?>><?php echo t('available'); ?></option>
                <option value="pending" <?php echo $post['status'] === 'pending' ? 'selected' : ''; ?>><?php echo t('pending'); ?></option>
                <option value="sold" <?php echo $post['status'] === 'sold' ? 'selected' : ''; ?>><?php echo t('sold'); ?></option>
                <option value="cancelled" <?php echo $post['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>
        </div>
        
        <!-- Existing Images -->
        <?php if (!empty($existing_images)): ?>
        <div class="form-group">
            <label>Current Photos</label>
            <div class="existing-images">
                <?php foreach ($existing_images as $image): ?>
                    <div class="existing-image-item">
                        <img src="<?php echo escape($image['image_path']); ?>" alt="Current image">
                        <label class="delete-image-checkbox">
                            <input type="checkbox" name="delete_images[]" value="<?php echo $image['id']; ?>">
                            <span>Delete</span>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Add New Photos -->
        <div class="form-group">
            <label>Add New Photos (Optional)</label>
            <div class="photo-upload">
                <div class="photo-thumbnail" onclick="document.getElementById('new_photo1').click()">
                    <input type="file" id="new_photo1" name="new_photos[]" accept="image/*" style="display: none;" onchange="previewNewImage(this, 0)">
                    <div class="add-photo" id="new_preview0">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        <span>Add Photo</span>
                    </div>
                </div>
                <div class="photo-thumbnail" onclick="document.getElementById('new_photo2').click()">
                    <input type="file" id="new_photo2" name="new_photos[]" accept="image/*" style="display: none;" onchange="previewNewImage(this, 1)">
                    <div class="add-photo" id="new_preview1">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        <span>Add Photo</span>
                    </div>
                </div>
                <div class="photo-thumbnail" onclick="document.getElementById('new_photo3').click()">
                    <input type="file" id="new_photo3" name="new_photos[]" accept="image/*" style="display: none;" onchange="previewNewImage(this, 2)">
                    <div class="add-photo" id="new_preview2">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        <span>Add Photo</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update Listing</button>
            <a href="/pages/profile.php" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>

<script>
// Waste type selection
document.querySelectorAll('.waste-type-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.waste-type-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        document.getElementById('category').value = this.dataset.type;
    });
});

// Update quantity label based on unit selection
function updateQuantityLabel() {
    const unit = document.getElementById('unit').value;
    const label = document.getElementById('quantity-label');
    if (unit === 'tonnes') {
        label.textContent = 'Quantity (tonnes) *';
    } else {
        label.textContent = '<?php echo t('quantity'); ?> (kg) *';
    }
}

// Initialize label on page load
updateQuantityLabel();

// Image preview for new images
function previewNewImage(input, index) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('new_preview' + index);
            preview.innerHTML = '<img src="' + e.target.result + '" style="width: 100%; height: 100%; object-fit: cover;">';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<style>
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.existing-images {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 16px;
}

.existing-image-item {
    position: relative;
    border: 2px solid var(--border-light);
    border-radius: var(--radius-md);
    overflow: hidden;
}

.existing-image-item img {
    width: 100%;
    height: 150px;
    object-fit: cover;
    display: block;
}

.delete-image-checkbox {
    position: absolute;
    bottom: 8px;
    right: 8px;
    background: rgba(0, 0, 0, 0.7);
    color: white;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
}

.delete-image-checkbox input {
    cursor: pointer;
}

.form-actions {
    display: flex;
    gap: 16px;
    margin-top: 32px;
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>



