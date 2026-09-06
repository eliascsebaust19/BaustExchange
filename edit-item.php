<?php
$pageTitle = 'Edit Item - BAUST Exchange';
require_once __DIR__ . '/components/auth-check.php';

$listingId = (int)($_GET['id'] ?? 0);
if ($listingId <= 0) redirect('/BaustExchange/my-listings.php');

$listing = fetch("SELECT * FROM listings WHERE id = ?", [$listingId]);
if (!$listing || !canEditListing($listing)) redirect('/BaustExchange/my-listings.php');

$images = fetchAll("SELECT * FROM listing_images WHERE listing_id = ?", [$listingId]);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        flash('error', 'Invalid security token.');
        redirect("/BaustExchange/edit-item.php?id={$listingId}");
    }
    
    $title = trim($_POST['title'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $condition = $_POST['condition'] ?? 'good';
    $transactionType = $_POST['transaction_type'] ?? 'sell';
    $price = $_POST['price'] !== '' ? (float)$_POST['price'] : null;
    $rentPrice = $_POST['rent_price'] !== '' ? (float)$_POST['rent_price'] : null;
    $rentPeriod = $_POST['rent_period'] ?? null;
    $exchangeFor = trim($_POST['exchange_for'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $contactPreference = trim($_POST['contact_preference'] ?? '');
    
    $errors = [];
    if (empty($title)) $errors[] = 'Title is required.';
    if ($categoryId <= 0) $errors[] = 'Category is required.';
    if (empty($description)) $errors[] = 'Description is required.';
    
    if (empty($errors)) {
        update('listings', [
            'category_id' => $categoryId,
            'title' => $title,
            'description' => $description,
            'condition' => $condition,
            'transaction_type' => $transactionType,
            'price' => $price,
            'rent_price' => $rentPrice,
            'rent_period' => $rentPeriod,
            'exchange_for' => $exchangeFor ?: null,
            'location' => $location ?: null,
            'contact_preference' => $contactPreference ?: null,
        ], 'id = ?', [$listingId]);
        
        // Handle new image uploads
        if (!empty($_FILES['images']['name'][0])) {
            foreach ($_FILES['images']['name'] as $key => $name) {
                if ($_FILES['images']['error'][$key] !== UPLOAD_ERR_OK) continue;
                
                $fileSize = $_FILES['images']['size'][$key];
                $fileType = $_FILES['images']['type'][$key];
                $fileExt = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                
                if ($fileSize > MAX_FILE_SIZE) continue;
                if (!in_array($fileType, ALLOWED_IMAGE_TYPES)) continue;
                if (!in_array($fileExt, ALLOWED_IMAGE_EXTENSIONS)) continue;
                
                $newFilename = uniqid('item_', true) . '.' . $fileExt;
                $uploadPath = ITEMS_UPLOAD_PATH . $newFilename;
                
                if (move_uploaded_file($_FILES['images']['tmp_name'][$key], $uploadPath)) {
                    insert('listing_images', [
                        'listing_id' => $listingId,
                        'image_path' => $newFilename,
                    ]);
                }
            }
        }
        
        // Handle image deletion
        if (!empty($_POST['delete_images'])) {
            foreach ($_POST['delete_images'] as $imageId) {
                $image = fetch("SELECT * FROM listing_images WHERE id = ? AND listing_id = ?", [$imageId, $listingId]);
                if ($image) {
                    $filePath = ITEMS_UPLOAD_PATH . $image['image_path'];
                    if (file_exists($filePath)) unlink($filePath);
                    delete('listing_images', 'id = ?', [$imageId]);
                }
            }
        }
        
        logActivity($_SESSION['user_id'], 'listing_updated', 'Updated listing: ' . $title);
        flash('success', 'Listing updated successfully!');
        redirect("/BaustExchange/item.php?id={$listingId}");
    } else {
        flash('error', implode(' ', $errors));
    }
}

$categories = fetchAll("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order");

include __DIR__ . '/components/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-2 d-none d-lg-block">
            <?php include __DIR__ . '/components/sidebar.php'; ?>
        </div>
        
        <div class="col-lg-7 col-md-8">
            <h4 class="mb-4">Edit Listing</h4>
            
            <?php if ($msg = flash('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= $msg ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <?= csrfField() ?>
                        
                        <div class="mb-3">
                            <label class="form-label">Item Name</label>
                            <input type="text" class="form-control" name="title" required maxlength="255"
                                   value="<?= sanitize($listing['title']) ?>">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Category</label>
                                <select class="form-select" name="category_id" required>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= $listing['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                                        <?= sanitize($cat['name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Condition</label>
                                <select class="form-select" name="condition" required>
                                    <option value="new" <?= $listing['condition'] === 'new' ? 'selected' : '' ?>>New</option>
                                    <option value="like_new" <?= $listing['condition'] === 'like_new' ? 'selected' : '' ?>>Like New</option>
                                    <option value="good" <?= $listing['condition'] === 'good' ? 'selected' : '' ?>>Good</option>
                                    <option value="used" <?= $listing['condition'] === 'used' ? 'selected' : '' ?>>Used</option>
                                    <option value="damaged" <?= $listing['condition'] === 'damaged' ? 'selected' : '' ?>>Damaged</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="4" required><?= sanitize($listing['description']) ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Transaction Type</label>
                            <select class="form-select" name="transaction_type" required id="transaction-type">
                                <option value="sell" <?= $listing['transaction_type'] === 'sell' ? 'selected' : '' ?>>Sell</option>
                                <option value="buy" <?= $listing['transaction_type'] === 'buy' ? 'selected' : '' ?>>Buy / Wanted to Buy</option>
                                <option value="share" <?= $listing['transaction_type'] === 'share' ? 'selected' : '' ?>>Share</option>
                                <option value="exchange" <?= $listing['transaction_type'] === 'exchange' ? 'selected' : '' ?>>Exchange</option>
                                <option value="rent" <?= $listing['transaction_type'] === 'rent' ? 'selected' : '' ?>>Rent</option>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3" id="price-field">
                                <label class="form-label">Price (৳)</label>
                                <input type="number" class="form-control" name="price" min="0" step="0.01"
                                       value="<?= $listing['price'] ?>">
                            </div>
                            <div class="col-md-6 mb-3" id="rent-price-field">
                                <label class="form-label">Rent Price (৳) <small class="text-muted">per period</small></label>
                                <input type="number" class="form-control" name="rent_price" min="0" step="0.01"
                                       value="<?= $listing['rent_price'] ?>">
                            </div>
                            <div class="col-md-6 mb-3" id="rent-period-field">
                                <label class="form-label">Rent Period</label>
                                <select class="form-select" name="rent_period">
                                    <option value="">Select Period</option>
                                    <option value="hour" <?= $listing['rent_period'] === 'hour' ? 'selected' : '' ?>>Per Hour</option>
                                    <option value="day" <?= ($listing['rent_period'] ?? '') === 'day' ? 'selected' : '' ?>>Per Day</option>
                                    <option value="week" <?= $listing['rent_period'] === 'week' ? 'selected' : '' ?>>Per Week</option>
                                    <option value="month" <?= $listing['rent_period'] === 'month' ? 'selected' : '' ?>>Per Month</option>
                                    <option value="semester" <?= $listing['rent_period'] === 'semester' ? 'selected' : '' ?>>Per Semester</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3" id="exchange-for-field">
                                <label class="form-label">What are you looking for?</label>
                                <input type="text" class="form-control" name="exchange_for" maxlength="255"
                                       value="<?= sanitize($listing['exchange_for']) ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Location</label>
                                <input type="text" class="form-control" name="location" maxlength="255"
                                       value="<?= sanitize($listing['location']) ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Contact Preference</label>
                                <input type="text" class="form-control" name="contact_preference" maxlength="255"
                                       value="<?= sanitize($listing['contact_preference']) ?>">
                            </div>
                        </div>
                        
                        <!-- Current Images -->
                        <?php if (!empty($images)): ?>
                        <div class="mb-3">
                            <label class="form-label">Current Images</label>
                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach ($images as $img): ?>
                                <div class="position-relative">
                                    <img src="/BaustExchange/uploads/items/<?= $img['image_path'] ?>" 
                                         class="image-preview" alt="Image">
                                    <label class="position-absolute" style="top: -5px; right: -5px;">
                                        <input type="checkbox" name="delete_images[]" value="<?= $img['id'] ?>" 
                                               class="d-none" onchange="this.parentElement.parentElement.style.opacity = this.checked ? '0.3' : '1'">
                                        <span class="badge bg-danger cursor-pointer">×</span>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <small class="text-muted">Check images to delete them</small>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label class="form-label">Add New Images</label>
                            <input type="file" class="form-control" name="images[]" multiple accept="image/*">
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save Changes</button>
                            <a href="/BaustExchange/item.php?id=<?= $listingId ?>" class="btn btn-outline-secondary">Cancel</a>
                        </div>
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
document.getElementById('transaction-type').addEventListener('change', function() {
    const type = this.value;
    document.getElementById('price-field').style.display = (type === 'sell' || type === 'buy') ? 'block' : 'none';
    document.getElementById('rent-price-field').style.display = (type === 'rent') ? 'block' : 'none';
    document.getElementById('rent-period-field').style.display = (type === 'rent') ? 'block' : 'none';
    document.getElementById('exchange-for-field').style.display = (type === 'exchange') ? 'block' : 'none';
});
document.getElementById('transaction-type').dispatchEvent(new Event('change'));
</script>

<?php include __DIR__ . '/components/footer.php'; ?>
