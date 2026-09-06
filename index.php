<?php
require_once __DIR__ . '/config/config.php';

$dbConnected = false;
try {
    require_once __DIR__ . '/config/database.php';
    $dbConnected = true;
    if (isLoggedIn()) {
        redirect('/BaustExchange/dashboard.php');
    }
} catch (Throwable $e) {
    $dbConnected = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BAUST Exchange - Buy, Sell, Share, Exchange, Rent</title>
    <!-- Bootstrap & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Shared Auth Stylesheet -->
    <link href="/BaustExchange/assets/css/auth.css" rel="stylesheet">
</head>
<body class="auth-body">

    <div class="auth-page-wrapper">
        <!-- Left Card -->
        <div class="auth-card auth-card-sm">
            <!-- Header -->
            <div class="auth-header">
                <div class="auth-logo-icon">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <h2>BAUST Exchange</h2>
                <p>Buy • Sell • Share • Exchange • Rent</p>
            </div>

            <!-- Body -->
            <div class="auth-body-content">
                <p class="text-center text-muted mb-4" style="font-size: 0.9rem; line-height: 1.5;">
                    A simple campus marketplace for BAUST teachers & students (senior/junior). 
                    Find books, electronics, furniture, and academic materials easily.
                </p>

                <?php if (!$dbConnected): ?>
                <div class="alert alert-warning py-2 mb-3 small">
                    <strong>Setup Required!</strong> Database not configured yet.
                    <br><a href="/BaustExchange/setup.php" class="alert-link">Click here to run setup</a>
                </div>
                <?php endif; ?>

                <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger py-2 mb-3 small alert-dismissible fade show">
                    <?php
                    $errors = [
                        'blocked' => 'Your account has been blocked. Please contact admin.',
                        'auth_failed' => 'Authentication failed. Please try again.',
                        'database' => 'A database error occurred. Please try again.',
                    ];
                    echo $errors[$_GET['error']] ?? 'An error occurred.';
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success py-2 mb-3 small alert-dismissible fade show">
                    You have been logged out successfully.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if ($dbConnected): ?>
                <div class="d-flex flex-column gap-2 mb-3">
                    <!-- Create Account Button -->
                    <a href="/BaustExchange/register.php" class="btn-auth-primary">
                        <i class="fas fa-user-plus"></i> Create Account
                    </a>

                    <!-- Login Button -->
                    <a href="/BaustExchange/login.php" class="btn-auth-outline">
                        <i class="fas fa-sign-in-alt"></i> Login with Email
                    </a>
                </div>

                <div class="auth-divider">
                    <span>OR</span>
                </div>

                <!-- Google Login -->
                <?php require_once __DIR__ . '/config/google-config.php'; ?>
                <a href="<?= getGoogleAuthUrl() ?>" class="btn-auth-google">
                    <svg width="18" height="18" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                    </svg>
                    Continue with Google
                </a>
                <?php else: ?>
                <a href="/BaustExchange/setup.php" class="btn-auth-primary">
                    <i class="fas fa-cog"></i> Run Setup
                </a>
                <?php endif; ?>

                <div class="auth-footer-text mt-3" style="font-size: 0.8rem;">
                    <i class="fas fa-shield-alt me-1 text-primary"></i> Secure campus authentication
                </div>
            </div>

            <!-- Features Footer -->
            <div class="auth-features-footer">
                <div class="auth-feature-item">
                    <i class="fas fa-book"></i> Books
                </div>
                <div class="auth-feature-item">
                    <i class="fas fa-laptop"></i> Electronics
                </div>
                <div class="auth-feature-item">
                    <i class="fas fa-chair"></i> Furniture
                </div>
                <div class="auth-feature-item">
                    <i class="fas fa-pen"></i> Stationery
                </div>
            </div>
        </div>

        <!-- Right Side Welcome Hero Text -->
        <div class="auth-hero-right d-none d-lg-block">
            <h1>Welcome to BAUST Exchange</h1>
            <p>The premier campus marketplace for buying, selling, sharing, exchanging & renting books, gadgets, stationery & academic gear with verified teachers and students.</p>
            <div class="auth-hero-badges">
                <div class="auth-hero-badge-item"><i class="fas fa-shield-halved text-success"></i> Verified Campus Peers</div>
                <div class="auth-hero-badge-item"><i class="fas fa-comments text-info"></i> Direct Chat & Messaging</div>
                <div class="auth-hero-badge-item"><i class="fas fa-arrows-rotate text-warning"></i> Buy, Sell, Share, Exchange & Rent</div>
            </div>
        </div>
    </div>

    <!-- JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
