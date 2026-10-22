<?php
/**
 * Register API Endpoint
 */

require_once __DIR__ . '/../includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('/');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/pages/register.php?error=required');
}

// Get form data
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');
$city = trim($_POST['city'] ?? '');
$password = $_POST['password'] ?? '';
$password_confirm = $_POST['password_confirm'] ?? '';

// Validation
if (empty($username) || empty($email) || empty($password)) {
    redirect('/pages/register.php?error=required');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect('/pages/register.php?error=invalid_email');
}

if (strlen($password) < 6) {
    redirect('/pages/register.php?error=weak_password');
}

if ($password !== $password_confirm) {
    redirect('/pages/register.php?error=password_mismatch');
}

$pdo = getDB();

// Check if username exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$username]);
if ($stmt->fetch()) {
    redirect('/pages/register.php?error=username_exists');
}

// Check if email exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    redirect('/pages/register.php?error=email_exists');
}

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert user (verified by default, account fee not paid)
$stmt = $pdo->prepare("
    INSERT INTO users (username, email, password, phone, address, city, role, verified, account_fee_paid) 
    VALUES (?, ?, ?, ?, ?, ?, 'user', 1, 0)
");
$stmt->execute([$username, $email, $hashed_password, $phone, $address, $city]);

// Create invoice for account creation fee
$user_id = $pdo->lastInsertId();
$stmt = $pdo->prepare("
    INSERT INTO invoices (user_id, invoice_type, amount, fees, total) 
    VALUES (?, 'account_creation', 0, ?, ?)
");
$stmt->execute([$user_id, ACCOUNT_CREATION_FEE, ACCOUNT_CREATION_FEE]);

// Create notification for admin
$stmt = $pdo->prepare("
    INSERT INTO notifications (user_id, type, message) 
    SELECT id, 'new_user', ? FROM users WHERE role = 'admin'
");
$stmt->execute(["New user registered: $username"]);

// Redirect to login with success message
redirect('/pages/login.php?success=registered');

