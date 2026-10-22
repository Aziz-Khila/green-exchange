<?php
/**
 * Contact Form API
 */

require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/#contact');
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$message = trim($_POST['message'] ?? '');

if (empty($name) || empty($email) || empty($message)) {
    redirect('/#contact?error=required');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect('/#contact?error=invalid_email');
}

$pdo = getDB();

// Create notification for admin
$stmt = $pdo->prepare("
    INSERT INTO notifications (user_id, type, message) 
    SELECT id, 'contact_form', ? FROM users WHERE role = 'admin'
");
$contact_message = "Contact form submission from $name ($email): " . substr($message, 0, 100);
$stmt->execute([$contact_message]);

// In a real application, you would send an email here

redirect('/#contact?success=sent');

