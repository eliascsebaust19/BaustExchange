<?php
$pageTitle = 'Settings - BAUST Exchange';
require_once __DIR__ . '/components/auth-check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        flash('error', 'Invalid security token.');
        redirect('/BaustExchange/settings.php');
    }
    
    // Handle account deletion
    if (isset($_POST['delete_account'])) {
        $userId = $_SESSION['user_id'];
        logActivity($userId, 'account_deleted', 'User deleted their account');
        
        // Delete user data
        delete('listing_images', 'listing_id IN (SELECT id FROM listings WHERE user_id = ?)', [$userId]);
        delete('listings', 'user_id = ?', [$userId]);
        delete('exchange_requests', 'sender_id = ? OR receiver_id = ?', [$userId, $userId]);
        delete('wanted_items', 'user_id = ?', [$userId]);
        delete('messages', 'sender_id = ? OR receiver_id = ?', [$userId, $userId]);
        delete('notifications', 'user_id = ?', [$userId]);
        delete('reports', 'reporter_id = ?', [$userId]);
        delete('users', 'id = ?', [$userId]);
        
        session_destroy();
        header("Location: /BaustExchange/index.php?success=1");
        exit;
    }
}

include __DIR__ . '/components/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-2 d-none d-lg-block">
            <?php include __DIR__ . '/components/sidebar.php'; ?>
        </div>
        
        <div class="col-lg-7 col-md-8">
            <h4 class="mb-4">Settings</h4>
            
            <?php if ($msg = flash('success')): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= $msg ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if ($msg = flash('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= $msg ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <!-- Account Settings -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">Account Settings</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" value="<?= sanitize($_SESSION['user_email']) ?>" disabled>
                        <small class="text-muted">Email cannot be changed (Google account)</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <input type="text" class="form-control" value="<?= userRoleLabel($_SESSION['user_role'], $_SESSION['user_level'] ?? null) ?>" disabled>
                        <small class="text-muted">Contact admin to change your role</small>
                    </div>
                </div>
            </div>
            
            <!-- Danger Zone -->
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h6 class="mb-0">Danger Zone</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">
                        Once you delete your account, there is no going back. All your data will be permanently deleted.
                    </p>
                    <form method="POST" onsubmit="return confirm('Are you absolutely sure you want to delete your account? This action cannot be undone.')">
                        <?= csrfField() ?>
                        <input type="hidden" name="delete_account" value="1">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-1"></i> Delete My Account
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 d-none d-lg-block">
            <?php include __DIR__ . '/components/right-sidebar.php'; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/components/footer.php'; ?>
