<?php
$pageTitle = 'Requests - BAUST Exchange';
require_once __DIR__ . '/components/auth-check.php';

$tab = $_GET['tab'] ?? 'incoming';

// Get incoming share requests (items I own being requested to share)
$incomingShares = fetchAll(
    "SELECT s.*, l.title as listing_title, l.transaction_type,
     u.name as requester_name, u.role as requester_role, u.student_level as requester_level, u.profile_image as requester_image,
     (SELECT image_path FROM listing_images WHERE listing_id = l.id LIMIT 1) as listing_image
     FROM shares s 
     JOIN listings l ON s.listing_id = l.id 
     JOIN users u ON s.requester_id = u.id 
     WHERE s.owner_id = ? 
     ORDER BY s.created_at DESC",
    [$_SESSION['user_id']]
);

// Get outgoing share requests (items I requested to share)
$outgoingShares = fetchAll(
    "SELECT s.*, l.title as listing_title, l.transaction_type,
     u.name as owner_name, u.role as owner_role, u.student_level as owner_level, u.profile_image as owner_image,
     (SELECT image_path FROM listing_images WHERE listing_id = l.id LIMIT 1) as listing_image
     FROM shares s 
     JOIN listings l ON s.listing_id = l.id 
     JOIN users u ON s.owner_id = u.id 
     WHERE s.requester_id = ? 
     ORDER BY s.created_at DESC",
    [$_SESSION['user_id']]
);

// Get incoming requests
$incomingRequests = fetchAll(
    "SELECT er.*, l.title as listing_title, l.transaction_type,
     u.name as sender_name, u.role as sender_role, u.student_level as sender_level, u.profile_image as sender_image,
     (SELECT image_path FROM listing_images WHERE listing_id = l.id LIMIT 1) as listing_image
     FROM exchange_requests er 
     JOIN listings l ON er.listing_id = l.id 
     JOIN users u ON er.sender_id = u.id 
     WHERE er.receiver_id = ? 
     ORDER BY er.created_at DESC",
    [$_SESSION['user_id']]
);

// Get outgoing requests
$outgoingRequests = fetchAll(
    "SELECT er.*, l.title as listing_title, l.transaction_type,
     u.name as receiver_name, u.role as receiver_role, u.student_level as receiver_level, u.profile_image as receiver_image,
     (SELECT image_path FROM listing_images WHERE listing_id = l.id LIMIT 1) as listing_image
     FROM exchange_requests er 
     JOIN listings l ON er.listing_id = l.id 
     JOIN users u ON er.receiver_id = u.id 
     WHERE er.sender_id = ? 
     ORDER BY er.created_at DESC",
    [$_SESSION['user_id']]
);

include __DIR__ . '/components/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-2 d-none d-lg-block">
            <?php include __DIR__ . '/components/sidebar.php'; ?>
        </div>
        
        <div class="col-lg-7 col-md-8">
            <h4 class="mb-4">Requests</h4>
            
            <?php if ($msg = flash('success')): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= $msg ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <ul class="nav nav-tabs mb-4">
                <li class="nav-item">
                    <a class="nav-link <?= $tab === 'incoming' ? 'active' : '' ?>" href="?tab=incoming">
                        Incoming <span class="badge bg-danger ms-1"><?= count($incomingRequests) ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $tab === 'outgoing' ? 'active' : '' ?>" href="?tab=outgoing">
                        Outgoing <span class="badge bg-primary ms-1"><?= count($outgoingRequests) ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $tab === 'share_incoming' ? 'active' : '' ?>" href="?tab=share_incoming">
                        Share Incoming <span class="badge bg-info ms-1"><?= count($incomingShares) ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $tab === 'share_outgoing' ? 'active' : '' ?>" href="?tab=share_outgoing">
                        Share Outgoing <span class="badge bg-secondary ms-1"><?= count($outgoingShares) ?></span>
                    </a>
                </li>
            </ul>
            
            <?php if ($tab === 'incoming'): ?>
                <?php if (empty($incomingRequests)): ?>
                <div class="card">
                    <div class="card-body">
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <h5>No incoming requests</h5>
                            <p>When someone requests your items, they'll appear here.</p>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                    <?php foreach ($incomingRequests as $request): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex align-items-start">
                                <img src="<?= getProfileImageUrl($request['sender_image'] ?? null) ?>" 
                                     class="rounded-circle me-3" width="48" height="48" style="object-fit: cover; border: 2px solid #2563eb;">
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="mb-1">
                                                <?= sanitize($request['sender_name']) ?>
                                                <span class="badge bg-light text-dark ms-1"><?= userRoleLabel($request['sender_role'], $request['sender_level'] ?? null) ?></span>
                                            </h6>
                                            <small class="text-muted">wants your <strong><?= sanitize($request['listing_title']) ?></strong></small>
                                        </div>
                                        <span class="badge <?= getStatusBadgeClass($request['status']) ?>"><?= ucfirst($request['status']) ?></span>
                                    </div>
                                    
                                    <?php if ($request['offered_item']): ?>
                                    <div class="mt-2 p-2 bg-light rounded">
                                        <small><strong>Offering:</strong> <?= sanitize($request['offered_item']) ?></small>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($request['message']): ?>
                                    <div class="mt-2">
                                        <small class="text-muted">"<?= sanitize($request['message']) ?>"</small>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <small class="text-muted d-block mt-2"><?= timeAgo($request['created_at']) ?></small>
                                    
                                    <?php if ($request['status'] === 'pending'): ?>
                                    <div class="mt-3">
                                        <button class="btn btn-success btn-sm me-2" onclick="handleExchangeRequest(<?= $request['id'] ?>, 'accept')">
                                            <i class="fas fa-check me-1"></i> Accept
                                        </button>
                                        <button class="btn btn-danger btn-sm" onclick="handleExchangeRequest(<?= $request['id'] ?>, 'reject')">
                                            <i class="fas fa-times me-1"></i> Reject
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($request['status'] === 'accepted'): ?>
                                    <div class="mt-3">
                                        <a href="/BaustExchange/chat.php?user_id=<?= $request['sender_id'] ?>&listing_id=<?= $request['listing_id'] ?>" 
                                           class="btn btn-primary btn-sm">
                                            <i class="fas fa-envelope me-1"></i> Message
                                        </a>
                                        <button class="btn btn-outline-success btn-sm ms-2" onclick="handleExchangeRequest(<?= $request['id'] ?>, 'complete')">
                                            <i class="fas fa-check-double me-1"></i> Mark Complete
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            
            <?php elseif ($tab === 'share_incoming'): ?>
                <?php if (empty($incomingShares)): ?>
                <div class="card">
                    <div class="card-body">
                        <div class="empty-state">
                            <i class="fas fa-share-alt"></i>
                            <h5>No incoming share requests</h5>
                            <p>When someone requests to share your items, they'll appear here.</p>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                    <?php foreach ($incomingShares as $request): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex align-items-start">
                                <img src="<?= getProfileImageUrl($request['requester_image'] ?? null) ?>" 
                                     class="rounded-circle me-3" width="48" height="48" style="object-fit: cover; border: 2px solid #2563eb;">
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="mb-1">
                                                <?= sanitize($request['requester_name']) ?>
                                                <span class="badge bg-light text-dark ms-1"><?= userRoleLabel($request['requester_role'], $request['requester_level'] ?? null) ?></span>
                                            </h6>
                                            <small class="text-muted">wants to share your <strong><?= sanitize($request['listing_title']) ?></strong></small>
                                        </div>
                                        <span class="badge <?= getStatusBadgeClass($request['status']) ?>"><?= ucfirst($request['status']) ?></span>
                                    </div>
                                    
                                    <?php if ($request['duration']): ?>
                                    <div class="mt-2 p-2 bg-light rounded">
                                        <small><strong>Duration needed:</strong> <?= sanitize($request['duration']) ?></small>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($request['message']): ?>
                                    <div class="mt-2">
                                        <small class="text-muted">"<?= sanitize($request['message']) ?>"</small>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <small class="text-muted d-block mt-2"><?= timeAgo($request['created_at']) ?></small>
                                    
                                    <?php if ($request['status'] === 'pending'): ?>
                                    <div class="mt-3">
                                        <button class="btn btn-success btn-sm me-2" onclick="handleShareRequest(<?= $request['id'] ?>, 'share_approve')">
                                            <i class="fas fa-check me-1"></i> Approve
                                        </button>
                                        <button class="btn btn-danger btn-sm" onclick="handleShareRequest(<?= $request['id'] ?>, 'share_reject')">
                                            <i class="fas fa-times me-1"></i> Reject
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($request['status'] === 'approved'): ?>
                                    <div class="mt-3">
                                        <a href="/BaustExchange/chat.php?user_id=<?= $request['requester_id'] ?>&listing_id=<?= $request['listing_id'] ?>" 
                                           class="btn btn-primary btn-sm">
                                            <i class="fas fa-envelope me-1"></i> Message
                                        </a>
                                        <button class="btn btn-outline-success btn-sm ms-2" onclick="handleShareRequest(<?= $request['id'] ?>, 'share_complete')">
                                            <i class="fas fa-check-double me-1"></i> Mark Complete
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            
            <?php elseif ($tab === 'share_outgoing'): ?>
                <?php if (empty($outgoingShares)): ?>
                <div class="card">
                    <div class="card-body">
                        <div class="empty-state">
                            <i class="fas fa-share-alt"></i>
                            <h5>No outgoing share requests</h5>
                            <p>Browse the marketplace to find items you can share!</p>
                            <a href="/BaustExchange/marketplace.php?transaction_type=share" class="btn btn-primary">Find Shareable Items</a>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                    <?php foreach ($outgoingShares as $request): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex align-items-start">
                                <img src="<?= $request['listing_image'] ? '/BaustExchange/uploads/items/' . $request['listing_image'] : '/BaustExchange/assets/images/no-image.png' ?>" 
                                     class="rounded me-3" width="48" height="48" style="object-fit: cover;">
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="mb-1">
                                                You requested to share <strong><?= sanitize($request['listing_title']) ?></strong>
                                            </h6>
                                            <small class="text-muted">from <?= sanitize($request['owner_name']) ?>
                                                <span class="badge bg-light text-dark ms-1"><?= userRoleLabel($request['owner_role'], $request['owner_level'] ?? null) ?></span>
                                            </small>
                                        </div>
                                        <span class="badge <?= getStatusBadgeClass($request['status']) ?>"><?= ucfirst($request['status']) ?></span>
                                    </div>
                                    
                                    <?php if ($request['duration']): ?>
                                    <div class="mt-2 p-2 bg-light rounded">
                                        <small><strong>Duration needed:</strong> <?= sanitize($request['duration']) ?></small>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <small class="text-muted d-block mt-2"><?= timeAgo($request['created_at']) ?></small>
                                    
                                    <?php if ($request['status'] === 'pending'): ?>
                                    <div class="mt-3">
                                        <button class="btn btn-outline-danger btn-sm" onclick="handleShareRequest(<?= $request['id'] ?>, 'share_cancel')">
                                            <i class="fas fa-times me-1"></i> Cancel Request
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($request['status'] === 'approved'): ?>
                                    <div class="mt-3">
                                        <a href="/BaustExchange/chat.php?user_id=<?= $request['owner_id'] ?>&listing_id=<?= $request['listing_id'] ?>" 
                                           class="btn btn-primary btn-sm">
                                            <i class="fas fa-envelope me-1"></i> Message
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            
            <?php else: ?>
                <?php if (empty($outgoingRequests)): ?>
                <div class="card">
                    <div class="card-body">
                        <div class="empty-state">
                            <i class="fas fa-paper-plane"></i>
                            <h5>No outgoing requests</h5>
                            <p>Browse the marketplace to find items you want!</p>
                            <a href="/BaustExchange/marketplace.php" class="btn btn-primary">Browse Marketplace</a>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                    <?php foreach ($outgoingRequests as $request): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex align-items-start">
                                <img src="<?= $request['listing_image'] ? '/BaustExchange/uploads/items/' . $request['listing_image'] : '/BaustExchange/assets/images/no-image.png' ?>" 
                                     class="rounded me-3" width="48" height="48" style="object-fit: cover;">
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="mb-1">
                                                You requested <strong><?= sanitize($request['listing_title']) ?></strong>
                                            </h6>
                                            <small class="text-muted">from <?= sanitize($request['receiver_name']) ?>
                                                <span class="badge bg-light text-dark ms-1"><?= userRoleLabel($request['receiver_role'], $request['receiver_level'] ?? null) ?></span>
                                            </small>
                                        </div>
                                        <span class="badge <?= getStatusBadgeClass($request['status']) ?>"><?= ucfirst($request['status']) ?></span>
                                    </div>
                                    
                                    <?php if ($request['offered_item']): ?>
                                    <div class="mt-2 p-2 bg-light rounded">
                                        <small><strong>Your offer:</strong> <?= sanitize($request['offered_item']) ?></small>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <small class="text-muted d-block mt-2"><?= timeAgo($request['created_at']) ?></small>
                                    
                                    <?php if ($request['status'] === 'pending'): ?>
                                    <div class="mt-3">
                                        <button class="btn btn-outline-danger btn-sm" onclick="handleExchangeRequest(<?= $request['id'] ?>, 'cancel')">
                                            <i class="fas fa-times me-1"></i> Cancel Request
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($request['status'] === 'accepted'): ?>
                                    <div class="mt-3">
                                        <a href="/BaustExchange/chat.php?user_id=<?= $request['receiver_id'] ?>&listing_id=<?= $request['listing_id'] ?>" 
                                           class="btn btn-primary btn-sm">
                                            <i class="fas fa-envelope me-1"></i> Message
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <div class="col-lg-3 d-none d-lg-block">
            <?php include __DIR__ . '/components/right-sidebar.php'; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/components/footer.php'; ?>
