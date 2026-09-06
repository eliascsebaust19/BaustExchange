<?php
$pageTitle = 'Dashboard - BAUST Exchange';
require_once __DIR__ . '/components/auth-check.php';

$userId = $_SESSION['user_id'];

// Get user stats
$myListingsCount = count_rows('listings', 'user_id = ? AND status NOT IN ("removed","rejected")', [$userId]);
$incomingRequests = count_rows('exchange_requests', 'receiver_id = ? AND status = "pending"', [$userId])
    + count_rows('rentals', 'owner_id = ? AND status = "pending"', [$userId])
    + count_rows('shares', 'owner_id = ? AND status = "pending"', [$userId]);
$outgoingRequests = count_rows('exchange_requests', 'sender_id = ? AND status = "pending"', [$userId])
    + count_rows('rentals', 'renter_id = ? AND status = "pending"', [$userId])
    + count_rows('shares', 'requester_id = ? AND status = "pending"', [$userId]);
$completedExchanges = count_rows('exchange_requests', '(sender_id = ? OR receiver_id = ?) AND status = "completed"', [$userId, $userId]);
$activeRentals = count_rows('rentals', '(renter_id = ? OR owner_id = ?) AND status = "active"', [$userId, $userId]);

// Get recent listings
$recentListings = fetchAll(
    "SELECT l.*, c.name as category_name, u.name as owner_name, u.role as owner_role,
     (SELECT image_path FROM listing_images WHERE listing_id = l.id LIMIT 1) as image
     FROM listings l 
     JOIN categories c ON l.category_id = c.id 
     JOIN users u ON l.user_id = u.id 
     WHERE l.status = 'active' 
     ORDER BY l.created_at DESC LIMIT 6"
);

// Get recent exchange requests
$recentRequests = fetchAll(
    "SELECT er.*, l.title as listing_title, u.name as other_user_name 
     FROM exchange_requests er 
     JOIN listings l ON er.listing_id = l.id 
     JOIN users u ON u.id = IF(er.sender_id = ?, er.receiver_id, er.sender_id) 
     WHERE (er.sender_id = ? OR er.receiver_id = ?) 
     ORDER BY er.created_at DESC LIMIT 5",
    [$userId, $userId, $userId]
);

// Get wanted items
$wantedItems = fetchAll(
    "SELECT w.*, c.name as category_name, u.name as user_name 
     FROM wanted_items w 
     JOIN categories c ON w.category_id = c.id 
     JOIN users u ON w.user_id = u.id 
     WHERE w.status = 'active' 
     ORDER BY w.created_at DESC LIMIT 5"
);

include __DIR__ . '/components/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-2 d-none d-lg-block">
            <?php include __DIR__ . '/components/sidebar.php'; ?>
        </div>
        
        <div class="col-lg-7 col-md-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0">Dashboard</h4>
                <a href="/BaustExchange/post-item.php" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Post Item
                </a>
            </div>
            
            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-3 col-6 mb-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-primary mb-1"><?= $myListingsCount ?></h3>
                            <small class="text-muted">My Listings</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-warning mb-1"><?= $incomingRequests ?></h3>
                            <small class="text-muted">Incoming Requests</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-info mb-1"><?= $outgoingRequests ?></h3>
                            <small class="text-muted">Outgoing Requests</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-success mb-1"><?= $completedExchanges ?></h3>
                            <small class="text-muted">Completed</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-primary mb-1"><?= $activeRentals ?></h3>
                            <small class="text-muted">Active Rentals</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Listings -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Recent Listings</h6>
                    <a href="/BaustExchange/marketplace.php" class="btn btn-link btn-sm">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recentListings)): ?>
                    <div class="empty-state">
                        <i class="fas fa-store"></i>
                        <p>No listings yet. Be the first to post!</p>
                    </div>
                    <?php else: ?>
                    <div class="row">
                        <?php foreach ($recentListings as $listing): ?>
                        <div class="col-md-4 mb-3">
                            <div class="card listing-card h-100">
                                <img src="<?= $listing['image'] ? '/BaustExchange/uploads/items/' . $listing['image'] : '/BaustExchange/assets/images/no-image.png' ?>" 
                                     class="card-img-top" alt="<?= sanitize($listing['title']) ?>">
                                <div class="card-body">
                                    <h6 class="card-title"><?= sanitize($listing['title']) ?></h6>
                                    <span class="badge bg-light text-dark mb-2"><?= sanitize($listing['category_name']) ?></span>
                                    <p class="listing-price mb-2"><?= formatPrice($listing['price'], $listing['transaction_type'], $listing['rent_price'] ?? null, $listing['rent_period'] ?? null) ?></p>
                                    <a href="/BaustExchange/item.php?id=<?= $listing['id'] ?>" class="btn btn-outline-primary btn-sm w-100">View Details</a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Wanted Items -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Wanted Items</h6>
                    <a href="/BaustExchange/wanted.php" class="btn btn-link btn-sm">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($wantedItems)): ?>
                    <div class="empty-state">
                        <i class="fas fa-search-dollar"></i>
                        <p>No wanted items yet.</p>
                    </div>
                    <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($wantedItems as $wanted): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1"><?= sanitize($wanted['title']) ?></h6>
                                    <small class="text-muted"><?= sanitize($wanted['category_name']) ?></small>
                                    <?php if ($wanted['budget']): ?>
                                    <small class="text-primary ms-2">Budget: ৳<?= number_format($wanted['budget']) ?></small>
                                    <?php endif; ?>
                                </div>
                                <small class="text-muted"><?= timeAgo($wanted['created_at']) ?></small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 d-none d-lg-block">
            <?php include __DIR__ . '/components/right-sidebar.php'; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/components/footer.php'; ?>
