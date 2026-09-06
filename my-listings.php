<?php
$pageTitle = 'My Listings - BAUST Exchange';
require_once __DIR__ . '/components/auth-check.php';

// Handle delete
if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    $listing = fetch("SELECT * FROM listings WHERE id = ?", [$deleteId]);
    if ($listing && canDeleteListing($listing)) {
        delete('listing_images', 'listing_id = ?', [$deleteId]);
        delete('listings', 'id = ?', [$deleteId]);
        logActivity($_SESSION['user_id'], 'listing_deleted', 'Deleted listing: ' . $listing['title']);
        flash('success', 'Listing deleted successfully.');
    }
    redirect('/BaustExchange/my-listings.php');
}

// Handle status update
if (isset($_GET['status']) && isset($_GET['listing_id'])) {
    $status = $_GET['status'];
    $updateId = (int)$_GET['listing_id'];
    $allowedStatuses = ['sold', 'exchanged', 'shared', 'rented'];
    if (in_array($status, $allowedStatuses)) {
        $listing = fetch("SELECT * FROM listings WHERE id = ?", [$updateId]);
        if ($listing && canEditListing($listing)) {
            update('listings', ['status' => $status], 'id = ?', [$updateId]);
            logActivity($_SESSION['user_id'], 'listing_status_updated', "Listing marked as {$status}: " . $listing['title']);
            flash('success', 'Listing status updated.');
        }
    }
    redirect('/BaustExchange/my-listings.php');
}

$listings = fetchAll(
    "SELECT l.*, c.name as category_name,
     (SELECT image_path FROM listing_images WHERE listing_id = l.id LIMIT 1) as image
     FROM listings l 
     JOIN categories c ON l.category_id = c.id 
     WHERE l.user_id = ? 
     ORDER BY l.created_at DESC",
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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0">My Listings</h4>
                <a href="/BaustExchange/post-item.php" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Post Item
                </a>
            </div>
            
            <?php if ($msg = flash('success')): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= $msg ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if (empty($listings)): ?>
            <div class="card">
                <div class="card-body">
                    <div class="empty-state">
                        <i class="fas fa-list"></i>
                        <h5>No listings yet</h5>
                        <p>Start by posting your first item!</p>
                        <a href="/BaustExchange/post-item.php" class="btn btn-primary">Post Item</a>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Item</th>
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
                                <div class="d-flex align-items-center">
                                    <img src="<?= $listing['image'] ? '/BaustExchange/uploads/items/' . $listing['image'] : '/BaustExchange/assets/images/no-image.png' ?>" 
                                         class="rounded me-2" width="40" height="40" style="object-fit: cover;">
                                    <a href="/BaustExchange/item.php?id=<?= $listing['id'] ?>" class="text-decoration-none">
                                        <?= sanitize($listing['title']) ?>
                                    </a>
                                </div>
                            </td>
                            <td><span class="badge bg-light text-dark"><?= sanitize($listing['category_name']) ?></span></td>
                            <td class="listing-price small"><?= formatPrice($listing['price'], $listing['transaction_type'], $listing['rent_price'] ?? null, $listing['rent_period'] ?? null) ?></td>
                            <td><span class="badge <?= getStatusBadgeClass($listing['status']) ?>"><?= ucfirst($listing['status']) ?></span></td>
                            <td><small class="text-muted"><?= timeAgo($listing['created_at']) ?></small></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="/BaustExchange/item.php?id=<?= $listing['id'] ?>" class="btn btn-outline-primary" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if ($listing['status'] === 'active'): ?>
                                    <a href="/BaustExchange/edit-item.php?id=<?= $listing['id'] ?>" class="btn btn-outline-secondary" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-success dropdown-toggle" data-bs-toggle="dropdown" title="Mark as">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="?status=sold&listing_id=<?= $listing['id'] ?>">Mark as Sold</a></li>
                                            <li><a class="dropdown-item" href="?status=exchanged&listing_id=<?= $listing['id'] ?>">Mark as Exchanged</a></li>
                                            <li><a class="dropdown-item" href="?status=shared&listing_id=<?= $listing['id'] ?>">Mark as Shared</a></li>
                                            <li><a class="dropdown-item" href="?status=rented&listing_id=<?= $listing['id'] ?>">Mark as Rented</a></li>
                                        </ul>
                                    </div>
                                    <button class="btn btn-outline-danger" onclick="confirmDelete(<?= $listing['id'] ?>)" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="col-lg-3 d-none d-lg-block">
            <?php include __DIR__ . '/components/right-sidebar.php'; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/components/footer.php'; ?>
