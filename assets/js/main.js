// Green Exchange - Main JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = 'red';
                } else {
                    field.style.borderColor = '';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
    });
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
});

// Language menu toggle
function toggleLanguageMenu() {
    const menu = document.getElementById('langMenu');
    const userMenu = document.getElementById('userMenu');
    if (menu) {
        menu.classList.toggle('show');
    }
    // Close user menu if open
    if (userMenu) {
        userMenu.classList.remove('show');
    }
}

function toggleUserMenu() {
    const userMenu = document.getElementById('userMenu');
    const langMenu = document.getElementById('langMenu');
    if (userMenu) {
        userMenu.classList.toggle('show');
    }
    // Close language menu if open
    if (langMenu) {
        langMenu.classList.remove('show');
    }
}

// Close menus when clicking outside
document.addEventListener('click', function(event) {
    const langMenu = document.getElementById('langMenu');
    const userMenu = document.getElementById('userMenu');
    const langToggle = event.target.closest('.lang-toggle');
    const userToggle = event.target.closest('.user-menu-toggle');
    
    if (!langToggle && langMenu && langMenu.classList.contains('show')) {
        langMenu.classList.remove('show');
    }
    
    if (!userToggle && userMenu && userMenu.classList.contains('show')) {
        userMenu.classList.remove('show');
    }
});

// Close language menu when clicking outside
document.addEventListener('click', function(event) {
    const langSelector = document.querySelector('.language-selector');
    const langMenu = document.getElementById('langMenu');
    
    if (langSelector && langMenu && !langSelector.contains(event.target)) {
        langMenu.classList.remove('show');
    }
});

