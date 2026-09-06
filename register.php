<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

if (isLoggedIn()) {
    redirect('/BaustExchange/dashboard.php');
}

$errors = [];
$old = [
    'name' => '',
    'email' => '',
    'role' => 'student',
    'student_level' => 'junior',
    'phone' => '',
    'department' => '',
    'student_id' => '',
    'level_term' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = trim($_POST['role'] ?? 'student');
    $studentLevel = trim($_POST['student_level'] ?? 'junior');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $studentId = trim($_POST['student_id'] ?? '');
    $levelTerm = trim($_POST['level_term'] ?? '');
    $termsAgreed = isset($_POST['terms_agreed']);

    $old = [
        'name' => $name,
        'email' => $email,
        'role' => $role,
        'student_level' => $studentLevel,
        'phone' => $phone,
        'department' => $department,
        'student_id' => $studentId,
        'level_term' => $levelTerm
    ];

    if (empty($name)) $errors[] = 'Full name is required.';
    if (empty($email)) $errors[] = 'Email address is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirmPassword) $errors[] = 'Passwords do not match.';
    if (!$termsAgreed) $errors[] = 'You must accept the Terms & Conditions and Cookie Policy to proceed.';

    if (empty($errors)) {
        $existing = fetch("SELECT id FROM users WHERE email = ?", [$email]);
        if ($existing) {
            $errors[] = 'An account with this email already exists. Please login.';
        }
    }

    if (empty($errors)) {
        $allowedRoles = ['student', 'teacher'];
        $userRole = in_array($role, $allowedRoles) ? $role : 'student';
        $userLevel = $userRole === 'student' && in_array($studentLevel, ['senior', 'junior']) ? $studentLevel : null;

        // For teachers, UID and Level/Term are not applicable
        if ($userRole === 'teacher') {
            $studentId = null;
            $levelTerm = 'N/A';
            $userLevel = null;
        }

        $userId = insert('users', [
            'google_id' => 'manual_' . uniqid(),
            'name' => $name,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $userRole,
            'student_level' => $userLevel,
            'status' => 'active',
            'phone' => $phone ?: null,
            'department' => $department ?: null,
            'student_id' => $studentId ?: null,
            'level_term' => $levelTerm ?: null,
        ]);

        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role'] = $userRole;
        $_SESSION['user_level'] = $userLevel;
        $_SESSION['user_status'] = 'active';

        session_regenerate_id(true);
        logActivity($userId, 'registration', 'New user registered: ' . $email);

        redirect('/BaustExchange/dashboard.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - BAUST Exchange</title>
    <!-- Bootstrap & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Shared Auth Stylesheet -->
    <link href="/BaustExchange/assets/css/auth.css" rel="stylesheet">
</head>
<body class="auth-body">

    <div class="auth-page-wrapper">
        <!-- Left Card -->
        <div class="auth-card auth-card-md">
        <!-- Header -->
        <div class="auth-header">
            <div class="auth-logo-icon">
                <i class="fas fa-exchange-alt"></i>
            </div>
            <h2>BAUST Exchange</h2>
            <p>Create your campus marketplace account</p>
        </div>

        <!-- Body -->
        <div class="auth-body-content">
            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger py-2 px-3 mb-3">
                <ul class="mb-0 small ps-3">
                    <?php foreach ($errors as $err): ?>
                    <li><?= sanitize($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <form method="POST">
                <!-- Full Name -->
                <div class="auth-input-group">
                    <label class="auth-form-label">Full Name <span class="text-danger">*</span></label>
                    <div class="auth-input-wrapper">
                        <i class="fas fa-user auth-input-icon"></i>
                        <input type="text" class="auth-form-control" name="name" required
                               value="<?= sanitize($old['name']) ?>" placeholder="e.g. Tanvir Ahmed">
                    </div>
                </div>

                <!-- Email Address -->
                <div class="auth-input-group">
                    <label class="auth-form-label">Email Address <span class="text-danger">*</span></label>
                    <div class="auth-input-wrapper">
                        <i class="fas fa-envelope auth-input-icon"></i>
                        <input type="email" class="auth-form-control" name="email" required
                               value="<?= sanitize($old['email']) ?>" placeholder="your.name@baust.edu.bd">
                    </div>
                </div>

                <!-- Account Role & Department -->
                <div class="row g-3">
                    <div class="col-md-6 auth-input-group">
                        <label class="auth-form-label">Account Role <span class="text-danger">*</span></label>
                        <div class="auth-input-wrapper">
                            <i class="fas fa-user-tag auth-input-icon"></i>
                            <select class="auth-form-control" name="role" id="roleSelect" onchange="handleRoleChange()">
                                <option value="student" <?= $old['role'] === 'student' ? 'selected' : '' ?>>Student</option>
                                <option value="teacher" <?= $old['role'] === 'teacher' ? 'selected' : '' ?>>Teacher / Faculty</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6 auth-input-group" id="studentLevelGroup">
                        <label class="auth-form-label">Student Level <span class="text-danger">*</span></label>
                        <div class="auth-input-wrapper">
                            <i class="fas fa-layer-group auth-input-icon"></i>
                            <select class="auth-form-control" name="student_level" id="studentLevelSelect">
                                <option value="junior" <?= $old['student_level'] === 'junior' ? 'selected' : '' ?>>Junior Student</option>
                                <option value="senior" <?= $old['student_level'] === 'senior' ? 'selected' : '' ?>>Senior Student</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6 auth-input-group">
                        <label class="auth-form-label">Department</label>
                        <div class="auth-input-wrapper">
                            <i class="fas fa-graduation-cap auth-input-icon"></i>
                            <input type="text" class="auth-form-control" name="department" list="deptList"
                                   value="<?= sanitize($old['department']) ?>" placeholder="e.g., CSE, EEE">
                            <datalist id="deptList">
                                <option value="CSE">Computer Science & Engineering</option>
                                <option value="EEE">Electrical & Electronic Engineering</option>
                                <option value="ME">Mechanical Engineering</option>
                                <option value="IPE">Industrial & Production Engineering</option>
                                <option value="CE">Civil Engineering</option>
                                <option value="BBA">Business Administration</option>
                                <option value="English">English</option>
                            </datalist>
                        </div>
                    </div>
                </div>

                <!-- Student UID & Level/Term -->
                <div class="row g-3">
                    <div class="col-md-6 auth-input-group">
                        <label class="auth-form-label" id="idLabel">Student UID</label>
                        <div class="auth-input-wrapper">
                            <i class="fas fa-id-card auth-input-icon"></i>
                            <input type="text" class="auth-form-control" name="student_id" id="studentIdInput"
                                   value="<?= sanitize($old['student_id']) ?>" placeholder="e.g. 0802420405101139">
                        </div>
                    </div>

                    <div class="col-md-6 auth-input-group">
                        <label class="auth-form-label">Level & Term</label>
                        <div class="auth-input-wrapper">
                            <i class="fas fa-layer-group auth-input-icon"></i>
                            <select class="auth-form-control" name="level_term" id="levelTermSelect">
                                <option value="">Select Level & Term</option>
                                <option value="Level 1 Term I" <?= $old['level_term'] === 'Level 1 Term I' ? 'selected' : '' ?>>Level 1 Term I</option>
                                <option value="Level 1 Term II" <?= $old['level_term'] === 'Level 1 Term II' ? 'selected' : '' ?>>Level 1 Term II</option>
                                <option value="Level 2 Term I" <?= $old['level_term'] === 'Level 2 Term I' ? 'selected' : '' ?>>Level 2 Term I</option>
                                <option value="Level 2 Term II" <?= $old['level_term'] === 'Level 2 Term II' ? 'selected' : '' ?>>Level 2 Term II</option>
                                <option value="Level 3 Term I" <?= $old['level_term'] === 'Level 3 Term I' ? 'selected' : '' ?>>Level 3 Term I</option>
                                <option value="Level 3 Term II" <?= $old['level_term'] === 'Level 3 Term II' ? 'selected' : '' ?>>Level 3 Term II</option>
                                <option value="Level 4 Term I" <?= $old['level_term'] === 'Level 4 Term I' ? 'selected' : '' ?>>Level 4 Term I</option>
                                <option value="Level 4 Term II" <?= $old['level_term'] === 'Level 4 Term II' ? 'selected' : '' ?>>Level 4 Term II</option>
                                <option value="N/A" <?= $old['level_term'] === 'N/A' ? 'selected' : '' ?>>N/A (Faculty)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Phone Number -->
                <div class="auth-input-group">
                    <label class="auth-form-label">Phone Number</label>
                    <div class="auth-input-wrapper">
                        <i class="fas fa-phone auth-input-icon"></i>
                        <input type="tel" class="auth-form-control" name="phone"
                               value="<?= sanitize($old['phone']) ?>" placeholder="01700000000">
                    </div>
                </div>

                <!-- Passwords -->
                <div class="row g-3">
                    <div class="col-md-6 auth-input-group">
                        <label class="auth-form-label">Password <span class="text-danger">*</span></label>
                        <div class="auth-input-wrapper">
                            <i class="fas fa-lock auth-input-icon"></i>
                            <input type="password" class="auth-form-control" name="password" id="passInput" required
                                   minlength="6" placeholder="Min 6 characters">
                            <button type="button" class="auth-toggle-pass" onclick="togglePass('passInput', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="auth-strength-meter">
                            <div class="auth-strength-bar" id="strBar"></div>
                        </div>
                        <span class="auth-strength-text" id="strText">Password strength</span>
                    </div>

                    <div class="col-md-6 auth-input-group">
                        <label class="auth-form-label">Confirm Password <span class="text-danger">*</span></label>
                        <div class="auth-input-wrapper">
                            <i class="fas fa-shield-alt auth-input-icon"></i>
                            <input type="password" class="auth-form-control" name="confirm_password" id="confirmPassInput" required
                                   minlength="6" placeholder="Repeat password">
                            <button type="button" class="auth-toggle-pass" onclick="togglePass('confirmPassInput', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <span class="auth-strength-text" id="matchText"></span>
                    </div>
                </div>

                <!-- Terms & Cookies Agreement Checkbox -->
                <div class="form-check my-3 text-start">
                    <input class="form-check-input" type="checkbox" name="terms_agreed" id="registerTermsCheck" required checked>
                    <label class="form-check-label small text-muted" for="registerTermsCheck">
                        I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal" class="fw-bold">Terms & Conditions</a> and <a href="#" data-bs-toggle="modal" data-bs-target="#cookieModal" class="fw-bold">Cookie Policy</a>.
                    </label>
                </div>

                <button type="submit" class="btn-auth-primary mt-2">
                    <i class="fas fa-user-plus"></i> Create Account
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
                Already have an account? <a href="/BaustExchange/login.php">Login here</a>
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

    <!-- Terms Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-shield-alt text-primary me-2"></i>Terms & Conditions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body small text-secondary">
                    <h6>1. BAUST Campus Platform</h6>
                    <p>BAUST Exchange is reserved exclusively for verified BAUST students and faculty members.</p>
                    <h6>2. Fair & Honest Trading</h6>
                    <p>All items posted for sale, exchange, or giveaway must accurately represent their condition and description.</p>
                    <h6>3. Campus Safety</h6>
                    <p>All transactions and physical item exchanges should take place safely within the BAUST campus grounds.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Cookie Modal -->
    <div class="modal fade" id="cookieModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-cookie-bite text-warning me-2"></i>Cookie & Privacy Policy</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body small text-secondary">
                    <p>We use essential cookies to maintain your login session and security preferences on BAUST Exchange.</p>
                    <div class="p-3 bg-light rounded border text-center">
                        <i class="fas fa-file-download fa-2x text-primary mb-2"></i>
                        <h6>BAUST Exchange Cookie Policy</h6>
                        <a href="data:text/plain;charset=utf-8,BAUST%20Exchange%20Cookie%20Policy%0A%0AWe%20use%20essential%20session%20cookies%20to%20authenticate%20users%20and%20remember%20UI%20preferences." download="BAUST_Exchange_Cookie_Policy.txt" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-download me-1"></i> Download Cookie Policy (.txt)
                        </a>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function handleRoleChange() {
            const roleSelect = document.getElementById('roleSelect');
            const levelTermSelect = document.getElementById('levelTermSelect');
            const idLabel = document.getElementById('idLabel');
            const studentIdInput = document.getElementById('studentIdInput');
            const studentLevelGroup = document.getElementById('studentLevelGroup');

            if (roleSelect.value === 'teacher') {
                idLabel.innerHTML = 'Student UID <span class="badge bg-secondary ms-1">Not Eligible</span>';
                studentIdInput.value = '';
                studentIdInput.placeholder = 'Not applicable for teachers';
                studentIdInput.disabled = true;

                levelTermSelect.value = 'N/A';
                levelTermSelect.disabled = true;

                studentLevelGroup.style.display = 'none';
                document.getElementById('studentLevelSelect').value = '';
            } else {
                idLabel.innerHTML = 'Student UID';
                studentIdInput.placeholder = 'e.g. 0802420405101139';
                studentIdInput.disabled = false;

                levelTermSelect.disabled = false;
                if (levelTermSelect.value === 'N/A') {
                    levelTermSelect.value = '';
                }

                studentLevelGroup.style.display = 'block';
                if (!document.getElementById('studentLevelSelect').value) {
                    document.getElementById('studentLevelSelect').value = 'junior';
                }
            }
        }

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

        const passInput = document.getElementById('passInput');
        const confirmPassInput = document.getElementById('confirmPassInput');
        const strBar = document.getElementById('strBar');
        const strText = document.getElementById('strText');
        const matchText = document.getElementById('matchText');

        passInput.addEventListener('input', function() {
            const val = passInput.value;
            let score = 0;
            if (val.length >= 6) score += 30;
            if (val.length >= 10) score += 30;
            if (/[A-Z]/.test(val) && /[0-9]/.test(val)) score += 40;

            strBar.style.width = score + '%';

            if (val.length === 0) {
                strBar.style.width = '0%';
                strText.textContent = 'Password strength';
                strText.style.color = '#64748b';
            } else if (score <= 30) {
                strBar.style.backgroundColor = '#ef4444';
                strText.textContent = 'Weak';
                strText.style.color = '#ef4444';
            } else if (score <= 60) {
                strBar.style.backgroundColor = '#f59e0b';
                strText.textContent = 'Medium';
                strText.style.color = '#d97706';
            } else {
                strBar.style.backgroundColor = '#10b981';
                strText.textContent = 'Strong';
                strText.style.color = '#059669';
            }
            checkMatch();
        });

        confirmPassInput.addEventListener('input', checkMatch);

        function checkMatch() {
            if (confirmPassInput.value.length === 0) {
                matchText.textContent = '';
            } else if (confirmPassInput.value === passInput.value) {
                matchText.textContent = '✓ Passwords match';
                matchText.style.color = '#059669';
            } else {
                matchText.textContent = '✗ Passwords do not match';
                matchText.style.color = '#ef4444';
            }
        }

        // Initialize role-dependent labels on page load
        document.addEventListener('DOMContentLoaded', handleRoleChange);
    </script>
</body>
</html>
