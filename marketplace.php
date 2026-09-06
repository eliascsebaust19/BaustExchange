<?php
$pageTitle = 'Marketplace - BAUST Exchange';
require_once __DIR__ . '/components/auth-check.php';

// Get filters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$condition = $_GET['condition'] ?? '';
$transactionType = $_GET['transaction_type'] ?? '';
$minPrice = $_GET['min_price'] ?? '';
$maxPrice = $_GET['max_price'] ?? '';
$department = $_GET['department'] ?? '';
$userRole = $_GET['user_role'] ?? '';
$studentLevel = $_GET['student_level'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = ITEMS_PER_PAGE;
$offset = ($page - 1) * $perPage;

// Build query
$where = ["l.status = 'active'"];
$params = [];

if ($search) {
    $where[] = "(l.title LIKE ? OR l.description LIKE ? OR c.name LIKE ? OR l.exchange_for LIKE ?)";
    $searchTerm = "%{$search}%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

if ($category) {
    $where[] = "c.slug = ?";
    $params[] = $category;
}

if ($condition) {
    $where[] = "l.condition = ?";
    $params[] = $condition;
}

if ($transactionType) {
    $where[] = "l.transaction_type = ?";
    $params[] = $transactionType;
}

if ($minPrice !== '') {
    $where[] = "l.price >= ?";
    $params[] = $minPrice;
}

if ($maxPrice !== '') {
    $where[] = "l.price <= ?";
    $params[] = $maxPrice;
}

if ($department) {
    $where[] = "u.department = ?";
    $params[] = $department;
}

if ($userRole) {
    $where[] = "u.role = ?";
    $params[] = $userRole;
}

if ($studentLevel) {
    $where[] = "u.student_level = ?";
    $params[] = $studentLevel;
}

$whereClause = implode(' AND ', $where);

// Get total count
$totalQuery = "SELECT COUNT(*) as count FROM listings l 
               JOIN categories c ON l.category_id = c.id 
               JOIN users u ON l.user_id = u.id 
               WHERE {$whereClause}";
$totalResult = fetch($totalQuery, $params);
$totalItems = $totalResult['count'];
$totalPages = ceil($totalItems / $perPage);

// Get listings
$query = "SELECT l.*, c.name as category_name, c.slug as category_slug, 
          u.name as owner_name, u.role as owner_role, u.student_level as owner_student_level, u.profile_image as owner_image,
          (SELECT image_path FROM listing_images WHERE listing_id = l.id LIMIT 1) as image
          FROM listings l 
          JOIN categories c ON l.category_id = c.id 
          JOIN users u ON l.user_id = u.id 
          WHERE {$whereClause}
          ORDER BY l.created_at DESC 
          LIMIT {$perPage} OFFSET {$offset}";
$listings = fetchAll($query, $params);

// Get categories for filter
$categories = fetchAll("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order");

include __DIR__ . '/components/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-2 d-none d-lg-block">
            <?php include __DIR__ . '/components/sidebar.php'; ?>
        </div>
        
        <div class="col-lg-7 col-md-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0">Marketplace</h4>
                <span class="text-muted"><?= $totalItems ?> items found</span>
            </div>
            
            <!-- Search Bar (Mobile) -->
            <div class="d-lg-none mb-3">
                <form action="/BaustExchange/marketplace.php" method="GET">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" placeholder="Search items..." value="<?= sanitize($search) ?>">
                        <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                    </div>
                </form>
            </div>
            
            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-header" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                    <h6 class="mb-0">
                        <i class="fas fa-filter me-1"></i> Filters
                        <i class="fas fa-chevron-down float-end"></i>
                    </h6>
                </div>
                <div class="collapse show" id="filterCollapse">
                    <div class="card-body">
                        <form action="/BaustExchange/marketplace.php" method="GET">
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label class="form-label small">Category</label>
                                    <select name="category" class="form-select form-select-sm">
                                        <option value="">All Categories</option>
                                        <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['slug'] ?>" <?= $category === $cat['slug'] ? 'selected' : '' ?>>
                                            <?= sanitize($cat['name']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label small">Condition</label>
                                    <select name="condition" class="form-select form-select-sm">
                                        <option value="">Any Condition</option>
                                        <option value="new" <?= $condition === 'new' ? 'selected' : '' ?>>New</option>
                                        <option value="like_new" <?= $condition === 'like_new' ? 'selected' : '' ?>>Like New</option>
                                        <option value="good" <?= $condition === 'good' ? 'selected' : '' ?>>Good</option>
                                        <option value="used" <?= $condition === 'used' ? 'selected' : '' ?>>Used</option>
                                        <option value="damaged" <?= $condition === 'damaged' ? 'selected' : '' ?>>Damaged</option>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label small">Transaction Type</label>
                                    <select name="transaction_type" class="form-select form-select-sm">
                                        <option value="">All Types</option>
                                        <option value="sell" <?= $transactionType === 'sell' ? 'selected' : '' ?>>Sell</option>
                                        <option value="buy" <?= $transactionType === 'buy' ? 'selected' : '' ?>>Buy</option>
                                        <option value="share" <?= $transactionType === 'share' ? 'selected' : '' ?>>Share</option>
                                        <option value="exchange" <?= $transactionType === 'exchange' ? 'selected' : '' ?>>Exchange</option>
                                        <option value="rent" <?= $transactionType === 'rent' ? 'selected' : '' ?>>Rent</option>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label small">Price Range</label>
                                    <div class="d-flex gap-2">
                                        <input type="number" class="form-control form-control-sm" name="min_price" placeholder="Min" value="<?= sanitize($minPrice) ?>">
                                        <input type="number" class="form-control form-control-sm" name="max_price" placeholder="Max" value="<?= sanitize($maxPrice) ?>">
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label small">User Role</label>
                                    <select name="user_role" class="form-select form-select-sm">
                                        <option value="">All Roles</option>
                                        <option value="student" <?= $userRole === 'student' ? 'selected' : '' ?>>Student</option>
                                        <option value="teacher" <?= $userRole === 'teacher' ? 'selected' : '' ?>>Teacher</option>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label small">Student Level</label>
                                    <select name="student_level" class="form-select form-select-sm">
                                        <option value="">All Levels</option>
                                        <option value="senior" <?= $studentLevel === 'senior' ? 'selected' : '' ?>>Senior</option>
                                        <option value="junior" <?= $studentLevel === 'junior' ? 'selected' : '' ?>>Junior</option>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label small">Department</label>
                                    <input type="text" class="form-control form-control-sm" name="department" placeholder="Department" value="<?= sanitize($department) ?>">
                                </div>
                                <div class="col-md-6 mb-3 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary btn-sm me-2"><i class="fas fa-search me-1"></i> Apply</button>
                                    <a href="/BaustExchange/marketplace.php" class="btn btn-outline-secondary btn-sm">Clear</a>
                                </div>
                            </div>
                            <?php if ($search): ?>
                            <input type="hidden" name="search" value="<?= sanitize($search) ?>">
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Listings Grid -->
            <?php if (empty($listings)): ?>
            <div class="card">
                <div class="card-body">
                    <div class="empty-state">
                        <i class="fas fa-store"></i>
                        <h5>No listings found</h5>
                        <p>Try adjusting your filters or search terms.</p>
                        <a href="/BaustExchange/post-item.php" class="btn btn-primary">Post an Item</a>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="row">
                <?php foreach ($listings as $listing): ?>
                <div class="col-md-4 mb-4">
                    <div class="card listing-card h-100">
                        <img src="<?= $listing['image'] ? '/BaustExchange/uploads/items/' . $listing['image'] : '/BaustExchange/assets/images/no-image.png' ?>" 
                             class="card-img-top" alt="<?= sanitize($listing['title']) ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge bg-light text-dark"><?= sanitize($listing['category_name']) ?></span>
                                <span class="badge badge-<?= $listing['transaction_type'] ?>">
                                    <?= ucfirst(str_replace('_', ' ', $listing['transaction_type'])) ?>
                                </span>
                            </div>
                            <h6 class="card-title"><?= sanitize($listing['title']) ?></h6>
                            <p class="listing-price mb-2"><?= formatPrice($listing['price'], $listing['transaction_type'], $listing['rent_price'] ?? null, $listing['rent_period'] ?? null) ?></p>
                            <div class="d-flex align-items-center mb-2">
                                <img src="<?= getProfileImageUrl($listing['owner_image'] ?? null) ?>" 
                                     class="rounded-circle me-2" width="24" height="24" style="object-fit: cover;">
                                <small class="text-muted">
                                    <?= sanitize($listing['owner_name']) ?>
                                    <span class="badge bg-light text-dark ms-1"><?= userRoleLabel($listing['owner_role'], $listing['owner_student_level'] ?? null) ?></span>
                                </small>
                            </div>
                            <a href="/BaustExchange/item.php?id=<?= $listing['id'] ?>" class="btn btn-outline-primary btn-sm w-100">View Details</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <nav>
                <ul class="pagination justify-content-center">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">Previous</a>
                    </li>
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                    </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next</a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <div class="col-lg-3 d-none d-lg-block">
            <?php include __DIR__ . '/components/right-sidebar.php'; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/components/footer.php'; ?>
