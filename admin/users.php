<?php
$pageTitle = 'Manage Users - BAUST Exchange';
require_once __DIR__ . '/../components/auth-check.php';

if (!isAdmin()) redirect('/BaustExchange/dashboard.php');

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $targetId = (int)$_GET['id'];
    
    $targetUser = fetch("SELECT * FROM users WHERE id = ?", [$targetId]);
    if ($targetUser && $targetId != $_SESSION['user_id']) {
        switch ($action) {
            case 'promote_teacher':
                update('users', ['role' => 'teacher'], 'id = ?', [$targetId]);
                logActivity($_SESSION['user_id'], 'admin_action', "Promoted {$targetUser['name']} to teacher");
                flash('success', "User promoted to teacher.");
                break;
            case 'demote_student':
                update('users', ['role' => 'student'], 'id = ?', [$targetId]);
                logActivity($_SESSION['user_id'], 'admin_action', "Demoted {$targetUser['name']} to student");
                flash('success', "User demoted to student.");
                break;
            case 'set_senior':
                update('users', ['student_level' => 'senior'], 'id = ?', [$targetId]);
                logActivity($_SESSION['user_id'], 'admin_action', "Set {$targetUser['name']} as senior student");
                flash('success', "User set as senior student.");
                break;
            case 'set_junior':
                update('users', ['student_level' => 'junior'], 'id = ?', [$targetId]);
                logActivity($_SESSION['user_id'], 'admin_action', "Set {$targetUser['name']} as junior student");
                flash('success', "User set as junior student.");
                break;
            case 'block':
                update('users', ['status' => 'blocked'], 'id = ?', [$targetId]);
                logActivity($_SESSION['user_id'], 'admin_action', "Blocked user: {$targetUser['name']}");
                flash('success', "User blocked.");
                break;
            case 'unblock':
                update('users', ['status' => 'active'], 'id = ?', [$targetId]);
                logActivity($_SESSION['user_id'], 'admin_action', "Unblocked user: {$targetUser['name']}");
                flash('success', "User unblocked.");
                break;
        }
    }
    redirect('/BaustExchange/admin/users.php');
}

// Filters
$search = $_GET['search'] ?? '';
$role = $_GET['role'] ?? '';
$status = $_GET['status'] ?? '';

$where = ["1=1"];
$params = [];

if ($search) {
    $where[] = "(name LIKE ? OR email LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if ($role) {
    $where[] = "role = ?";
    $params[] = $role;
}

if ($status) {
    $where[] = "status = ?";
    $params[] = $status;
}

$whereClause = implode(' AND ', $where);
$users = fetchAll("SELECT * FROM users WHERE {$whereClause} ORDER BY created_at DESC", $params);

include __DIR__ . '/../components/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-2 d-none d-lg-block">
            <?php include __DIR__ . '/../components/admin/sidebar.php'; ?>
        </div>
        
        <div class="col-lg-10 col-md-12">
            <h4 class="mb-4">Manage Users</h4>
            
            <?php if ($msg = flash('success')): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= $msg ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="search" placeholder="Search by name or email..." value="<?= sanitize($search) ?>">
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" name="role">
                                <option value="">All Roles</option>
                                <option value="student" <?= $role === 'student' ? 'selected' : '' ?>>Student</option>
                                <option value="teacher" <?= $role === 'teacher' ? 'selected' : '' ?>>Teacher</option>
                                <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" name="status">
                                <option value="">All Status</option>
                                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="blocked" <?= $status === 'blocked' ? 'selected' : '' ?>>Blocked</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search me-1"></i> Filter</button>
                        </div>
                        <div class="col-md-2">
                            <a href="/BaustExchange/admin/users.php" class="btn btn-outline-secondary w-100">Clear</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Users Table -->
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Role</th>
                                    <th>Department</th>
                                    <th>Status</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?= $user['profile_image'] ?? '/BaustExchange/assets/images/default-avatar.png' ?>" 
                                                 class="rounded-circle me-2" width="36" height="36" style="object-fit: cover;">
                                            <div>
                                                <small class="d-block fw-bold"><?= sanitize($user['name']) ?></small>
                                                <small class="text-muted"><?= sanitize($user['email']) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-primary"><?= userRoleLabel($user['role'], $user['student_level'] ?? null) ?></span></td>
                                    <td><small><?= sanitize($user['department'] ?? '-') ?></small></td>
                                    <td>
                                        <span class="badge <?= $user['status'] === 'active' ? 'bg-success' : 'bg-danger' ?>">
                                            <?= ucfirst($user['status']) ?>
                                        </span>
                                    </td>
                                    <td><small class="text-muted"><?= timeAgo($user['created_at']) ?></small></td>
                                    <td>
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <div class="btn-group btn-group-sm">
                                            <?php if ($user['role'] === 'student'): ?>
                                            <a href="?action=promote_teacher&id=<?= $user['id'] ?>" class="btn btn-outline-success" title="Promote to Teacher">
                                                <i class="fas fa-arrow-up"></i>
                                            </a>
                                            <?php elseif ($user['role'] === 'teacher'): ?>
                                            <a href="?action=demote_student&id=<?= $user['id'] ?>" class="btn btn-outline-warning" title="Demote to Student">
                                                <i class="fas fa-arrow-down"></i>
                                            </a>
                                            <?php endif; ?>
                                            
                                            <?php if ($user['role'] === 'student'): ?>
                                            <?php if (($user['student_level'] ?? '') !== 'senior'): ?>
                                            <a href="?action=set_senior&id=<?= $user['id'] ?>" class="btn btn-outline-primary" title="Set as Senior">
                                                <i class="fas fa-user-graduate"></i>
                                            </a>
                                            <?php elseif (($user['student_level'] ?? '') !== 'junior'): ?>
                                            <a href="?action=set_junior&id=<?= $user['id'] ?>" class="btn btn-outline-secondary" title="Set as Junior">
                                                <i class="fas fa-user"></i>
                                            </a>
                                            <?php endif; ?>
                                            <?php endif; ?>
                                            
                                            <?php if ($user['status'] === 'active'): ?>
                                            <a href="?action=block&id=<?= $user['id'] ?>" class="btn btn-outline-danger" title="Block User"
                                               onclick="return confirm('Block this user?')">
                                                <i class="fas fa-ban"></i>
                                            </a>
                                            <?php else: ?>
                                            <a href="?action=unblock&id=<?= $user['id'] ?>" class="btn btn-outline-success" title="Unblock User">
                                                <i class="fas fa-check"></i>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                        <?php else: ?>
                                        <small class="text-muted">You</small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../components/footer.php'; ?>
