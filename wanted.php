<?php
$pageTitle = 'Wanted Items - BAUST Exchange';
require_once __DIR__ . '/components/auth-check.php';

$wantedItems = fetchAll(
    "SELECT w.*, c.name as category_name, u.name as user_name, u.role as user_role, u.student_level as user_student_level, u.profile_image as user_image
     FROM wanted_items w 
     JOIN categories c ON w.category_id = c.id 
     JOIN users u ON w.user_id = u.id 
     WHERE w.status = 'active' 
     ORDER BY w.created_at DESC"
);

// Simple matching - find listings that match wanted items
$matches = [];
$myListings = fetchAll(
    "SELECT l.*, c.name as category_name FROM listings l 
     JOIN categories c ON l.category_id = c.id 
     WHERE l.user_id = ? AND l.status = 'active'",
    [$_SESSION['user_id']]
);

foreach ($wantedItems as $wanted) {
    if ($wanted['user_id'] == $_SESSION['user_id']) continue;
    
    foreach ($myListings as $listing) {
        $match = false;
        
        // Category match
        if ($listing['category_id'] == $wanted['category_id']) {
            $match = true;
        }
        
        // Title keyword match
        $wantedWords = explode(' ', strtolower($wanted['title']));
        $listingTitle = strtolower($listing['title']);
        foreach ($wantedWords as $word) {
            if (strlen($word) > 2 && strpos($listingTitle, $word) !== false) {
                $match = true;
                break;
            }
        }
        
        // Exchange for match
        if ($listing['exchange_for'] && strpos(strtolower($listing['exchange_for']), strtolower($wanted['title'])) !== false) {
            $match = true;
        }
        
        if ($match) {
            $matches[] = [
                'wanted' => $wanted,
                'listing' => $listing,
            ];
        }
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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0">Wanted Items</h4>
                <a href="/BaustExchange/post-wanted.php" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Add Wanted Item
                </a>
            </div>
            
            <!-- Matching Items -->
            <?php if (!empty($matches)): ?>
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="fas fa-magic me-1"></i> Possible Exchange Matches</h6>
                </div>
                <div class="card-body">
                    <?php foreach ($matches as $match): ?>
                    <div class="d-flex align-items-center justify-content-between p-2 bg-light rounded mb-2">
                        <div>
                            <small><strong><?= sanitize($match['wanted']['title']) ?></strong> wanted by <?= sanitize($match['wanted']['user_name']) ?></small>
                            <br>
                            <small class="text-muted">Your listing: <strong><?= sanitize($match['listing']['title']) ?></strong></small>
                        </div>
                        <a href="/BaustExchange/item.php?id=<?= $match['listing']['id'] ?>" class="btn btn-outline-success btn-sm">View Match</a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- All Wanted Items -->
            <?php if (empty($wantedItems)): ?>
            <div class="card">
                <div class="card-body">
                    <div class="empty-state">
                        <i class="fas fa-search-dollar"></i>
                        <h5>No wanted items yet</h5>
                        <p>Be the first to post what you're looking for!</p>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="row">
                <?php foreach ($wantedItems as $wanted): ?>
                <div class="col-md-6 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1"><?= sanitize($wanted['title']) ?></h6>
                                    <span class="badge bg-light text-dark mb-2"><?= sanitize($wanted['category_name']) ?></span>
                                    <?php if ($wanted['description']): ?>
                                    <p class="small text-muted mb-2"><?= sanitize($wanted['description']) ?></p>
                                    <?php endif; ?>
                                    <?php if ($wanted['budget']): ?>
                                    <p class="mb-2"><strong>Budget:</strong> ৳<?= number_format($wanted['budget']) ?></p>
                                    <?php endif; ?>
                                    <div class="d-flex align-items-center">
                                        <img src="<?= $wanted['user_image'] ?? '/BaustExchange/assets/images/default-avatar.png' ?>" 
                                             class="rounded-circle me-2" width="24" height="24" style="object-fit: cover;">
                                        <small class="text-muted">
                                            <?= sanitize($wanted['user_name']) ?>
                                            <span class="badge bg-light text-dark ms-1"><?= userRoleLabel($wanted['user_role'], $wanted['user_student_level'] ?? null) ?></span>
                                        </small>
                                    </div>
                                </div>
                                <small class="text-muted"><?= timeAgo($wanted['created_at']) ?></small>
                            </div>
                        </div>
                    </div>
                </div>
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
