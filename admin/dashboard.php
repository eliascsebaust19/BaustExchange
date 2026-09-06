<?php
$pageTitle = 'Admin Dashboard - BAUST Exchange';
require_once __DIR__ . '/../components/auth-check.php';

if (!isAdmin()) redirect('/BaustExchange/dashboard.php');

// Get stats
$totalUsers = count_rows('users');
$students = count_rows('users', 'role = "student"');
$seniorStudents = count_rows('users', 'role = "student" AND student_level = "senior"');
$juniorStudents = count_rows('users', 'role = "student" AND student_level = "junior"');
$teachers = count_rows('users', 'role = "teacher"');
$admins = count_rows('users', 'role = "admin"');
$totalListings = count_rows('listings');
$activeListings = count_rows('listings', 'status = "active"');
$pendingListings = count_rows('listings', 'status = "pending"');
$totalExchanges = count_rows('exchange_requests', 'status = "completed"');
$pendingReports = count_rows('reports', 'status = "pending"');
$blockedUsers = count_rows('users', 'status = "blocked"');

// Recent activities
$recentActivities = fetchAll(
    "SELECT a.*, u.name as user_name 
     FROM activities a 
     LEFT JOIN users u ON a.user_id = u.id 
     ORDER BY a.created_at DESC LIMIT 10"
);

// Recent users
$recentUsers = fetchAll(
    "SELECT * FROM users ORDER BY created_at DESC LIMIT 5"
);

// Recent listings
$recentListings = fetchAll(
    "SELECT l.*, u.name as owner_name, c.name as category_name 
     FROM listings l 
     JOIN users u ON l.user_id = u.id 
     JOIN categories c ON l.category_id = c.id 
     ORDER BY l.created_at DESC LIMIT 5"
);

include __DIR__ . '/../components/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-2 d-none d-lg-block">
            <?php include __DIR__ . '/../components/admin/sidebar.php'; ?>
        </div>
        
        <div class="col-lg-10 col-md-12">
            <h4 class="mb-4">Admin Dashboard</h4>
            
            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card border-primary">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-muted mb-1">Total Users</h6>
                                    <h3 class="mb-0"><?= $totalUsers ?></h3>
                                </div>
                                <i class="fas fa-users fa-2x text-primary opacity-50"></i>
                            </div>
                            <small class="text-muted">Students: <?= $students ?> | Teachers: <?= $teachers ?> | Admins: <?= $admins ?></small>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card border-success">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-muted mb-1">Active Listings</h6>
                                    <h3 class="mb-0"><?= $activeListings ?></h3>
                                </div>
                                <i class="fas fa-store fa-2x text-success opacity-50"></i>
                            </div>
                            <small class="text-muted">Pending: <?= $pendingListings ?> | Total: <?= $totalListings ?></small>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card border-info">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-muted mb-1">Completed Exchanges</h6>
                                    <h3 class="mb-0"><?= $totalExchanges ?></h3>
                                </div>
                                <i class="fas fa-exchange-alt fa-2x text-info opacity-50"></i>
                            </div>
                            <small class="text-muted">All time completed exchanges</small>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card border-danger">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-muted mb-1">Pending Reports</h6>
                                    <h3 class="mb-0"><?= $pendingReports ?></h3>
                                </div>
                                <i class="fas fa-flag fa-2x text-danger opacity-50"></i>
                            </div>
                            <small class="text-muted">Blocked Users: <?= $blockedUsers ?></small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <!-- Recent Users -->
                <div class="col-xl-6 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Recent Users</h6>
                            <a href="/BaustExchange/admin/users.php" class="btn btn-link btn-sm">View All</a>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Role</th>
                                        <th>Joined</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentUsers as $user): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="<?= $user['profile_image'] ?? '/BaustExchange/assets/images/default-avatar.png' ?>" 
                                                     class="rounded-circle me-2" width="32" height="32" style="object-fit: cover;">
                                                <div>
                                                    <small class="d-block"><?= sanitize($user['name']) ?></small>
                                                    <small class="text-muted"><?= sanitize($user['email']) ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="badge bg-primary"><?= userRoleLabel($user['role'], $user['student_level'] ?? null) ?></span></td>
                                        <td><small class="text-muted"><?= timeAgo($user['created_at']) ?></small></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Listings -->
                <div class="col-xl-6 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Recent Listings</h6>
                            <a href="/BaustExchange/admin/listings.php" class="btn btn-link btn-sm">View All</a>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Owner</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentListings as $listing): ?>
                                    <tr>
                                        <td>
                                            <small>
                                                <a href="/BaustExchange/item.php?id=<?= $listing['id'] ?>" class="text-decoration-none">
                                                    <?= sanitize($listing['title']) ?>
                                                </a>
                                                <br><span class="text-muted"><?= sanitize($listing['category_name']) ?></span>
                                            </small>
                                        </td>
                                        <td><small><?= sanitize($listing['owner_name']) ?></small></td>
                                        <td><span class="badge <?= getStatusBadgeClass($listing['status']) ?>"><?= ucfirst($listing['status']) ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Recent Activity</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Action</th>
                                <th>Description</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentActivities as $activity): ?>
                            <tr>
                                <td><small><?= $activity['user_name'] ? sanitize($activity['user_name']) : 'System' ?></small></td>
                                <td><span class="badge bg-light text-dark"><?= sanitize($activity['action']) ?></span></td>
                                <td><small class="text-muted"><?= sanitize($activity['description']) ?></small></td>
                                <td><small class="text-muted"><?= timeAgo($activity['created_at']) ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../components/footer.php'; ?>
