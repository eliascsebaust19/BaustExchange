<?php
$pageTitle = 'Manage Categories - BAUST Exchange';
require_once __DIR__ . '/../components/auth-check.php';

if (!isAdmin()) redirect('/BaustExchange/dashboard.php');

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        flash('error', 'Invalid security token.');
        redirect('/BaustExchange/admin/categories.php');
    }
    
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add':
            $name = trim($_POST['name'] ?? '');
            $slug = strtolower(trim($_POST['slug'] ?? ''));
            $icon = trim($_POST['icon'] ?? 'fa-tag');
            
            if (empty($name) || empty($slug)) {
                flash('error', 'Name and slug are required.');
            } else {
                $exists = fetch("SELECT id FROM categories WHERE slug = ?", [$slug]);
                if ($exists) {
                    flash('error', 'Category slug already exists.');
                } else {
                    insert('categories', [
                        'name' => $name,
                        'slug' => $slug,
                        'icon' => $icon,
                        'is_active' => 1,
                        'sort_order' => (int)($_POST['sort_order'] ?? 0),
                    ]);
                    flash('success', 'Category added successfully.');
                }
            }
            break;
            
        case 'edit':
            $catId = (int)$_POST['category_id'];
            $name = trim($_POST['name'] ?? '');
            $slug = strtolower(trim($_POST['slug'] ?? ''));
            $icon = trim($_POST['icon'] ?? 'fa-tag');
            $isActive = isset($_POST['is_active']) ? 1 : 0;
            
            if (empty($name) || empty($slug)) {
                flash('error', 'Name and slug are required.');
            } else {
                update('categories', [
                    'name' => $name,
                    'slug' => $slug,
                    'icon' => $icon,
                    'is_active' => $isActive,
                    'sort_order' => (int)($_POST['sort_order'] ?? 0),
                ], 'id = ?', [$catId]);
                flash('success', 'Category updated successfully.');
            }
            break;
            
        case 'delete':
            $catId = (int)$_POST['category_id'];
            $listingsCount = count_rows('listings', 'category_id = ?', [$catId]);
            if ($listingsCount > 0) {
                flash('error', 'Cannot delete category with existing listings.');
            } else {
                delete('categories', 'id = ?', [$catId]);
                flash('success', 'Category deleted successfully.');
            }
            break;
    }
    redirect('/BaustExchange/admin/categories.php');
}

$categories = fetchAll("SELECT * FROM categories ORDER BY sort_order");

include __DIR__ . '/../components/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-2 d-none d-lg-block">
            <?php include __DIR__ . '/../components/admin/sidebar.php'; ?>
        </div>
        
        <div class="col-lg-10 col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0">Manage Categories</h4>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                    <i class="fas fa-plus me-1"></i> Add Category
                </button>
            </div>
            
            <?php if ($msg = flash('success')): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= $msg ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if ($msg = flash('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= $msg ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Slug</th>
                                    <th>Icon</th>
                                    <th>Listings</th>
                                    <th>Status</th>
                                    <th>Sort</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $cat): ?>
                                <tr>
                                    <td><strong><?= sanitize($cat['name']) ?></strong></td>
                                    <td><code><?= sanitize($cat['slug']) ?></code></td>
                                    <td><i class="fas <?= sanitize($cat['icon']) ?>"></i></td>
                                    <td><span class="badge bg-info"><?= count_rows('listings', 'category_id = ?', [$cat['id']]) ?></span></td>
                                    <td>
                                        <span class="badge <?= $cat['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                                            <?= $cat['is_active'] ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </td>
                                    <td><?= $cat['sort_order'] ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" data-bs-toggle="modal" 
                                                    data-bs-target="#editCategoryModal<?= $cat['id'] ?>" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Delete this category?')">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                                                <button type="submit" class="btn btn-outline-danger" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                        
                                        <!-- Edit Modal -->
                                        <div class="modal fade" id="editCategoryModal<?= $cat['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Edit Category</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <?= csrfField() ?>
                                                            <input type="hidden" name="action" value="edit">
                                                            <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                                                            <div class="mb-3">
                                                                <label class="form-label">Name</label>
                                                                <input type="text" class="form-control" name="name" value="<?= sanitize($cat['name']) ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Slug</label>
                                                                <input type="text" class="form-control" name="slug" value="<?= sanitize($cat['slug']) ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Icon (Font Awesome class)</label>
                                                                <input type="text" class="form-control" name="icon" value="<?= sanitize($cat['icon']) ?>">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Sort Order</label>
                                                                <input type="number" class="form-control" name="sort_order" value="<?= $cat['sort_order'] ?>">
                                                            </div>
                                                            <div class="form-check">
                                                                <input type="checkbox" class="form-check-input" name="is_active" value="1" <?= $cat['is_active'] ? 'checked' : '' ?>>
                                                                <label class="form-check-label">Active</label>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-primary">Save Changes</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
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

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Slug</label>
                        <input type="text" class="form-control" name="slug" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Icon (Font Awesome class)</label>
                        <input type="text" class="form-control" name="icon" value="fa-tag">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sort Order</label>
                        <input type="number" class="form-control" name="sort_order" value="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../components/footer.php'; ?>
