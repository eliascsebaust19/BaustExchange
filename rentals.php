<?php
$pageTitle = 'Rentals - BAUST Exchange';
require_once __DIR__ . '/components/auth-check.php';

$tab = $_GET['tab'] ?? 'incoming';

// Get incoming rental requests (items I own being requested to rent)
$incomingRentals = fetchAll(
    "SELECT r.*, l.title as listing_title, l.rent_price as listing_rent_price, l.rent_period as listing_rent_period,
     u.name as renter_name, u.role as renter_role, u.student_level as renter_level, u.profile_image as renter_image,
     (SELECT image_path FROM listing_images WHERE listing_id = l.id LIMIT 1) as listing_image
     FROM rentals r 
     JOIN listings l ON r.listing_id = l.id 
     JOIN users u ON r.renter_id = u.id 
     WHERE r.owner_id = ? 
     ORDER BY r.created_at DESC",
    [$_SESSION['user_id']]
);

// Get outgoing rental requests (items I requested to rent)
$outgoingRentals = fetchAll(
    "SELECT r.*, l.title as listing_title, l.rent_price as listing_rent_price, l.rent_period as listing_rent_period,
     u.name as owner_name, u.role as owner_role, u.student_level as owner_level, u.profile_image as owner_image,
     (SELECT image_path FROM listing_images WHERE listing_id = l.id LIMIT 1) as listing_image
     FROM rentals r 
     JOIN listings l ON r.listing_id = l.id 
     JOIN users u ON r.owner_id = u.id 
     WHERE r.renter_id = ? 
     ORDER BY r.created_at DESC",
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
            <h4 class="mb-4">Rentals</h4>
            
            <?php if ($msg = flash('success')): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= $msg ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <ul class="nav nav-tabs mb-4">
                <li class="nav-item">
                    <a class="nav-link <?= $tab === 'incoming' ? 'active' : '' ?>" href="?tab=incoming">
                        Incoming Requests <span class="badge bg-danger ms-1"><?= count($incomingRentals) ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $tab === 'outgoing' ? 'active' : '' ?>" href="?tab=outgoing">
                        My Rentals <span class="badge bg-primary ms-1"><?= count($outgoingRentals) ?></span>
                    </a>
                </li>
            </ul>
            
            <?php if ($tab === 'incoming'): ?>
                <?php if (empty($incomingRentals)): ?>
                <div class="card">
                    <div class="card-body">
                        <div class="empty-state">
                            <i class="fas fa-hand-holding-usd"></i>
                            <h5>No incoming rental requests</h5>
                            <p>When someone requests to rent your items, they'll appear here.</p>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                    <?php foreach ($incomingRentals as $rental): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex align-items-start">
                                <img src="<?= getProfileImageUrl($rental['renter_image'] ?? null) ?>" 
                                     class="rounded-circle me-3" width="48" height="48" style="object-fit: cover; border: 2px solid #2563eb;">
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="mb-1">
                                                <?= sanitize($rental['renter_name']) ?>
                                                <span class="badge bg-light text-dark ms-1"><?= userRoleLabel($rental['renter_role'], $rental['renter_level'] ?? null) ?></span>
                                            </h6>
                                            <small class="text-muted">wants to rent your <strong><?= sanitize($rental['listing_title']) ?></strong></small>
                                        </div>
                                        <span class="badge <?= getStatusBadgeClass($rental['status']) ?>"><?= ucfirst($rental['status']) ?></span>
                                    </div>
                                    
                                    <div class="mt-2 p-2 bg-light rounded">
                                        <small><strong>Rent:</strong> ৳<?= number_format($rental['rent_price']) ?>/<?= ucfirst($rental['rent_period']) ?></small>
                                    </div>
                                    
                                    <?php if ($rental['message']): ?>
                                    <div class="mt-2">
                                        <small class="text-muted">"<?= sanitize($rental['message']) ?>"</small>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <small class="text-muted d-block mt-2"><?= timeAgo($rental['created_at']) ?></small>
                                    
                                    <?php if ($rental['status'] === 'pending'): ?>
                                    <div class="mt-3">
                                        <button class="btn btn-success btn-sm me-2" onclick="handleRequest(<?= $rental['id'] ?>, 'rent_accept')">
                                            <i class="fas fa-check me-1"></i> Accept
                                        </button>
                                        <button class="btn btn-danger btn-sm" onclick="handleRequest(<?= $rental['id'] ?>, 'rent_reject')">
                                            <i class="fas fa-times me-1"></i> Reject
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($rental['status'] === 'active'): ?>
                                    <div class="mt-3">
                                        <a href="/BaustExchange/chat.php?user_id=<?= $rental['renter_id'] ?>&listing_id=<?= $rental['listing_id'] ?>" 
                                           class="btn btn-primary btn-sm">
                                            <i class="fas fa-envelope me-1"></i> Message Renter
                                        </a>
                                        <button class="btn btn-outline-success btn-sm ms-2" onclick="handleRequest(<?= $rental['id'] ?>, 'rent_complete')">
                                            <i class="fas fa-check-double me-1"></i> Mark Returned
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            
            <?php else: ?>
                <?php if (empty($outgoingRentals)): ?>
                <div class="card">
                    <div class="card-body">
                        <div class="empty-state">
                            <i class="fas fa-hand-holding-usd"></i>
                            <h5>No rentals yet</h5>
                            <p>Browse the marketplace to find items available for rent!</p>
                            <a href="/BaustExchange/marketplace.php?transaction_type=rent" class="btn btn-primary">Find Rentals</a>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                    <?php foreach ($outgoingRentals as $rental): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex align-items-start">
                                <img src="<?= $rental['listing_image'] ? '/BaustExchange/uploads/items/' . $rental['listing_image'] : '/BaustExchange/assets/images/no-image.png' ?>" 
                                     class="rounded me-3" width="48" height="48" style="object-fit: cover;">
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="mb-1">
                                                You requested to rent <strong><?= sanitize($rental['listing_title']) ?></strong>
                                            </h6>
                                            <small class="text-muted">from <?= sanitize($rental['owner_name']) ?>
                                                <span class="badge bg-light text-dark ms-1"><?= userRoleLabel($rental['owner_role'], $rental['owner_level'] ?? null) ?></span>
                                            </small>
                                        </div>
                                        <span class="badge <?= getStatusBadgeClass($rental['status']) ?>"><?= ucfirst($rental['status']) ?></span>
                                    </div>
                                    
                                    <div class="mt-2 p-2 bg-light rounded">
                                        <small><strong>Rent:</strong> ৳<?= number_format($rental['rent_price']) ?>/<?= ucfirst($rental['rent_period']) ?></small>
                                    </div>
                                    
                                    <small class="text-muted d-block mt-2"><?= timeAgo($rental['created_at']) ?></small>
                                    
                                    <?php if ($rental['status'] === 'pending'): ?>
                                    <div class="mt-3">
                                        <button class="btn btn-outline-danger btn-sm" onclick="handleRequest(<?= $rental['id'] ?>, 'rent_cancel')">
                                            <i class="fas fa-times me-1"></i> Cancel Request
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($rental['status'] === 'active'): ?>
                                    <div class="mt-3">
                                        <a href="/BaustExchange/chat.php?user_id=<?= $rental['owner_id'] ?>&listing_id=<?= $rental['listing_id'] ?>" 
                                           class="btn btn-primary btn-sm">
                                            <i class="fas fa-envelope me-1"></i> Message Owner
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