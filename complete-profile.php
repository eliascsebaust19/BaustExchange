<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

if (!isLoggedIn()) {
    redirect('/BaustExchange/login.php');
}

$userId = $_SESSION['user_id'];
$user = fetch("SELECT * FROM users WHERE id = ?", [$userId]);
if (!$user) redirect('/BaustExchange/logout.php');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = trim($_POST['role'] ?? 'student');
    $studentLevel = trim($_POST['student_level'] ?? 'junior');
    $department = trim($_POST['department'] ?? '');
    $studentId = trim($_POST['student_id'] ?? '');
    $levelTerm = trim($_POST['level_term'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $termsAgreed = isset($_POST['terms_agreed']);

    if (empty($department)) $errors[] = 'Department is required.';
    if (!$termsAgreed) $errors[] = 'You must accept the Terms & Conditions and Cookie Policy to proceed.';
    
    if ($role === 'student' && empty($studentId)) {
        $errors[] = 'Student UID is required for student accounts.';
    }

    if (empty($errors)) {
        $allowedRoles = ['student', 'teacher'];
        $userRole = in_array($role, $allowedRoles) ? $role : 'student';
        $userLevel = $userRole === 'student' && in_array($studentLevel, ['senior', 'junior']) ? $studentLevel : null;

        if ($userRole === 'teacher') {
            $studentId = null;
            $levelTerm = 'N/A';
            $userLevel = null;
        }

        update('users', [
            'role' => $userRole,
            'student_level' => $userLevel,
            'department' => $department,
            'student_id' => $studentId ?: null,
            'level_term' => $levelTerm ?: null,
            'phone' => $phone ?: null,
        ], 'id = ?', [$userId]);

        $_SESSION['user_role'] = $userRole;
        $_SESSION['user_level'] = $userLevel;
        unset($_SESSION['needs_profile_completion']);

        logActivity($userId, 'profile_completion', 'User completed profile details');
        flash('success', 'Profile setup complete! Welcome to BAUST Exchange.');
        redirect('/BaustExchange/dashboard.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Your Profile - BAUST Exchange</title>
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
                <i class="fas fa-user-check"></i>
            </div>
            <h2>Complete Profile</h2>
            <p>Welcome <?= sanitize($user['name']) ?>! Please complete your account info to continue</p>
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
                <!-- Account Role & Department -->
                <div class="row g-3">
                    <div class="col-md-6 auth-input-group">
                        <label class="auth-form-label">Account Role <span class="text-danger">*</span></label>
                        <div class="auth-input-wrapper">
                            <i class="fas fa-user-tag auth-input-icon"></i>
                            <select class="auth-form-control" name="role" id="roleSelect" onchange="handleRoleChange()">
                                <option value="student" <?= $user['role'] === 'student' ? 'selected' : '' ?>>Student</option>
                                <option value="teacher" <?= $user['role'] === 'teacher' ? 'selected' : '' ?>>Teacher / Faculty</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6 auth-input-group" id="studentLevelGroup">
                        <label class="auth-form-label">Student Level <span class="text-danger">*</span></label>
                        <div class="auth-input-wrapper">
                            <i class="fas fa-layer-group auth-input-icon"></i>
                            <select class="auth-form-control" name="student_level" id="studentLevelSelect">
                                <option value="junior" <?= ($user['student_level'] ?? '') === 'junior' ? 'selected' : '' ?>>Junior Student</option>
                                <option value="senior" <?= ($user['student_level'] ?? '') === 'senior' ? 'selected' : '' ?>>Senior Student</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6 auth-input-group">
                        <label class="auth-form-label">Department <span class="text-danger">*</span></label>
                        <div class="auth-input-wrapper">
                            <i class="fas fa-graduation-cap auth-input-icon"></i>
                            <input type="text" class="auth-form-control" name="department" list="deptList" required
                                   value="<?= sanitize($user['department'] ?? '') ?>" placeholder="e.g., CSE, EEE">
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
                        <label class="auth-form-label" id="idLabel">Student UID <span class="text-danger">*</span></label>
                        <div class="auth-input-wrapper">
                            <i class="fas fa-id-card auth-input-icon"></i>
                            <input type="text" class="auth-form-control" name="student_id" id="studentIdInput"
                                   value="<?= sanitize($user['student_id'] ?? '') ?>" placeholder="e.g. 0802420405101139">
                        </div>
                    </div>

                    <div class="col-md-6 auth-input-group">
                        <label class="auth-form-label">Level & Term</label>
                        <div class="auth-input-wrapper">
                            <i class="fas fa-layer-group auth-input-icon"></i>
                            <select class="auth-form-control" name="level_term" id="levelTermSelect">
                                <option value="">Select Level & Term</option>
                                <option value="Level 1 Term I">Level 1 Term I</option>
                                <option value="Level 1 Term II">Level 1 Term II</option>
                                <option value="Level 2 Term I">Level 2 Term I</option>
                                <option value="Level 2 Term II">Level 2 Term II</option>
                                <option value="Level 3 Term I">Level 3 Term I</option>
                                <option value="Level 3 Term II">Level 3 Term II</option>
                                <option value="Level 4 Term I">Level 4 Term I</option>
                                <option value="Level 4 Term II">Level 4 Term II</option>
                                <option value="N/A">N/A (Faculty)</option>
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
                               value="<?= sanitize($user['phone'] ?? '') ?>" placeholder="01700000000">
                    </div>
                </div>

                <!-- Terms & Cookies Agreement Checkbox -->
                <div class="form-check my-3 text-start">
                    <input class="form-check-input" type="checkbox" name="terms_agreed" id="termsCheck" required checked>
                    <label class="form-check-label small" for="termsCheck">
                        I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal" class="fw-bold">Terms & Conditions</a> and <a href="#" data-bs-toggle="modal" data-bs-target="#cookieModal" class="fw-bold">Cookie Policy</a>.
                    </label>
                </div>

                <button type="submit" class="btn-auth-primary">
                    <i class="fas fa-check-circle"></i> Save & Continue to Dashboard
                </button>
            </form>
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
                studentIdInput.required = false;

                levelTermSelect.value = 'N/A';
                levelTermSelect.disabled = true;

                studentLevelGroup.style.display = 'none';
                document.getElementById('studentLevelSelect').value = 'junior';
            } else {
                idLabel.innerHTML = 'Student UID <span class="text-danger">*</span>';
                studentIdInput.placeholder = 'e.g. 0802420405101139';
                studentIdInput.disabled = false;
                studentIdInput.required = true;

                levelTermSelect.disabled = false;
                if (levelTermSelect.value === 'N/A') {
                    levelTermSelect.value = '';
                }

                studentLevelGroup.style.display = 'block';
            }
        }

        document.addEventListener('DOMContentLoaded', handleRoleChange);
    </script>
</body>
</html>
