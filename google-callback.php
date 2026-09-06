<?php
require_once __DIR__ . '/config/config.php';

// Check if database is connected
try {
    require_once __DIR__ . '/config/database.php';
} catch (Throwable $e) {
    header("Location: /BaustExchange/index.php?error=database");
    exit;
}

require_once __DIR__ . '/config/google-config.php';

if (!isset($_GET['code'])) {
    redirect('/BaustExchange/login.php');
}

$code = $_GET['code'];

// Exchange code for tokens
$tokenData = exchangeGoogleCode($code);

if (!isset($tokenData['access_token'])) {
    error_log("Google token exchange failed: " . json_encode($tokenData));
    redirect('/BaustExchange/index.php?error=auth_failed');
}

// Get user info
$userInfo = getGoogleUserInfo($tokenData['access_token']);

if (!isset($userInfo['id']) || !isset($userInfo['email'])) {
    error_log("Failed to get Google user info: " . json_encode($userInfo));
    redirect('/BaustExchange/index.php?error=auth_failed');
}

// Check if user exists
$existingUser = fetch("SELECT * FROM users WHERE google_id = ?", [$userInfo['id']]);

if ($existingUser) {
    // Check if blocked
    if ($existingUser['status'] === 'blocked') {
        redirect('/BaustExchange/index.php?error=blocked');
    }
    
    // Update profile image if changed
    if (isset($userInfo['picture']) && $userInfo['picture'] !== $existingUser['profile_image']) {
        update('users', ['profile_image' => $userInfo['picture']], 'id = ?', [$existingUser['id']]);
    }
    
    // Login existing user
    $_SESSION['user_id'] = $existingUser['id'];
    $_SESSION['user_name'] = $existingUser['name'];
    $_SESSION['user_email'] = $existingUser['email'];
    $_SESSION['user_role'] = $existingUser['role'];
    $_SESSION['user_level'] = $existingUser['student_level'] ?? null;
    $_SESSION['user_status'] = $existingUser['status'];
    
    // Check if details are missing
    if (empty($existingUser['department'])) {
        $_SESSION['needs_profile_completion'] = true;
        redirect('/BaustExchange/complete-profile.php');
    }
    
    // Regenerate session ID for security
    session_regenerate_id(true);
    
    // Log activity
    logActivity($existingUser['id'], 'login', 'User logged in');
    
    redirect('/BaustExchange/dashboard.php');
} else {
    // Create new user
    $newUserId = insert('users', [
        'google_id' => $userInfo['id'],
        'name' => $userInfo['name'],
        'email' => $userInfo['email'],
        'profile_image' => $userInfo['picture'] ?? null,
        'role' => 'student',
        'status' => 'active',
    ]);
    
    // Set session
    $_SESSION['user_id'] = $newUserId;
    $_SESSION['user_name'] = $userInfo['name'];
    $_SESSION['user_email'] = $userInfo['email'];
    $_SESSION['user_role'] = 'student';
    $_SESSION['user_status'] = 'active';
    $_SESSION['needs_profile_completion'] = true;
    
    // Regenerate session ID for security
    session_regenerate_id(true);
    
    // Log activity
    logActivity($newUserId, 'registration', 'New Google user registered: ' . $userInfo['email']);
    
    redirect('/BaustExchange/complete-profile.php');
}
