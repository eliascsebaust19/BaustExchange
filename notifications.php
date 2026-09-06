<?php
$pageTitle = 'Notifications - BAUST Exchange';
require_once __DIR__ . '/components/auth-check.php';

// Mark all as read
if (isset($_GET['mark_all_read'])) {
    query("UPDATE notifications SET is_read = 1 WHERE user_id = ?", [$_SESSION['user_id']]);
    redirect('/BaustExchange/notifications.php');
}

// Mark single as read
if (isset($_GET['read'])) {
    $notifId = (int)$_GET['read'];
    query("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?", [$notifId, $_SESSION['user_id']]);
    
    $notif = fetch("SELECT * FROM notifications WHERE id = ?", [$notifId]);
    if ($notif && $notif['reference_type'] && $notif['reference_id']) {
        switch ($notif['reference_type']) {
            case 'listing':
                redirect("/BaustExchange/item.php?id={$notif['reference_id']}");
                break;
            case 'request':
                redirect("/BaustExchange/requests.php");
                break;
            case 'rental':
                redirect("/BaustExchange/rentals.php");
                break;
            case 'share':
                redirect("/BaustExchange/requests.php?tab=share_incoming");
                break;
            case 'message':
                redirect("/BaustExchange/messages.php");
                break;
        }
    }
    redirect('/BaustExchange/notifications.php');
}

// Clear all
if (isset($_GET['clear_all'])) {
    delete('notifications', 'user_id = ?', [$_SESSION['user_id']]);
    redirect('/BaustExchange/notifications.php');
}

$notifications = fetchAll(
    "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC",
    [$_SESSION['user_id']]
);

$unreadCount = count_rows('notifications', 'user_id = ? AND is_read = 0', [$_SESSION['user_id']]);

include __DIR__ . '/components/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-2 d-none d-lg-block">
            <?php include __DIR__ . '/components/sidebar.php'; ?>
        </div>
        
        <div class="col-lg-7 col-md-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0">
                    Notifications
                    <?php if ($unreadCount > 0): ?>
                    <span class="badge bg-danger ms-2"><?= $unreadCount ?></span>
                    <?php endif; ?>
                </h4>
                <div>
                    <?php if ($unreadCount > 0): ?>
                    <a href="?mark_all_read=1" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-check-double me-1"></i> Mark all read
                    </a>
                    <?php endif; ?>
                    <?php if (!empty($notifications)): ?>
                    <a href="?clear_all=1" class="btn btn-outline-danger btn-sm" onclick="return confirm('Clear all notifications?')">
                        <i class="fas fa-trash me-1"></i> Clear all
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (empty($notifications)): ?>
            <div class="card">
                <div class="card-body">
                    <div class="empty-state">
                        <i class="fas fa-bell"></i>
                        <h5>No notifications</h5>
                        <p>You're all caught up!</p>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="list-group">
                <?php foreach ($notifications as $notif): ?>
                <a href="?read=<?= $notif['id'] ?>" 
                   class="list-group-item list-group-item-action <?= !$notif['is_read'] ? 'list-group-item-light' : '' ?>">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <?php
                            $icon = 'fa-bell';
                            $color = 'text-primary';
                            switch ($notif['type']) {
                                case 'exchange_request':
                                    $icon = 'fa-exchange-alt';
                                    $color = 'text-warning';
                                    break;
                                case 'rent_request':
                                    $icon = 'fa-hand-holding-usd';
                                    $color = 'text-warning';
                                    break;
                                case 'share_request':
                                    $icon = 'fa-share-alt';
                                    $color = 'text-warning';
                                    break;
                                case 'request_accepted':
                                    $icon = 'fa-check-circle';
                                    $color = 'text-success';
                                    break;
                                case 'rental_accepted':
                                case 'share_approved':
                                    $icon = 'fa-check-circle';
                                    $color = 'text-success';
                                    break;
                                case 'request_rejected':
                                    $icon = 'fa-times-circle';
                                    $color = 'text-danger';
                                    break;
                                case 'rental_rejected':
                                case 'share_rejected':
                                    $icon = 'fa-times-circle';
                                    $color = 'text-danger';
                                    break;
                                case 'rental_completed':
                                case 'share_completed':
                                    $icon = 'fa-check-double';
                                    $color = 'text-success';
                                    break;
                                case 'new_message':
                                    $icon = 'fa-envelope';
                                    $color = 'text-info';
                                    break;
                                case 'listing_approved':
                                    $icon = 'fa-check';
                                    $color = 'text-success';
                                    break;
                                case 'listing_rejected':
                                    $icon = 'fa-times';
                                    $color = 'text-danger';
                                    break;
                            }
                            ?>
                            <i class="fas <?= $icon ?> <?= $color ?> me-2"></i>
                            <span><?= sanitize($notif['message']) ?></span>
                            <?php if (!$notif['is_read']): ?>
                            <span class="badge bg-primary ms-2">New</span>
                            <?php endif; ?>
                        </div>
                        <small class="text-muted"><?= timeAgo($notif['created_at']) ?></small>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="col-lg-3 d-none d-lg-block">
            <?php include __DIR__ . '/components/right-sidebar.php'; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/components/footer.php'; ?>
