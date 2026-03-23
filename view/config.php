<?php
// Database configuration
define('DB_HOST', '127.0.0.1'); // Changed from 'localhost' to explicit IP
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'kol_koc_booking2');

// Application configuration
define('SITE_URL', 'http://localhost/Report4');
define('UPLOAD_PATH', __DIR__ . '/../uploads');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Time zone
date_default_timezone_set('Asia/Ho_Chi_Minh');