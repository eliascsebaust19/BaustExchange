<?php
require_once __DIR__ . '/config/config.php';

try {
    require_once __DIR__ . '/config/database.php';
} catch (Throwable $e) {
    header("Location: /BaustExchange/index.php?error=database");
    exit;
}

if (isLoggedIn()) {
    redirect('/BaustExchange/dashboard.php');
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email and password are required.';
    } else {
        $user = fetch("SELECT * FROM users WHERE email = ?", [$email]);

        if (!$user) {
            $error = 'No account found with this email. Please register first.';
        } elseif ($user['status'] === 'blocked') {
            $error = 'Your account has been blocked. Contact admin.';
        } elseif (!$user['password']) {
            $error = 'This account uses Google login. Please login with Google.';
        } elseif (!password_verify($password, $user['password'])) {
            $error = 'Incorrect password.';
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_level'] = $user['student_level'] ?? null;
            $_SESSION['user_status'] = $user['status'];

            session_regenerate_id(true);
            logActivity($user['id'], 'login', 'User logged in');

            redirect('/BaustExchange/dashboard.php');
        }
    }
}

if (isset($_GET['error'])) {
    $errorMap = [
        'blocked' => 'Your account has been blocked.',
        'auth_failed' => 'Google authentication failed.',
        'database' => 'Database error. Please try again.',
    ];
    $error = $errorMap[$_GET['error']] ?? 'An error occurred.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - BAUST Exchange</title>
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
                <p>Welcome back! Please login to continue</p>
            </div>

            <!-- Body -->
            <div class="auth-body-content">
                <?php if ($error): ?>
                <div class="alert alert-danger py-2 small mb-3"><?= sanitize($error) ?></div>
                <?php endif; ?>

                <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success py-2 small mb-3">Account created successfully! Please login.</div>
                <?php endif; ?>

                <form method="POST">
                    <div class="auth-input-group">
                        <label class="auth-form-label">Email Address</label>
                        <div class="auth-input-wrapper">
                            <i class="fas fa-envelope auth-input-icon"></i>
                            <input type="email" class="auth-form-control" name="email" required
                                   value="<?= sanitize($email) ?>" placeholder="your.name@baust.edu.bd">
                        </div>
                    </div>

                    <div class="auth-input-group">
                        <label class="auth-form-label">Password</label>
                        <div class="auth-input-wrapper">
                            <i class="fas fa-lock auth-input-icon"></i>
                            <input type="password" class="auth-form-control" name="password" id="loginPassword" required
                                   placeholder="Enter your password">
                            <button type="button" class="auth-toggle-pass" onclick="togglePass('loginPassword', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-auth-primary mt-2">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </button>
                </form>

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

                <div class="auth-footer-text">
                    Don't have an account? <a href="/BaustExchange/register.php">Register here</a>
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
            <p>The premier campus marketplace for buying, selling, exchanging books, gadgets, stationery & academic gear with verified peers.</p>
            <div class="auth-hero-badges">
                <div class="auth-hero-badge-item"><i class="fas fa-shield-halved text-success"></i> Verified Campus Peers</div>
                <div class="auth-hero-badge-item"><i class="fas fa-comments text-info"></i> Direct Chat & Messaging</div>
                <div class="auth-hero-badge-item"><i class="fas fa-arrows-rotate text-warning"></i> Easy Item Exchange</div>
            </div>
        </div>
    </div>

    <!-- JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePass(inputId, btn) {
            const input = document.getElementById(inputId);
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
    </script>
</body>
</html>
