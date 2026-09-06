<?php
$pageTitle = 'Manage Reports - BAUST Exchange';
require_once __DIR__ . '/../components/auth-check.php';

if (!isAdmin()) redirect('/BaustExchange/dashboard.php');

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $reportId = (int)$_GET['id'];
    
    $report = fetch("SELECT * FROM reports WHERE id = ?", [$reportId]);
    if ($report) {
        switch ($action) {
            case 'resolve':
                update('reports', ['status' => 'resolved'], 'id = ?', [$reportId]);
                logActivity($_SESSION['user_id'], 'admin_action', "Resolved report #{$reportId}");
                flash('success', 'Report resolved.');
                break;
            case 'dismiss':
                update('reports', ['status' => 'dismissed'], 'id = ?', [$reportId]);
                logActivity($_SESSION['user_id'], 'admin_action', "Dismissed report #{$reportId}");
                flash('success', 'Report dismissed.');
                break;
            case 'remove_listing':
                if ($report['listing_id']) {
                    update('listings', ['status' => 'removed'], 'id = ?', [$report['listing_id']]);
                    update('reports', ['status' => 'resolved'], 'id = ?', [$reportId]);
                    logActivity($_SESSION['user_id'], 'admin_action', "Removed listing due to report #{$reportId}");
                    flash('success', 'Listing removed and report resolved.');
                }
                break;
            case 'block_user':
                if ($report['reported_user_id']) {
                    update('users', ['status' => 'blocked'], 'id = ?', [$report['reported_user_id']]);
                    update('reports', ['status' => 'resolved'], 'id = ?', [$reportId]);
                    logActivity($_SESSION['user_id'], 'admin_action', "Blocked user due to report #{$reportId}");
                    flash('success', 'User blocked and report resolved.');
                }
                break;
        }
    }
    redirect('/BaustExchange/admin/reports.php');
}

$reports = fetchAll(
    "SELECT r.*, 
     rp.name as reporter_name, rp.email as reporter_email,
     ru.name as reported_user_name, ru.email as reported_user_email,
     l.title as listing_title
     FROM reports r 
     JOIN users rp ON r.reporter_id = rp.id 
     LEFT JOIN users ru ON r.reported_user_id = ru.id 
     LEFT JOIN listings l ON r.listing_id = l.id 
     ORDER BY r.created_at DESC"
);

include __DIR__ . '/../components/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-2 d-none d-lg-block">
            <?php include __DIR__ . '/../components/admin/sidebar.php'; ?>
        </div>
        
        <div class="col-lg-10 col-md-12">
            <h4 class="mb-4">Manage Reports</h4>
            
            <?php if ($msg = flash('success')): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= $msg ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if (empty($reports)): ?>
            <div class="card">
                <div class="card-body">
                    <div class="empty-state">
                        <i class="fas fa-flag"></i>
                        <h5>No reports</h5>
                        <p>All clear! No reports to review.</p>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Reporter</th>
                                    <th>Against</th>
                                    <th>Listing</th>
                                    <th>Reason</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reports as $report): ?>
                                <tr>
                                    <td><?= $report['id'] ?></td>
                                    <td>
                                        <small>
                                            <?= sanitize($report['reporter_name']) ?>
                                            <br><span class="text-muted"><?= sanitize($report['reporter_email']) ?></span>
                                        </small>
                                    </td>
                                    <td>
                                        <small>
                                            <?= $report['reported_user_name'] ? sanitize($report['reported_user_name']) : '-' ?>
                                            <?php if ($report['reported_user_email']): ?>
                                            <br><span class="text-muted"><?= sanitize($report['reported_user_email']) ?></span>
                                            <?php endif; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php if ($report['listing_title']): ?>
                                        <a href="/BaustExchange/item.php?id=<?= $report['listing_id'] ?>" class="text-decoration-none small">
                                            <?= sanitize($report['listing_title']) ?>
                                        </a>
                                        <?php else: ?>
                                        -
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge bg-warning text-dark"><?= ucfirst(str_replace('_', ' ', $report['reason'])) ?></span></td>
                                    <td><small class="text-muted"><?= sanitize($report['description'] ?? '-') ?></small></td>
                                    <td><span class="badge <?= getStatusBadgeClass($report['status']) ?>"><?= ucfirst($report['status']) ?></span></td>
                                    <td>
                                        <?php if ($report['status'] === 'pending'): ?>
                                        <div class="btn-group btn-group-sm">
                                            <a href="?action=resolve&id=<?= $report['id'] ?>" class="btn btn-outline-success" title="Resolve">
                                                <i class="fas fa-check"></i>
                                            </a>
                                            <a href="?action=dismiss&id=<?= $report['id'] ?>" class="btn btn-outline-secondary" title="Dismiss">
                                                <i class="fas fa-times"></i>
                                            </a>
                                            <?php if ($report['listing_id']): ?>
                                            <a href="?action=remove_listing&id=<?= $report['id'] ?>" class="btn btn-outline-warning" title="Remove Listing"
                                               onclick="return confirm('Remove the listed item?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                            <?php endif; ?>
                                            <?php if ($report['reported_user_id']): ?>
                                            <a href="?action=block_user&id=<?= $report['id'] ?>" class="btn btn-outline-danger" title="Block User"
                                               onclick="return confirm('Block this user?')">
                                                <i class="fas fa-ban"></i>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                        <?php else: ?>
                                        <small class="text-muted">Handled</small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../components/footer.php'; ?>
