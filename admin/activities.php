<?php
$pageTitle = 'Activity Log - BAUST Exchange';
require_once __DIR__ . '/../components/auth-check.php';

if (!isAdmin()) redirect('/BaustExchange/dashboard.php');

// Handle clear
if (isset($_GET['clear'])) {
    delete('activities', '1=1');
    logActivity($_SESSION['user_id'], 'admin_action', 'Cleared activity log');
    flash('success', 'Activity log cleared.');
    redirect('/BaustExchange/admin/activities.php');
}

$activities = fetchAll(
    "SELECT a.*, u.name as user_name, u.email as user_email
     FROM activities a 
     LEFT JOIN users u ON a.user_id = u.id 
     ORDER BY a.created_at DESC LIMIT 100"
);

include __DIR__ . '/../components/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-2 d-none d-lg-block">
            <?php include __DIR__ . '/../components/admin/sidebar.php'; ?>
        </div>
        
        <div class="col-lg-10 col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0">Activity Log</h4>
                <a href="?clear=1" class="btn btn-outline-danger btn-sm" onclick="return confirm('Clear all activity logs?')">
                    <i class="fas fa-trash me-1"></i> Clear Log
                </a>
            </div>
            
            <?php if ($msg = flash('success')): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= $msg ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Description</th>
                                    <th>IP Address</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activities as $activity): ?>
                                <tr>
                                    <td>
                                        <small>
                                            <?= $activity['user_name'] ? sanitize($activity['user_name']) : 'System' ?>
                                            <?php if ($activity['user_email']): ?>
                                            <br><span class="text-muted"><?= sanitize($activity['user_email']) ?></span>
                                            <?php endif; ?>
                                        </small>
                                    </td>
                                    <td><span class="badge bg-light text-dark"><?= sanitize($activity['action']) ?></span></td>
                                    <td><small><?= sanitize($activity['description']) ?></small></td>
                                    <td><small class="text-muted"><?= sanitize($activity['ip_address']) ?></small></td>
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
</div>

<?php include __DIR__ . '/../components/footer.php'; ?>
