<?php
// Google OAuth 2.0 Configuration (overridden at deploy time by google-credentials.php)
if (file_exists(__DIR__ . '/google-credentials.php')) {
    require_once __DIR__ . '/google-credentials.php';
} else {
    define('GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID');
    define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET');
    define('GOOGLE_REDIRECT_URI', 'http://localhost/BaustExchange/google-callback.php');
}

// Google OAuth URLs
define('GOOGLE_AUTH_URL', 'https://accounts.google.com/o/oauth2/v2/auth');
define('GOOGLE_TOKEN_URL', 'https://oauth2.googleapis.com/token');
define('GOOGLE_USER_INFO_URL', 'https://www.googleapis.com/oauth2/v2/userinfo');

function getGoogleAuthUrl() {
    $params = http_build_query([
        'client_id' => GOOGLE_CLIENT_ID,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'response_type' => 'code',
        'scope' => 'openid email profile',
        'access_type' => 'offline',
        'prompt' => 'consent',
    ]);
    return GOOGLE_AUTH_URL . '?' . $params;
}

function exchangeGoogleCode($code) {
    $data = [
        'code' => $code,
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'grant_type' => 'authorization_code',
    ];
    
    $ch = curl_init(GOOGLE_TOKEN_URL);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

function getGoogleUserInfo($accessToken) {
    $ch = curl_init(GOOGLE_USER_INFO_URL);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}
