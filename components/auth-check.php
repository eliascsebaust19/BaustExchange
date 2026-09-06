<?php
require_once __DIR__ . '/../config/config.php';

// Check if database is connected
try {
    require_once __DIR__ . '/../config/database.php';
} catch (Throwable $e) {
    header("Location: /BaustExchange/index.php?error=database");
    exit;
}

if (!isLoggedIn()) {
    header("Location: /BaustExchange/login.php");
    exit;
}

if (isBlocked()) {
    session_destroy();
    header("Location: /BaustExchange/login.php?error=blocked");
    exit;
}

// Redirect incomplete profiles (Google login) to complete-profile.php
$currentPage = basename($_SERVER['PHP_SELF']);
if (!empty($_SESSION['needs_profile_completion']) && !in_array($currentPage, ['complete-profile.php', 'logout.php'])) {
    header("Location: /BaustExchange/complete-profile.php");
    exit;
}
