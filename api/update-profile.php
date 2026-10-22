<?php
/**
 * Update Profile API
 */

require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    redirect('/pages/login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/pages/edit-profile.php');
}

$user = getCurrentUser();
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$city = trim($_POST['city'] ?? '');
$address = trim($_POST['address'] ?? '');
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validation
if (empty($email)) {
    redirect('/pages/edit-profile.php?error=required');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect('/pages/edit-profile.php?error=invalid_email');
}

$pdo = getDB();

// Check if email exists (for other users)
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
$stmt->execute([$email, $user['id']]);
if ($stmt->fetch()) {
    redirect('/pages/edit-profile.php?error=email_exists');
}

// Update profile
$stmt = $pdo->prepare("
    UPDATE users 
    SET email = ?, phone = ?, city = ?, address = ? 
    WHERE id = ?
");
$stmt->execute([$email, $phone, $city, $address, $user['id']]);

// Update password if provided
if (!empty($new_password)) {
    if (strlen($new_password) < 6) {
        redirect('/pages/edit-profile.php?error=weak_password');
    }
    
    if ($new_password !== $confirm_password) {
        redirect('/pages/edit-profile.php?error=password_mismatch');
    }
    
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([$hashed_password, $user['id']]);
}

redirect('/pages/edit-profile.php?success=1');

