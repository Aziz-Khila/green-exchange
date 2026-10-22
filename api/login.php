<?php
/**
 * Login API Endpoint
 */

require_once __DIR__ . '/../includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('/');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/pages/login.php?error=required');
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    redirect('/pages/login.php?error=required');
}

$pdo = getDB();

// Check if username or email exists
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
$stmt->execute([$username, $username]);
$user = $stmt->fetch();

if (!$user) {
    redirect('/pages/login.php?error=invalid');
}

// Verify password
if (!password_verify($password, $user['password'])) {
    redirect('/pages/login.php?error=invalid');
}

// Set session
$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role'] = $user['role'];

// Redirect based on role
if ($user['role'] === 'admin') {
    redirect('/pages/admin.php');
} else {
    redirect('/pages/dashboard.php');
}

