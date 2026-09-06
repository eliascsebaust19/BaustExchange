<?php
$pageTitle = 'Messages - BAUST Exchange';
require_once __DIR__ . '/components/auth-check.php';

$userId = $_SESSION['user_id'];

// Get conversations
$conversations = fetchAll(
    "SELECT m.*, 
     CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END as other_user_id,
     (SELECT name FROM users WHERE id = CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END) as other_user_name,
     (SELECT profile_image FROM users WHERE id = CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END) as other_user_image,
     (SELECT role FROM users WHERE id = CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END) as other_user_role,
     (SELECT student_level FROM users WHERE id = CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END) as other_user_level
     FROM messages m 
     WHERE m.id IN (
         SELECT MAX(id) FROM messages 
         WHERE sender_id = ? OR receiver_id = ? 
         GROUP BY CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END
     )
     ORDER BY m.created_at DESC",
    [$userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId]
);

include __DIR__ . '/components/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-2 d-none d-lg-block">
            <?php include __DIR__ . '/components/sidebar.php'; ?>
        </div>
        
        <div class="col-lg-7 col-md-8">
            <h4 class="mb-4">Messages</h4>
            
            <?php if (empty($conversations)): ?>
            <div class="card">
                <div class="card-body">
                    <div class="empty-state">
                        <i class="fas fa-envelope"></i>
                        <h5>No messages yet</h5>
                        <p>Start a conversation by contacting an item owner.</p>
                        <a href="/BaustExchange/marketplace.php" class="btn btn-primary">Browse Marketplace</a>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="list-group">
                <?php foreach ($conversations as $conv): ?>
                <a href="/BaustExchange/chat.php?user_id=<?= $conv['other_user_id'] ?>" 
                   class="list-group-item list-group-item-action message-thread <?= !$conv['is_read'] && $conv['receiver_id'] == $userId ? 'unread' : '' ?>">
                    <div class="d-flex align-items-center">
                        <img src="<?= getProfileImageUrl($conv['other_user_image'] ?? null) ?>" 
                             class="rounded-circle me-3" width="48" height="48" style="object-fit: cover; border: 2px solid #2563eb;">
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between">
                                <h6 class="mb-0">
                                    <?= sanitize($conv['other_user_name']) ?>
                                    <span class="badge bg-light text-dark ms-1"><?= userRoleLabel($conv['other_user_role'], $conv['other_user_level'] ?? null) ?></span>
                                </h6>
                                <small class="text-muted"><?= timeAgo($conv['created_at']) ?></small>
                            </div>
                            <p class="mb-0 text-muted small text-truncate" style="max-width: 400px;">
                                <?= $conv['sender_id'] == $userId ? 'You: ' : '' ?>
                                <?= sanitize($conv['message']) ?>
                            </p>
                        </div>
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
