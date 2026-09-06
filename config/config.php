<?php
// Database Configuration (overridden at deploy time by db-credentials.php)
if (file_exists(__DIR__ . '/db-credentials.php')) {
    require_once __DIR__ . '/db-credentials.php';
} else {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'baust_exchange');
    define('DB_USER', 'root');
    define('DB_PASSWORD', '');
}
define('DB_CHARSET', 'utf8mb4');

// Site Configuration
define('SITE_NAME', 'BAUST Exchange');
define('SITE_URL', 'http://localhost/BaustExchange');
define('ITEMS_PER_PAGE', 12);

// Upload Configuration
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('ITEMS_UPLOAD_PATH', UPLOAD_PATH . 'items/');
define('PROFILES_UPLOAD_PATH', UPLOAD_PATH . 'profiles/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Session Configuration
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error Reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('max_execution_time', 30);

// Timezone
date_default_timezone_set('Asia/Dhaka');
