<?php
$currentUser = currentUser();
$recentNotifications = fetchAll("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5", [$_SESSION['user_id']]);
$wantedItems = fetchAll("SELECT w.*, c.name as category_name FROM wanted_items w JOIN categories c ON w.category_id = c.id WHERE w.status = 'active' ORDER BY w.created_at DESC LIMIT 3");
?>

<div class="right-sidebar">
    <!-- Panel Header with Toggle Control -->
    <div class="d-flex align-items-center justify-content-between mb-2 pb-1 border-bottom">
        <span class="text-muted small fw-bold text-uppercase" style="letter-spacing: 0.5px;">
            <i class="fas fa-sliders-h text-primary me-1"></i> Quick Panel
        </span>
        <button class="btn btn-sm btn-outline-secondary py-0 px-2 rounded-pill" type="button" 
                data-bs-toggle="collapse" data-bs-target="#rightSidebarContent" 
                id="toggleRightSidebarBtn" style="font-size: 0.75rem; font-weight: 600;">
            <i class="fas fa-chevron-up me-1" id="rightSidebarIcon"></i> 
            <span id="rightSidebarBtnText">Hide</span>
        </button>
    </div>

    <!-- Collapsible Container -->
    <div class="collapse show" id="rightSidebarContent">
        <!-- User Profile Card -->
        <div class="card mb-3">
            <div class="card-body text-center py-3">
                <img src="<?= getProfileImageUrl($currentUser['profile_image'] ?? null) ?>" 
                     alt="Profile" class="rounded-circle mb-2" width="60" height="60" style="object-fit: cover; border: 2px solid #059669;">
                <h6 class="mb-1 fw-bold"><?= sanitize($currentUser['name']) ?></h6>
                <small class="text-muted">
                    <span class="badge bg-primary"><?= userRoleLabel($currentUser['role'], $currentUser['student_level'] ?? null) ?></span>
                    <?php if ($currentUser['department']): ?>
                    <span class="ms-1 fw-semibold"><?= sanitize($currentUser['department']) ?></span>
                    <?php endif; ?>
                </small>
                <div class="mt-2">
                    <a href="/BaustExchange/profile.php" class="btn btn-outline-primary btn-sm w-100">View Profile</a>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card mb-3">
            <div class="card-header py-2 d-flex justify-content-between align-items-center" data-bs-toggle="collapse" data-bs-target="#quickActionsCollapse" style="cursor: pointer;">
                <h6 class="mb-0 small fw-bold"><i class="fas fa-bolt text-warning me-1"></i> Quick Actions</h6>
                <i class="fas fa-chevron-down small text-muted"></i>
            </div>
            <div class="collapse show" id="quickActionsCollapse">
                <div class="card-body p-2">
                    <a href="/BaustExchange/post-item.php" class="btn btn-primary btn-sm w-100 mb-2">
                        <i class="fas fa-plus me-1"></i> Post Item
                    </a>
                    <a href="/BaustExchange/post-wanted.php" class="btn btn-outline-primary btn-sm w-100">
                        <i class="fas fa-search me-1"></i> Add Wanted Item
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Notifications -->
        <div class="card mb-3">
            <div class="card-header py-2 d-flex justify-content-between align-items-center" data-bs-toggle="collapse" data-bs-target="#notificationsCollapse" style="cursor: pointer;">
                <h6 class="mb-0 small fw-bold"><i class="fas fa-bell text-info me-1"></i> Notifications</h6>
                <i class="fas fa-chevron-down small text-muted"></i>
            </div>
            <div class="collapse show" id="notificationsCollapse">
                <div class="card-body p-0">
                    <?php if (empty($recentNotifications)): ?>
                    <p class="text-muted p-3 mb-0 small text-center">No notifications yet.</p>
                    <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($recentNotifications as $notif): ?>
                        <li class="list-group-item px-3 py-2">
                            <small class="d-block text-dark"><?= sanitize($notif['message']) ?></small>
                            <small class="text-muted" style="font-size: 0.72rem;"><?= timeAgo($notif['created_at']) ?></small>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                    <div class="p-2 text-center border-top">
                        <a href="/BaustExchange/notifications.php" class="btn btn-link btn-sm p-0 text-decoration-none small">View All</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Wanted Items -->
        <div class="card mb-3">
            <div class="card-header py-2 d-flex justify-content-between align-items-center" data-bs-toggle="collapse" data-bs-target="#wantedItemsCollapse" style="cursor: pointer;">
                <h6 class="mb-0 small fw-bold"><i class="fas fa-search-dollar text-success me-1"></i> Wanted Items</h6>
                <i class="fas fa-chevron-down small text-muted"></i>
            </div>
            <div class="collapse show" id="wantedItemsCollapse">
                <div class="card-body p-0">
                    <?php if (empty($wantedItems)): ?>
                    <p class="text-muted p-3 mb-0 small text-center">No wanted items available.</p>
                    <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($wantedItems as $wanted): ?>
                        <li class="list-group-item px-3 py-2">
                            <small class="d-block fw-bold text-dark"><?= sanitize($wanted['title']) ?></small>
                            <small class="text-muted d-block"><?= sanitize($wanted['category_name']) ?></small>
                            <?php if ($wanted['budget']): ?>
                            <small class="text-success fw-semibold">Budget: ৳<?= number_format($wanted['budget']) ?></small>
                            <?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                    <div class="p-2 text-center border-top">
                        <a href="/BaustExchange/wanted.php" class="btn btn-link btn-sm p-0 text-decoration-none small">View All</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="card">
            <div class="card-header py-2 d-flex justify-content-between align-items-center" data-bs-toggle="collapse" data-bs-target="#activityCollapse" style="cursor: pointer;">
                <h6 class="mb-0 small fw-bold"><i class="fas fa-history text-secondary me-1"></i> Recent Activity</h6>
                <i class="fas fa-chevron-down small text-muted"></i>
            </div>
            <div class="collapse show" id="activityCollapse">
                <div class="card-body p-0">
                    <?php
                    $activities = fetchAll("SELECT * FROM activities WHERE user_id = ? ORDER BY created_at DESC LIMIT 5", [$_SESSION['user_id']]);
                    if (empty($activities)):
                    ?>
                    <p class="text-muted p-3 mb-0 small text-center">No recent activity.</p>
                    <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($activities as $activity): ?>
                        <li class="list-group-item px-3 py-2">
                            <small class="d-block text-dark"><?= sanitize($activity['description']) ?></small>
                            <small class="text-muted" style="font-size: 0.72rem;"><?= timeAgo($activity['created_at']) ?></small>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
