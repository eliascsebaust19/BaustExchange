<?php
require_once __DIR__ . '/config/config.php';

try {
    require_once __DIR__ . '/config/database.php';
    if (isLoggedIn()) {
        logActivity($_SESSION['user_id'], 'logout', 'User logged out');
    }
} catch (Throwable $e) {
    // Continue with logout even if database error occurs
}

// Clear session
$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();
header("Location: /BaustExchange/index.php?success=1");
exit;
