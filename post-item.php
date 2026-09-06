<?php
$pageTitle = 'Post Item - BAUST Exchange';
require_once __DIR__ . '/components/auth-check.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        flash('error', 'Invalid security token. Please try again.');
        redirect('/BaustExchange/post-item.php');
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
    
    // Validation
    $errors = [];
    if (empty($title)) $errors[] = 'Title is required.';
    if ($categoryId <= 0) $errors[] = 'Category is required.';
    if (empty($description)) $errors[] = 'Description is required.';
    if (!in_array($condition, ['new', 'like_new', 'good', 'used', 'damaged'])) $errors[] = 'Invalid condition.';
    if (!in_array($transactionType, ['sell', 'buy', 'share', 'exchange', 'rent'])) $errors[] = 'Invalid transaction type.';
    if (($transactionType === 'sell' || $transactionType === 'buy') && ($price === null || $price <= 0)) $errors[] = 'Price is required for sell/buy items.';
    if ($transactionType === 'rent' && ($rentPrice === null || $rentPrice <= 0)) $errors[] = 'Rent price is required for rent items.';
    
    if (empty($errors)) {
        // Create listing
        $listingId = insert('listings', [
            'user_id' => $_SESSION['user_id'],
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
            'status' => 'active', // Auto-approve for now
        ]);
        
        // Handle image uploads
        if (!empty($_FILES['images']['name'][0])) {
            $uploadDir = ITEMS_UPLOAD_PATH;
            foreach ($_FILES['images']['name'] as $key => $name) {
                if ($_FILES['images']['error'][$key] !== UPLOAD_ERR_OK) continue;
                
                $fileSize = $_FILES['images']['size'][$key];
                $fileType = $_FILES['images']['type'][$key];
                $fileExt = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                
                if ($fileSize > MAX_FILE_SIZE) continue;
                if (!in_array($fileType, ALLOWED_IMAGE_TYPES)) continue;
                if (!in_array($fileExt, ALLOWED_IMAGE_EXTENSIONS)) continue;
                
                $newFilename = uniqid('item_', true) . '.' . $fileExt;
                $uploadPath = $uploadDir . $newFilename;
                
                if (move_uploaded_file($_FILES['images']['tmp_name'][$key], $uploadPath)) {
                    insert('listing_images', [
                        'listing_id' => $listingId,
                        'image_path' => $newFilename,
                    ]);
                }
            }
        }
        
        logActivity($_SESSION['user_id'], 'listing_created', 'Created listing: ' . $title);
        flash('success', 'Listing created successfully!');
        redirect('/BaustExchange/item.php?id=' . $listingId);
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
            <h4 class="mb-4">Post an Item</h4>
            
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
                            <label class="form-label">Item Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="title" required maxlength="255"
                                   value="<?= sanitize($_POST['title'] ?? '') ?>">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Category <span class="text-danger">*</span></label>
                                <select class="form-select" name="category_id" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= ($_POST['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                        <?= sanitize($cat['name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Condition <span class="text-danger">*</span></label>
                                <select class="form-select" name="condition" required>
                                    <option value="new" <?= ($_POST['condition'] ?? '') === 'new' ? 'selected' : '' ?>>New</option>
                                    <option value="like_new" <?= ($_POST['condition'] ?? '') === 'like_new' ? 'selected' : '' ?>>Like New</option>
                                    <option value="good" <?= ($_POST['condition'] ?? 'good') === 'good' ? 'selected' : '' ?>>Good</option>
                                    <option value="used" <?= ($_POST['condition'] ?? '') === 'used' ? 'selected' : '' ?>>Used</option>
                                    <option value="damaged" <?= ($_POST['condition'] ?? '') === 'damaged' ? 'selected' : '' ?>>Damaged</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="description" rows="4" required><?= sanitize($_POST['description'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Transaction Type <span class="text-danger">*</span></label>
                            <select class="form-select" name="transaction_type" required id="transaction-type">
                                <option value="sell" <?= ($_POST['transaction_type'] ?? '') === 'sell' ? 'selected' : '' ?>>Sell</option>
                                <option value="buy" <?= ($_POST['transaction_type'] ?? '') === 'buy' ? 'selected' : '' ?>>Buy / Wanted to Buy</option>
                                <option value="share" <?= ($_POST['transaction_type'] ?? '') === 'share' ? 'selected' : '' ?>>Share</option>
                                <option value="exchange" <?= ($_POST['transaction_type'] ?? '') === 'exchange' ? 'selected' : '' ?>>Exchange</option>
                                <option value="rent" <?= ($_POST['transaction_type'] ?? '') === 'rent' ? 'selected' : '' ?>>Rent</option>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3" id="price-field">
                                <label class="form-label">Price (৳)</label>
                                <input type="number" class="form-control" name="price" min="0" step="0.01"
                                       value="<?= sanitize($_POST['price'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3" id="rent-price-field">
                                <label class="form-label">Rent Price (৳) <small class="text-muted">per period</small></label>
                                <input type="number" class="form-control" name="rent_price" min="0" step="0.01"
                                       value="<?= sanitize($_POST['rent_price'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3" id="rent-period-field">
                                <label class="form-label">Rent Period</label>
                                <select class="form-select" name="rent_period">
                                    <option value="">Select Period</option>
                                    <option value="hour" <?= ($_POST['rent_period'] ?? '') === 'hour' ? 'selected' : '' ?>>Per Hour</option>
                                    <option value="day" <?= ($_POST['rent_period'] ?? '') === 'day' ? 'selected' : '' ?>>Per Day</option>
                                    <option value="week" <?= ($_POST['rent_period'] ?? '') === 'week' ? 'selected' : '' ?>>Per Week</option>
                                    <option value="month" <?= ($_POST['rent_period'] ?? '') === 'month' ? 'selected' : '' ?>>Per Month</option>
                                    <option value="semester" <?= ($_POST['rent_period'] ?? '') === 'semester' ? 'selected' : '' ?>>Per Semester</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3" id="exchange-for-field">
                                <label class="form-label">What are you looking for in exchange?</label>
                                <input type="text" class="form-control" name="exchange_for" maxlength="255"
                                       value="<?= sanitize($_POST['exchange_for'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Location</label>
                                <input type="text" class="form-control" name="location" maxlength="255"
                                       value="<?= sanitize($_POST['location'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Contact Preference</label>
                                <input type="text" class="form-control" name="contact_preference" maxlength="255"
                                       value="<?= sanitize($_POST['contact_preference'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Images (Max 5, Max 5MB each)</label>
                            <div class="image-upload-area">
                                <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                                <p class="mb-0">Click or drag images here</p>
                                <small class="text-muted">JPG, PNG, GIF, WebP only</small>
                            </div>
                            <input type="file" class="d-none" name="images[]" id="images" multiple accept="image/*">
                            <div id="image-preview" class="mt-2"></div>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-plus me-1"></i> Post Item</button>
                            <a href="/BaustExchange/marketplace.php" class="btn btn-outline-secondary">Cancel</a>
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

// Trigger on load
document.getElementById('transaction-type').dispatchEvent(new Event('change'));
</script>

<?php include __DIR__ . '/components/footer.php'; ?>
