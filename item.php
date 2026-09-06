<?php
$pageTitle = 'Item Details - BAUST Exchange';
require_once __DIR__ . '/components/auth-check.php';

$listingId = (int)($_GET['id'] ?? 0);
if ($listingId <= 0) redirect('/BaustExchange/marketplace.php');

$listing = fetch(
    "SELECT l.*, c.name as category_name, c.slug as category_slug,
     u.id as owner_id, u.name as owner_name, u.role as owner_role, u.student_level as owner_student_level, u.profile_image as owner_image, u.department as owner_department
     FROM listings l 
     JOIN categories c ON l.category_id = c.id 
     JOIN users u ON l.user_id = u.id 
     WHERE l.id = ?",
    [$listingId]
);

if (!$listing) redirect('/BaustExchange/marketplace.php');

// Increment views
update('listings', ['views' => $listing['views'] + 1], 'id = ?', [$listingId]);

// Get images
$images = fetchAll("SELECT * FROM listing_images WHERE listing_id = ? ORDER BY created_at", [$listingId]);

// Check if user already requested
$existingRequest = null;
if ($listing['user_id'] != $_SESSION['user_id']) {
    $existingRequest = fetch(
        "SELECT * FROM exchange_requests WHERE listing_id = ? AND sender_id = ? AND status IN ('pending','accepted')",
        [$listingId, $_SESSION['user_id']]
    );
}

include __DIR__ . '/components/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-2 d-none d-lg-block">
            <?php include __DIR__ . '/components/sidebar.php'; ?>
        </div>
        
        <div class="col-lg-7 col-md-8">
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/BaustExchange/marketplace.php">Marketplace</a></li>
                    <li class="breadcrumb-item"><a href="/BaustExchange/marketplace.php?category=<?= $listing['category_slug'] ?>"><?= sanitize($listing['category_name']) ?></a></li>
                    <li class="breadcrumb-item active"><?= sanitize($listing['title']) ?></li>
                </ol>
            </nav>
            
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <?php if (!empty($images)): ?>
                            <div id="listingCarousel" class="carousel slide" data-bs-ride="carousel">
                                <div class="carousel-inner rounded">
                                    <?php foreach ($images as $i => $img): ?>
                                    <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
                                        <img src="/BaustExchange/uploads/items/<?= $img['image_path'] ?>" 
                                             class="d-block w-100" alt="<?= sanitize($listing['title']) ?>"
                                             style="height: 400px; object-fit: cover;">
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php if (count($images) > 1): ?>
                                <button class="carousel-control-prev" type="button" data-bs-target="#listingCarousel" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon"></span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#listingCarousel" data-bs-slide="next">
                                    <span class="carousel-control-next-icon"></span>
                                </button>
                                <?php endif; ?>
                            </div>
                            <?php else: ?>
                            <img src="/BaustExchange/assets/images/no-image.png" class="w-100 rounded" 
                                 alt="No image" style="height: 400px; object-fit: cover;">
                            <?php endif; ?>
                            
                            <!-- Thumbnails -->
                            <?php if (count($images) > 1): ?>
                            <div class="d-flex mt-2 gap-2 overflow-auto">
                                <?php foreach ($images as $i => $img): ?>
                                <img src="/BaustExchange/uploads/items/<?= $img['image_path'] ?>" 
                                     class="rounded" width="60" height="60" style="object-fit: cover; cursor: pointer;"
                                     onclick="goToSlide(<?= $i ?>)">
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge bg-light text-dark"><?= sanitize($listing['category_name']) ?></span>
                                <span class="badge badge-<?= $listing['transaction_type'] ?>">
                                    <?= ucfirst(str_replace('_', ' ', $listing['transaction_type'])) ?>
                                </span>
                            </div>
                            
                            <h4><?= sanitize($listing['title']) ?></h4>
                            
                            <p class="listing-price mb-3"><?= formatPrice($listing['price'], $listing['transaction_type'], $listing['rent_price'] ?? null, $listing['rent_period'] ?? null) ?></p>
                            
                            <?php if ($listing['transaction_type'] === 'rent'): ?>
                            <div class="mb-3">
                                <strong>Rent Price:</strong>
                                <span>৳<?= number_format($listing['rent_price']) ?>/<?= ucfirst($listing['rent_period'] ?? 'day') ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <strong>Condition:</strong>
                                <span class="text-capitalize"><?= str_replace('_', ' ', $listing['condition']) ?></span>
                            </div>
                            
                            <?php if ($listing['exchange_for']): ?>
                            <div class="mb-3">
                                <strong>Looking for:</strong>
                                <?= sanitize($listing['exchange_for']) ?>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($listing['location']): ?>
                            <div class="mb-3">
                                <i class="fas fa-map-marker-alt text-muted me-1"></i>
                                <?= sanitize($listing['location']) ?>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($listing['contact_preference']): ?>
                            <div class="mb-3">
                                <strong>Contact:</strong>
                                <?= sanitize($listing['contact_preference']) ?>
                            </div>
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <small class="text-muted">
                                    <i class="fas fa-eye me-1"></i><?= $listing['views'] ?> views
                                    <i class="fas fa-clock ms-2 me-1"></i><?= timeAgo($listing['created_at']) ?>
                                </small>
                            </div>
                            
                            <?php if ($listing['user_id'] != $_SESSION['user_id']): ?>
                            <?php if (!$existingRequest && !isBlocked()): ?>
                            <button class="btn btn-primary w-100 mb-2" data-bs-toggle="modal" data-bs-target="#requestModal">
                                <i class="fas fa-exchange-alt me-1"></i> Request
                            </button>
                            <?php elseif ($existingRequest): ?>
                            <div class="alert alert-info mb-2">You have already sent a request for this item.</div>
                            <?php endif; ?>
                            
                            <a href="/BaustExchange/chat.php?user_id=<?= $listing['owner_id'] ?>&listing_id=<?= $listing['id'] ?>" 
                               class="btn btn-outline-primary w-100 mb-2">
                                <i class="fas fa-envelope me-1"></i> Contact Owner
                            </a>
                            
                            <button class="btn btn-outline-danger w-100" data-bs-toggle="modal" data-bs-target="#reportModal">
                                <i class="fas fa-flag me-1"></i> Report
                            </button>
                            <?php else: ?>
                            <a href="/BaustExchange/edit-item.php?id=<?= $listing['id'] ?>" class="btn btn-outline-primary w-100 mb-2">
                                <i class="fas fa-edit me-1"></i> Edit Listing
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h6>Description</h6>
                    <p><?= nl2br(sanitize($listing['description'])) ?></p>
                </div>
            </div>
            
            <!-- Owner Info -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">Posted by</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <img src="<?= getProfileImageUrl($listing['owner_image'] ?? null) ?>" 
                             class="rounded-circle me-3" width="50" height="50" style="object-fit: cover; border: 2px solid #2563eb;">
                        <div>
                            <h6 class="mb-0"><?= sanitize($listing['owner_name']) ?></h6>
                            <small class="text-muted">
                                <span class="badge bg-primary"><?= userRoleLabel($listing['owner_role'], $listing['owner_student_level'] ?? null) ?></span>
                                <?php if ($listing['owner_department']): ?>
                                <span class="ms-2"><?= sanitize($listing['owner_department']) ?></span>
                                <?php endif; ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 d-none d-lg-block">
            <?php include __DIR__ . '/components/right-sidebar.php'; ?>
        </div>
    </div>
</div>

<!-- Request Modal -->
<?php if ($listing['user_id'] != $_SESSION['user_id'] && !$existingRequest && !isBlocked()): ?>
<div class="modal fade" id="requestModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php
                    $reqTitle = match($listing['transaction_type']) {
                        'rent' => 'Send Rental Request',
                        'share' => 'Send Sharing Request',
                        'exchange' => 'Send Exchange Request',
                        default => 'Send Purchase Request',
                    };
                    echo $reqTitle;
                ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Message</label>
                    <textarea class="form-control" id="request-message" rows="3" placeholder="Why do you want this item?"></textarea>
                </div>
                <?php if ($listing['transaction_type'] === 'exchange'): ?>
                <div class="mb-3">
                    <label class="form-label">What are you offering?</label>
                    <input type="text" class="form-control" id="offered-item" placeholder="e.g., DSA Book, Calculator, etc.">
                </div>
                <?php elseif ($listing['transaction_type'] === 'rent'): ?>
                <div class="mb-3">
                    <label class="form-label">How long do you need it?</label>
                    <select class="form-select" id="rent-duration" name="rent_duration">
                        <option value="1 day">1 Day</option>
                        <option value="1 week">1 Week</option>
                        <option value="2 weeks">2 Weeks</option>
                        <option value="1 month">1 Month</option>
                        <option value="1 semester">1 Semester</option>
                    </select>
                </div>
                <?php elseif ($listing['transaction_type'] === 'share'): ?>
                <div class="mb-3">
                    <label class="form-label">How long do you need to share this item?</label>
                    <input type="text" class="form-control" id="share-duration" placeholder="e.g., 1 week, 1 month">
                </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="sendRequest(<?= $listing['id'] ?>, '<?= $listing['transaction_type'] ?>')">
                    <i class="fas fa-paper-plane me-1"></i> Send Request
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Report Modal -->
<div class="modal fade" id="reportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Report Listing</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="/BaustExchange/api/reports.php">
                <div class="modal-body">
                    <input type="hidden" name="listing_id" value="<?= $listing['id'] ?>">
                    <div class="mb-3">
                        <label class="form-label">Reason</label>
                        <select class="form-select" name="reason" required>
                            <option value="">Select Reason</option>
                            <option value="fake_listing">Fake Listing</option>
                            <option value="spam">Spam</option>
                            <option value="wrong_info">Wrong Information</option>
                            <option value="offensive">Offensive Content</option>
                            <option value="suspicious_user">Suspicious User</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description (optional)</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Submit Report</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function goToSlide(index) {
    const carousel = bootstrap.Carousel.getInstance(document.getElementById('listingCarousel'));
    carousel.to(index);
}
</script>

<?php include __DIR__ . '/components/footer.php'; ?>
