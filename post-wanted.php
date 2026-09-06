<?php
$pageTitle = 'Post Wanted Item - BAUST Exchange';
require_once __DIR__ . '/components/auth-check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        flash('error', 'Invalid security token.');
        redirect('/BaustExchange/post-wanted.php');
    }
    
    $title = trim($_POST['title'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $budget = $_POST['budget'] !== '' ? (float)$_POST['budget'] : null;
    
    $errors = [];
    if (empty($title)) $errors[] = 'Title is required.';
    if ($categoryId <= 0) $errors[] = 'Category is required.';
    
    if (empty($errors)) {
        $wantedId = insert('wanted_items', [
            'user_id' => $_SESSION['user_id'],
            'title' => $title,
            'category_id' => $categoryId,
            'description' => $description ?: null,
            'budget' => $budget,
            'status' => 'active',
        ]);
        
        logActivity($_SESSION['user_id'], 'wanted_created', 'Posted wanted item: ' . $title);
        flash('success', 'Wanted item posted successfully!');
        redirect('/BaustExchange/wanted.php');
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
            <h4 class="mb-4">Post Wanted Item</h4>
            
            <?php if ($msg = flash('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= $msg ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <form method="POST">
                        <?= csrfField() ?>
                        
                        <div class="mb-3">
                            <label class="form-label">What are you looking for? <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="title" required maxlength="255"
                                   placeholder="e.g., DBMS Book, Calculator, Chair"
                                   value="<?= sanitize($_POST['title'] ?? '') ?>">
                        </div>
                        
                        <div class="mb-3">
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
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"
                                      placeholder="Add any specific details about what you're looking for..."><?= sanitize($_POST['description'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Budget (৳)</label>
                            <input type="number" class="form-control" name="budget" min="0" step="0.01"
                                   placeholder="How much are you willing to pay?"
                                   value="<?= sanitize($_POST['budget'] ?? '') ?>">
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-plus me-1"></i> Post Wanted Item</button>
                            <a href="/BaustExchange/wanted.php" class="btn btn-outline-secondary">Cancel</a>
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

<?php include __DIR__ . '/components/footer.php'; ?>
