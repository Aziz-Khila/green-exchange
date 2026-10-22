<?php
/**
 * Admin - Platform Settings
 */

$page_title = 'Platform Settings';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('/');
}

$admin_page = true;
require_once __DIR__ . '/../includes/header.php';

?>

<div class="container">
    <div class="admin-header">
        <div>
            <h1 class="page-title">Platform Settings</h1>
            <p class="admin-subtitle">Configure platform commission settings</p>
        </div>
        <a href="/pages/admin.php" class="btn btn-outline">
            <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
    
    <!-- Commission Settings -->
    <div class="admin-section">
        <h2 class="section-title">Commission Settings</h2>
        <div class="settings-card">
            <form class="settings-form" id="commissionForm">
                <div class="settings-item editable">
                    <div class="settings-label">
                        <i class="fa-solid fa-percent"></i>
                        <div>
                            <h4>Buyer Commission Rate</h4>
                            <p>Percentage added to buyer's total</p>
                        </div>
                    </div>
                    <div class="settings-value">
                        <div class="value-display">
                            <span class="value-number" id="buyerCommissionDisplay"><?php echo number_format($buyer_rate, 2); ?></span>
                            <span class="value-unit">%</span>
                        </div>
                        <input type="number" 
                               step="0.01" 
                               min="0" 
                               max="100" 
                               value="<?php echo $buyer_rate; ?>" 
                               class="settings-input"
                               id="buyerCommission"
                               style="display: none;"
                               readonly>
                        <button type="button" class="edit-btn" onclick="enableEdit('buyerCommission', this)">
                            <i class="fa-solid fa-pencil"></i> Edit
                        </button>
                    </div>
                </div>
                
                <div class="settings-item editable">
                    <div class="settings-label">
                        <i class="fa-solid fa-percent"></i>
                        <div>
                            <h4>Seller Commission Rate</h4>
                            <p>Percentage deducted from seller's revenue</p>
                        </div>
                    </div>
                    <div class="settings-value">
                        <div class="value-display">
                            <span class="value-number" id="sellerCommissionDisplay"><?php echo number_format($seller_rate, 2); ?></span>
                            <span class="value-unit">%</span>
                        </div>
                        <input type="number" 
                               step="0.01" 
                               min="0" 
                               max="100" 
                               value="<?php echo $seller_rate; ?>" 
                               class="settings-input"
                               id="sellerCommission"
                               style="display: none;"
                               readonly>
                        <button type="button" class="edit-btn" onclick="enableEdit('sellerCommission', this)">
                            <i class="fa-solid fa-pencil"></i> Edit
                        </button>
                    </div>
                </div>
                
                <div class="settings-item editable">
                    <div class="settings-label">
                        <i class="fa-solid fa-dollar-sign"></i>
                        <div>
                            <h4>Account Creation Fee</h4>
                            <p>One-time fee for new account registration</p>
                        </div>
                    </div>
                    <div class="settings-value">
                        <div class="value-display">
                            <span class="value-number" id="accountFeeDisplay"><?php echo number_format($account_fee, 2); ?></span>
                            <span class="value-unit">TND</span>
                        </div>
                        <input type="number" 
                               step="0.01" 
                               min="0" 
                               value="<?php echo $account_fee; ?>" 
                               class="settings-input"
                               id="accountFee"
                               style="display: none;"
                               readonly>
                        <button type="button" class="edit-btn" onclick="enableEdit('accountFee', this)">
                            <i class="fa-solid fa-pencil"></i> Edit
                        </button>
                    </div>
                </div>
                
                <div class="settings-form-actions">
                    <button type="button" class="btn btn-outline" onclick="resetForm()">Cancel</button>
                    <button type="submit" class="btn btn-primary" disabled id="saveBtn">
                        <i class="fa-solid fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Admin Body Background */
body.admin-body {
    background: #f9fafb;
}

.admin-section {
    margin-bottom: 32px;
}

.admin-section .section-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 20px;
    color: #1f2937;
}

.settings-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 24px;
}

.settings-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 24px;
    border-bottom: 1px solid #f3f4f6;
    transition: background 0.2s;
}

.settings-item:last-child {
    border-bottom: none;
}

.settings-item:hover {
    background: #f9fafb;
}

.settings-label {
    display: flex;
    align-items: center;
    gap: 16px;
    flex: 1;
}

.settings-label i {
    font-size: 1.25rem;
    color: #10b981;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #d1fae5;
    border-radius: 8px;
}

.settings-label h4 {
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 4px;
}

.settings-label p {
    font-size: 0.875rem;
    color: #6b7280;
    margin: 0;
}

.settings-value {
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 12px;
}

.settings-form {
    padding: 0;
}

.settings-item.editable {
    position: relative;
}

.value-display {
    display: flex;
    align-items: baseline;
    gap: 4px;
    margin-bottom: 12px;
}

.value-number {
    font-size: 1.75rem;
    font-weight: 700;
    color: #1f2937;
}

.value-unit {
    font-size: 1rem;
    font-weight: 500;
    color: #6b7280;
}

.settings-input {
    width: 100px;
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 1.5rem;
    font-weight: 700;
    text-align: center;
    transition: border-color 0.2s;
    margin-bottom: 12px;
}

.settings-input:focus {
    outline: none;
    border-color: #10b981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

.settings-input[readonly] {
    background: #f9fafb;
    cursor: not-allowed;
}

.settings-input:not([readonly]) {
    background: white;
    cursor: text;
}

.edit-btn {
    background: #10b981;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 8px;
    font-weight: 500;
    font-size: 0.875rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
}

.edit-btn:hover {
    background: #059669;
}

.edit-btn.save-mode {
    background: #3b82f6;
}

.edit-btn.save-mode:hover {
    background: #2563eb;
}

.settings-form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 24px;
    padding-top: 24px;
    border-top: 1px solid #e5e7eb;
}

.settings-form-actions .btn {
    min-width: 140px;
}

.settings-form-actions .btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Responsive Design */
@media (max-width: 768px) {
    .admin-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .settings-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
    }
    
    .settings-value {
        width: 100%;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    
    .edit-control {
        width: 100%;
    }
    
    .settings-input {
        width: 100%;
    }
    
    .settings-form-actions {
        flex-direction: column;
    }
    
    .settings-form-actions .btn {
        width: 100%;
    }
}
</style>

<script>
let originalValues = {};

function enableEdit(inputId, button) {
    const input = document.getElementById(inputId);
    const display = document.getElementById(inputId + 'Display');
    const form = document.getElementById('commissionForm');
    const saveBtn = document.getElementById('saveBtn');
    const valueDisplay = input.closest('.settings-value').querySelector('.value-display');
    
    // Store original value if not already stored
    if (!originalValues[inputId]) {
        originalValues[inputId] = input.value;
    }
    
    // Hide display, show input
    if (valueDisplay) valueDisplay.style.display = 'none';
    input.style.display = 'block';
    input.removeAttribute('readonly');
    input.focus();
    input.select();
    
    // Change button to save mode
    button.innerHTML = '<i class="fa-solid fa-check"></i> Done';
    button.classList.add('save-mode');
    button.onclick = function() { disableEdit(inputId, button); };
    
    // Enable save button
    saveBtn.disabled = false;
}

function disableEdit(inputId, button) {
    const input = document.getElementById(inputId);
    const display = document.getElementById(inputId + 'Display');
    const valueDisplay = input.closest('.settings-value').querySelector('.value-display');
    
    // Update display with current value
    if (display) {
        display.textContent = input.value;
    }
    
    // Hide input, show display
    input.style.display = 'none';
    input.setAttribute('readonly', 'readonly');
    if (valueDisplay) valueDisplay.style.display = 'flex';
    
    // Restore button
    button.innerHTML = '<i class="fa-solid fa-pencil"></i> Edit';
    button.classList.remove('save-mode');
    button.onclick = function() { enableEdit(inputId, button); };
    
    // Check if any field is being edited
    const anyEditing = Array.from(document.querySelectorAll('.settings-input')).some(inp => inp.style.display !== 'none' && !inp.hasAttribute('readonly'));
    document.getElementById('saveBtn').disabled = !anyEditing;
}

function resetForm() {
    // Reset all inputs
    Object.keys(originalValues).forEach(inputId => {
        const input = document.getElementById(inputId);
        const button = input.closest('.settings-value').querySelector('.edit-btn');
        disableEdit(inputId, button);
    });
    
    originalValues = {};
    document.getElementById('saveBtn').disabled = true;
}

document.getElementById('commissionForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const saveBtn = document.getElementById('saveBtn');
    const originalText = saveBtn.innerHTML;
    
    // Disable button and show loading
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';
    
    // Get all changed values
    const buyerInput = document.getElementById('buyerCommission');
    const sellerInput = document.getElementById('sellerCommission');
    const accountFeeInput = document.getElementById('accountFee');
    
    const data = {};
    
    if (buyerInput.style.display !== 'none' && buyerInput.value != originalValues['buyerCommission']) {
        data.buyer_commission_rate = parseFloat(buyerInput.value);
    }
    
    if (sellerInput.style.display !== 'none' && sellerInput.value != originalValues['sellerCommission']) {
        data.seller_commission_rate = parseFloat(sellerInput.value);
    }
    
    if (accountFeeInput.style.display !== 'none' && accountFeeInput.value != originalValues['accountFee']) {
        data.account_creation_fee = parseFloat(accountFeeInput.value);
    }
    
    // Send to server
    fetch('/api/update-commission-settings.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            // Update original values to new values
            if (data.buyer_commission_rate !== undefined) {
                originalValues['buyerCommission'] = data.buyer_commission_rate.toString();
                const buyerDisplay = document.getElementById('buyerCommissionDisplay');
                if (buyerDisplay) buyerDisplay.textContent = data.buyer_commission_rate;
            }
            
            if (data.seller_commission_rate !== undefined) {
                originalValues['sellerCommission'] = data.seller_commission_rate.toString();
                const sellerDisplay = document.getElementById('sellerCommissionDisplay');
                if (sellerDisplay) sellerDisplay.textContent = data.seller_commission_rate;
            }
            
            if (data.account_creation_fee !== undefined) {
                originalValues['accountFee'] = data.account_creation_fee.toString();
                const accountFeeDisplay = document.getElementById('accountFeeDisplay');
                if (accountFeeDisplay) accountFeeDisplay.textContent = data.account_creation_fee;
            }
            
            // Show success message
            alert('Commission settings saved successfully!');
            
            // Reset form UI
            resetForm();
        } else {
            alert('Error: ' + (result.error || 'Failed to save settings'));
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error saving settings. Please try again.');
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalText;
    });
});

// Track changes
document.querySelectorAll('.settings-input').forEach(input => {
    input.addEventListener('input', function() {
        const saveBtn = document.getElementById('saveBtn');
        const display = document.getElementById(this.id + 'Display');
        
        // Update display in real-time
        if (display) {
            display.textContent = this.value;
        }
        
        // Check if any field has changed
        const anyChanged = Array.from(document.querySelectorAll('.settings-input')).some(inp => {
            const id = inp.id;
            return inp.style.display !== 'none' && inp.value != originalValues[id] && !inp.hasAttribute('readonly');
        });
        saveBtn.disabled = !anyChanged;
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

