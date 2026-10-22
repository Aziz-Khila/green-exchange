<?php
/**
 * Post Listing Page - Sell Waste
 */

$page_title = 'Sell Waste';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('/pages/login.php');
}

$user = getCurrentUser();
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <h1 class="page-title"><?php echo t('list_your_recyclables'); ?></h1>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error">
            <?php 
            $errors = [
                'required' => t('fill_all_fields'),
                'invalid_image' => t('invalid_input')
            ];
            echo escape($errors[$_GET['error']] ?? t('error'));
            ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <?php echo t('listing_posted'); ?>
        </div>
    <?php endif; ?>
    
    <form action="/api/post.php" method="POST" enctype="multipart/form-data">
        <!-- Waste Type Selection -->
        <div class="form-group">
            <label><?php echo t('waste_type'); ?></label>
            <div class="waste-type-selection">
                <div class="waste-type-btn" data-type="plastics">
                    <div class="waste-type-icon waste-type-icon-plastics">
                        <i class="fa-solid fa-bottle-water"></i>
                    </div>
                    <span><?php echo t('category_plastics'); ?></span>
                </div>
                <div class="waste-type-btn" data-type="electronics">
                    <div class="waste-type-icon waste-type-icon-electronics">
                        <i class="fa-solid fa-microchip"></i>
                    </div>
                    <span><?php echo t('category_electronics'); ?></span>
                </div>
                <div class="waste-type-btn" data-type="cardboard">
                    <div class="waste-type-icon waste-type-icon-cardboard">
                        <i class="fa-solid fa-box-open"></i>
                    </div>
                    <span><?php echo t('category_cardboard'); ?></span>
                </div>
                <div class="waste-type-btn" data-type="glass">
                    <div class="waste-type-icon waste-type-icon-glass">
                        <i class="fa-solid fa-wine-bottle"></i>
                    </div>
                    <span><?php echo t('category_glass'); ?></span>
                </div>
                <div class="waste-type-btn" data-type="metal">
                    <div class="waste-type-icon waste-type-icon-metal">
                        <i class="fa-solid fa-industry"></i>
                    </div>
                    <span><?php echo t('category_metal'); ?></span>
                </div>
            </div>
            <input type="hidden" name="category" id="category" value="plastics" required>
        </div>
        
        <!-- Waste Type Detail -->
        <div class="form-group">
            <label for="waste_type"><?php echo t('waste_type_detail'); ?></label>
            <input type="text" id="waste_type" name="waste_type" placeholder="e.g., Bulk clear PET plastic bottles, sorted, ready for collection" required>
        </div>
        
        <!-- Title -->
        <div class="form-group">
            <label for="title"><?php echo t('title'); ?> *</label>
            <input type="text" id="title" name="title" required>
        </div>
        
        <!-- Description -->
        <div class="form-group">
            <label for="description"><?php echo t('description'); ?></label>
            <textarea id="description" name="description" rows="4"></textarea>
        </div>
        
        <!-- Unit -->
        <div class="form-group">
            <label for="unit"><?php echo t('unit'); ?> *</label>
            <select id="unit" name="unit" required onchange="updateQuantityLabel()">
                <option value="kg"><?php echo t('kg'); ?></option>
                <option value="tonnes"><?php echo t('tonnes'); ?></option>
            </select>
        </div>
        
        <!-- Quantity -->
        <div class="form-group">
            <label for="quantity" id="quantity-label"><?php echo t('quantity'); ?> (kg) *</label>
            <input type="number" id="quantity" name="quantity" step="0.01" min="0" required>
        </div>
        
        <!-- Price per kg -->
        <div class="form-group">
            <label for="price_per_kg"><?php echo t('price_per_kg'); ?> *</label>
            <input type="number" id="price_per_kg" name="price_per_kg" step="0.01" min="0" required>
        </div>
        
        <!-- Location -->
        <div class="form-group">
            <label for="location"><?php echo t('location'); ?> *</label>
            <input type="text" id="location" name="location" required>
        </div>
        
        <!-- Address -->
        <div class="form-group">
            <label for="address"><?php echo t('full_address'); ?></label>
            <textarea id="address" name="address" rows="2"></textarea>
        </div>
        
        <!-- Transport -->
        <div class="form-group">
            <label for="transport_by"><?php echo t('transport_by'); ?> *</label>
            <select id="transport_by" name="transport_by" required>
                <option value="buyer"><?php echo t('buyer'); ?></option>
                <option value="seller"><?php echo t('seller'); ?></option>
            </select>
        </div>
        
        <!-- Photo Upload -->
        <div class="form-group">
            <label><?php echo t('upload_photos'); ?> <?php echo t('optional_default_image'); ?></label>
            <div class="photo-upload">
                <div class="photo-thumbnail" onclick="document.getElementById('photo1').click()">
                    <input type="file" id="photo1" name="photos[]" accept="image/*" style="display: none;" onchange="previewImage(this, 0)">
                    <div class="add-photo" id="preview0">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        <span>Add Photo</span>
                    </div>
                </div>
                <div class="photo-thumbnail" onclick="document.getElementById('photo2').click()">
                    <input type="file" id="photo2" name="photos[]" accept="image/*" style="display: none;" onchange="previewImage(this, 1)">
                    <div class="add-photo" id="preview1">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        <span>Add Photo</span>
                    </div>
                </div>
                <div class="photo-thumbnail" onclick="document.getElementById('photo3').click()">
                    <input type="file" id="photo3" name="photos[]" accept="image/*" style="display: none;" onchange="previewImage(this, 2)">
                    <div class="add-photo" id="preview2">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        <span>Add Photo</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Contact Details (pre-filled from user profile) -->
        <div class="form-group">
            <label><?php echo t('contact_details'); ?></label>
            <div style="background: #f5f5f5; padding: 15px; border-radius: 6px;">
                <p><strong><?php echo t('username'); ?>:</strong> <?php echo escape($user['username']); ?></p>
                <p><strong><?php echo t('email'); ?>:</strong> <?php echo escape($user['email']); ?></p>
                <p><strong><?php echo t('phone'); ?>:</strong> <?php echo escape($user['phone'] ?? t('not_provided')); ?></p>
                <small><a href="/pages/profile.php"><?php echo t('update_contact'); ?></a></small>
            </div>
        </div>
        
        <div class="form-group">
            <button type="submit" id="submitBtn" class="btn btn-primary btn-block" disabled>
                <span id="submitText"><?php echo t('post_listing'); ?></span>
                <span id="uploadLoader" style="display: none;">
                    <i class="fa-solid fa-spinner fa-spin"></i> <?php echo t('uploading_images'); ?>...
                </span>
            </button>
        </div>
    </form>
</div>

<style>
.upload-loader {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-left: 8px;
}

.upload-loader i {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.photo-thumbnail {
    position: relative;
}

.upload-progress {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    border-radius: 8px;
    font-size: 0.9rem;
}

.upload-progress i {
    margin-right: 8px;
}
</style>

<script>
// Waste type selection
document.querySelectorAll('.waste-type-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.waste-type-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        document.getElementById('category').value = this.dataset.type;
    });
});

// Set default active
document.querySelector('.waste-type-btn[data-type="plastics"]').classList.add('active');

// Update quantity label based on unit selection
function updateQuantityLabel() {
    const unit = document.getElementById('unit').value;
    const label = document.getElementById('quantity-label');
    const quantityText = '<?php echo t('quantity'); ?>';
    if (unit === 'tonnes') {
        label.textContent = quantityText + ' (tonnes) *';
    } else {
        label.textContent = quantityText + ' (kg) *';
    }
}

// Initialize label on page load
updateQuantityLabel();

// Track uploaded images
let uploadedImages = {
    0: false,
    1: false,
    2: false
};

let uploadingCount = 0;

// Check if any image is being uploaded or uploaded
function checkUploadStatus() {
    const hasUploaded = Object.values(uploadedImages).some(v => v === true);
    const submitBtn = document.getElementById('submitBtn');
    
    if (uploadingCount > 0) {
        // Show loader and disable button while uploading
        document.getElementById('submitText').style.display = 'none';
        document.getElementById('uploadLoader').style.display = 'inline';
        submitBtn.disabled = true;
    } else if (hasUploaded) {
        // Enable button if at least one image is uploaded
        document.getElementById('submitText').style.display = 'inline';
        document.getElementById('uploadLoader').style.display = 'none';
        submitBtn.disabled = false;
    } else {
        // No images uploaded - button enabled (default image will be used)
        document.getElementById('submitText').style.display = 'inline';
        document.getElementById('uploadLoader').style.display = 'none';
        submitBtn.disabled = false;
    }
}

// Image preview and upload tracking
function previewImage(input, index) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const preview = document.getElementById('preview' + index);
        const thumbnail = preview.closest('.photo-thumbnail');
        
        // Show upload progress
        uploadingCount++;
        checkUploadStatus();
        
        // Create progress overlay
        let progressOverlay = thumbnail.querySelector('.upload-progress');
        if (!progressOverlay) {
            progressOverlay = document.createElement('div');
            progressOverlay.className = 'upload-progress';
            thumbnail.appendChild(progressOverlay);
        }
        progressOverlay.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Uploading...';
        progressOverlay.style.display = 'flex';
        
        // Simulate upload (in real scenario, this would be handled by the form submission)
        // For now, we'll just show the preview after a short delay
        const reader = new FileReader();
        reader.onload = function(e) {
            // Hide progress and show preview
            setTimeout(() => {
                progressOverlay.style.display = 'none';
                preview.innerHTML = '<img src="' + e.target.result + '" style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;">';
                uploadedImages[index] = true;
                uploadingCount--;
                checkUploadStatus();
            }, 500); // Simulate upload delay
        };
        reader.readAsDataURL(file);
    } else {
        uploadedImages[index] = false;
        checkUploadStatus();
    }
}

// Initialize upload status check
checkUploadStatus();

// Handle form submission - show loader during actual upload
document.querySelector('form').addEventListener('submit', function(e) {
    const submitBtn = document.getElementById('submitBtn');
    const hasUploaded = Object.values(uploadedImages).some(v => v === true);
    
    if (hasUploaded) {
        // Show loader during form submission
        document.getElementById('submitText').style.display = 'none';
        document.getElementById('uploadLoader').style.display = 'inline';
        submitBtn.disabled = true;
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

