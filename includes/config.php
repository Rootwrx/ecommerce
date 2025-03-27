<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define constants
define('SITE_NAME', 'E-commerce Store');
define('BASE_URL', 'http://localhost/eco2');
define('ADMIN_URL', BASE_URL . '/admin');
define('ASSETS_URL', BASE_URL . '/assets');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'dev');
define('DB_PASS', 'dev');
define('DB_NAME', 'eco2');

/*
    For Hosting
// Define constants
define('SITE_NAME', 'E-commerce Store');
define('BASE_URL', 'http://site-ecommerce.hstn.me');
define('ADMIN_URL', BASE_URL . '/admin');
define('ASSETS_URL', BASE_URL . '/assets');

// Database configuration
define('DB_HOST', 'sql308.hstn.me');
define('DB_USER', 'mseet_38613261');
define('DB_PASS', 'AeonFree00');
define('DB_NAME', 'mseet_38613261_eco2');

*/
// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set('UTC');
?>