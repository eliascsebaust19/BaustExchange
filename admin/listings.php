<?php
$pageTitle = 'Manage Listings - BAUST Exchange';
require_once __DIR__ . '/../components/auth-check.php';

if (!isAdmin()) redirect('/BaustExchange/dashboard.php');

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $listingId = (int)$_GET['id'];
    
    $listing = fetch("SELECT * FROM listings WHERE id = ?", [$listingId]);
    if ($listing) {
        switch ($action) {
            case 'approve':
                update('listings', ['status' => 'active'], 'id = ?', [$listingId]);
                createNotification($listing['user_id'], 'listing_approved', 'Your listing "' . $listing['title'] . '" has been approved.');
                logActivity($_SESSION['user_id'], 'admin_action', "Approved listing: {$listing['title']}");
                flash('success', 'Listing approved.');
                break;
            case 'reject':
                update('listings', ['status' => 'rejected'], 'id = ?', [$listingId]);
                createNotification($listing['user_id'], 'listing_rejected', 'Your listing "' . $listing['title'] . '" has been rejected.');
                logActivity($_SESSION['user_id'], 'admin_action', "Rejected listing: {$listing['title']}");
                flash('success', 'Listing rejected.');
                break;
            case 'remove':
                update('listings', ['status' => 'removed'], 'id = ?', [$listingId]);
                logActivity($_SESSION['user_id'], 'admin_action', "Removed listing: {$listing['title']}");
                flash('success', 'Listing removed.');
                break;
            case 'delete':
                delete('listing_images', 'listing_id = ?', [$listingId]);
                delete('listings', 'id = ?', [$listingId]);
                logActivity($_SESSION['user_id'], 'admin_action', "Deleted listing: {$listing['title']}");
                flash('success', 'Listing deleted permanently.');
                break;
        }
    }
    redirect('/BaustExchange/admin/listings.php');
}

// Filters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$category = $_GET['category'] ?? '';

$where = ["1=1"];
$params = [];

if ($search) {
    $where[] = "(l.title LIKE ? OR u.name LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if ($status) {
    $where[] = "l.status = ?";
    $params[] = $status;
}

if ($category) {
    $where[] = "c.slug = ?";
    $params[] = $category;
}

$whereClause = implode(' AND ', $where);
$listings = fetchAll(
    "SELECT l.*, u.name as owner_name, u.email as owner_email, c.name as category_name
     FROM listings l 
     JOIN users u ON l.user_id = u.id 
     JOIN categories c ON l.category_id = c.id 
     WHERE {$whereClause}
     ORDER BY l.created_at DESC",
    $params
);

$categories = fetchAll("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order");

include __DIR__ . '/../components/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-2 d-none d-lg-block">
            <?php include __DIR__ . '/../components/admin/sidebar.php'; ?>
        </div>
        
        <div class="col-lg-10 col-md-12">
            <h4 class="mb-4">Manage Listings</h4>
            
            <?php if ($msg = flash('success')): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= $msg ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <input type="text" class="form-control" name="search" placeholder="Search..." value="<?= sanitize($search) ?>">
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" name="status">
                                <option value="">All Status</option>
                                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="sold" <?= $status === 'sold' ? 'selected' : '' ?>>Sold</option>
                                <option value="exchanged" <?= $status === 'exchanged' ? 'selected' : '' ?>>Exchanged</option>
                                <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="category">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['slug'] ?>" <?= $category === $cat['slug'] ? 'selected' : '' ?>>
                                    <?= sanitize($cat['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                        <div class="col-md-2">
                            <a href="/BaustExchange/admin/listings.php" class="btn btn-outline-secondary w-100">Clear</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Listings Table -->
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Owner</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($listings as $listing): ?>
                                <tr>
                                    <td>
                                        <a href="/BaustExchange/item.php?id=<?= $listing['id'] ?>" class="text-decoration-none">
                                            <?= sanitize($listing['title']) ?>
                                        </a>
                                    </td>
                                    <td><small><?= sanitize($listing['owner_name']) ?></small></td>
                                    <td><span class="badge bg-light text-dark"><?= sanitize($listing['category_name']) ?></span></td>
                                    <td class="small"><?= formatPrice($listing['price'], $listing['transaction_type'], $listing['rent_price'] ?? null, $listing['rent_period'] ?? null) ?></td>
                                    <td><span class="badge <?= getStatusBadgeClass($listing['status']) ?>"><?= ucfirst($listing['status']) ?></span></td>
                                    <td><small class="text-muted"><?= timeAgo($listing['created_at']) ?></small></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <?php if ($listing['status'] === 'pending'): ?>
                                            <a href="?action=approve&id=<?= $listing['id'] ?>" class="btn btn-outline-success" title="Approve">
                                                <i class="fas fa-check"></i>
                                            </a>
                                            <a href="?action=reject&id=<?= $listing['id'] ?>" class="btn btn-outline-warning" title="Reject">
                                                <i class="fas fa-times"></i>
                                            </a>
                                            <?php endif; ?>
                                            <?php if ($listing['status'] !== 'removed'): ?>
                                            <a href="?action=remove&id=<?= $listing['id'] ?>" class="btn btn-outline-secondary" title="Remove"
                                               onclick="return confirm('Remove this listing?')">
                                                <i class="fas fa-eye-slash"></i>
                                            </a>
                                            <?php endif; ?>
                                            <a href="?action=delete&id=<?= $listing['id'] ?>" class="btn btn-outline-danger" title="Delete"
                                               onclick="return confirm('Permanently delete this listing?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../components/footer.php'; ?>
