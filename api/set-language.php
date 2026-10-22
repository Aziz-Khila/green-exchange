<?php
/**
 * Set Language API
 */

require_once __DIR__ . '/../includes/functions.php';

$lang = $_GET['lang'] ?? $_POST['lang'] ?? '';

if (empty($lang) || !in_array($lang, AVAILABLE_LANGUAGES)) {
    redirect('/');
}

setLanguage($lang);

// Redirect back to referer or home
$referer = $_SERVER['HTTP_REFERER'] ?? '/';
redirect($referer);

