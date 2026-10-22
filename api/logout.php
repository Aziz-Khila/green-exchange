<?php
/**
 * Logout API Endpoint
 */

require_once __DIR__ . '/../includes/functions.php';

// Destroy session
session_destroy();

// Redirect to login
redirect('/pages/login.php?success=logout');

