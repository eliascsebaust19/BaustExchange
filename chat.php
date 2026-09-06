<?php
$pageTitle = 'Chat - BAUST Exchange';
require_once __DIR__ . '/components/auth-check.php';

$otherUserId = (int)($_GET['user_id'] ?? 0);
$listingId = (int)($_GET['listing_id'] ?? 0);

if ($otherUserId <= 0) redirect('/BaustExchange/messages.php');

$otherUser = fetch("SELECT * FROM users WHERE id = ?", [$otherUserId]);
if (!$otherUser) redirect('/BaustExchange/messages.php');

$listing = null;
if ($listingId > 0) {
    $listing = fetch("SELECT * FROM listings WHERE id = ?", [$listingId]);
}

// Handle message sending
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']);
    if (!empty($message)) {
        insert('messages', [
            'sender_id' => $_SESSION['user_id'],
            'receiver_id' => $otherUserId,
            'listing_id' => $listingId ?: null,
            'message' => $message,
        ]);
        
        createNotification($otherUserId, 'new_message', 
            $_SESSION['user_name'] . ' sent you a message', null, 'message');
    }
    redirect("/BaustExchange/chat.php?user_id={$otherUserId}&listing_id={$listingId}");
}

// Mark messages as read
query("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0", 
    [$otherUserId, $_SESSION['user_id']]);

// Get messages
$messages = fetchAll(
    "SELECT m.*, u.name as sender_name 
     FROM messages m 
     JOIN users u ON m.sender_id = u.id 
     WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)
     ORDER BY m.created_at ASC",
    [$_SESSION['user_id'], $otherUserId, $otherUserId, $_SESSION['user_id']]
);

include __DIR__ . '/components/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-2 d-none d-lg-block">
            <?php include __DIR__ . '/components/sidebar.php'; ?>
        </div>
        
        <div class="col-lg-7 col-md-8">
            <div class="card chat-container">
                <!-- Chat Header -->
                <div class="card-header d-flex align-items-center">
                    <a href="/BaustExchange/messages.php" class="btn btn-link me-2">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <img src="<?= getProfileImageUrl($otherUser['profile_image'] ?? null) ?>" 
                         class="rounded-circle me-2" width="36" height="36" style="object-fit: cover; border: 2px solid #2563eb;">
                    <div>
                        <h6 class="mb-0"><?= sanitize($otherUser['name']) ?></h6>
                        <small class="text-muted"><?= userRoleLabel($otherUser['role'], $otherUser['student_level'] ?? null) ?></small>
                    </div>
                    <?php if ($listing): ?>
                    <div class="ms-auto">
                        <a href="/BaustExchange/item.php?id=<?= $listing['id'] ?>" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-eye me-1"></i> View Item
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                
                <input type="hidden" id="current-user-id" value="<?= $_SESSION['user_id'] ?>">
                
                <!-- Messages -->
                <div class="chat-messages">
                    <?php if (empty($messages)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-comments fa-3x mb-3 opacity-50"></i>
                        <p>No messages yet. Start the conversation!</p>
                    </div>
                    <?php else: ?>
                        <?php foreach ($messages as $msg): ?>
                        <div class="chat-message <?= $msg['sender_id'] == $_SESSION['user_id'] ? 'sent' : 'received' ?>">
                            <div class="message-bubble">
                                <?= nl2br(sanitize($msg['message'])) ?>
                                <small class="d-block mt-1 opacity-75"><?= timeAgo($msg['created_at']) ?></small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Input -->
                <div class="chat-input">
                    <form method="POST" class="d-flex gap-2">
                        <input type="text" class="form-control" name="message" 
                               placeholder="Type a message..." required autocomplete="off">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i>
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

<script>
// Scroll to bottom of chat
const chatMessages = document.querySelector('.chat-messages');
if (chatMessages) {
    chatMessages.scrollTop = chatMessages.scrollHeight;
}
</script>

<?php include __DIR__ . '/components/footer.php'; ?>
