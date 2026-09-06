<?php
$pageTitle = 'Profile - BAUST Exchange';
require_once __DIR__ . '/components/auth-check.php';

$userId = (int)($_GET['id'] ?? $_SESSION['user_id']);
$profileUser = fetch("SELECT * FROM users WHERE id = ?", [$userId]);
if (!$profileUser) redirect('/BaustExchange/dashboard.php');

$isOwnProfile = $userId == $_SESSION['user_id'];

// Handle profile update & auto picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isOwnProfile) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        flash('error', 'Invalid security token.');
        redirect("/BaustExchange/profile.php?id={$userId}");
    }
    
    $updateData = [];

    // Handle profile image upload (auto upload or manual)
    if (!empty($_FILES['profile_image']['name'])) {
        $file = $_FILES['profile_image'];
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $fileType = $file['type'];
        
        if ($file['size'] <= MAX_FILE_SIZE && 
            in_array($fileType, ALLOWED_IMAGE_TYPES) && 
            in_array($fileExt, ALLOWED_IMAGE_EXTENSIONS)) {
            
            $newFilename = uniqid('profile_', true) . '.' . $fileExt;
            $uploadPath = PROFILES_UPLOAD_PATH . $newFilename;
            
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                $updateData['profile_image'] = $newFilename;
                flash('success', 'Profile picture updated successfully!');
            } else {
                flash('error', 'Failed to save profile picture.');
            }
        } else {
            flash('error', 'Invalid image format or file size too large (max 5MB).');
        }
    }
    
    // If standard edit form submitted
    if (isset($_POST['update_profile_details'])) {
        $department = trim($_POST['department'] ?? '');
        $studentId = trim($_POST['student_id'] ?? '');
        $levelTerm = trim($_POST['level_term'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        
        $updateData['department'] = $department ?: null;
        $updateData['student_id'] = $studentId ?: null;
        $updateData['level_term'] = $levelTerm ?: null;
        $updateData['phone'] = $phone ?: null;
        
        flash('success', 'Profile details updated successfully!');
    }
    
    if (!empty($updateData)) {
        update('users', $updateData, 'id = ?', [$userId]);
        $_SESSION['user_name'] = $profileUser['name'];
    }
    
    redirect("/BaustExchange/profile.php?id={$userId}");
}

// Get user stats
$listingsCount = count_rows('listings', 'user_id = ? AND status = "active"', [$userId]);
$completedExchanges = count_rows('exchange_requests', 
    '(sender_id = ? OR receiver_id = ?) AND status = "completed"', [$userId, $userId]);

// Get user listings
$userListings = fetchAll(
    "SELECT l.*, c.name as category_name,
     (SELECT image_path FROM listing_images WHERE listing_id = l.id LIMIT 1) as image
     FROM listings l 
     JOIN categories c ON l.category_id = c.id 
     WHERE l.user_id = ? AND l.status = 'active'
     ORDER BY l.created_at DESC LIMIT 6",
    [$userId]
);

// Refresh profile user data
if ($isOwnProfile) {
    $profileUser = fetch("SELECT * FROM users WHERE id = ?", [$userId]);
}

include __DIR__ . '/components/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-2 d-none d-lg-block">
            <?php include __DIR__ . '/components/sidebar.php'; ?>
        </div>
        
        <div class="col-lg-7 col-md-8">
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
            
            <!-- Hidden Auto Avatar Upload Form -->
            <?php if ($isOwnProfile): ?>
            <form id="autoAvatarForm" method="POST" enctype="multipart/form-data" class="d-none">
                <?= csrfField() ?>
                <input type="file" id="autoAvatarInput" name="profile_image" accept="image/*">
            </form>
            <?php endif; ?>

            <!-- Profile Header -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <?php if ($isOwnProfile): ?>
                        <div class="profile-avatar-wrapper me-4" id="profileAvatarWrapper" title="Click to upload profile photo" style="cursor: pointer;">
                            <img src="<?= getProfileImageUrl($profileUser['profile_image'] ?? null) ?>" alt="Profile">
                            <div class="profile-avatar-overlay">
                                <i class="fas fa-camera"></i>
                                <span>Change</span>
                            </div>
                        </div>
                        <?php else: ?>
                        <img src="<?= getProfileImageUrl($profileUser['profile_image'] ?? null) ?>" 
                             class="rounded-circle me-4" width="100" height="100" style="object-fit: cover; border: 3px solid #059669;" alt="Profile">
                        <?php endif; ?>
                        
                        <div>
                            <h4 class="mb-1"><?= sanitize($profileUser['name']) ?></h4>
                            <p class="mb-1">
                                <span class="badge bg-primary me-1"><?= userRoleLabel($profileUser['role'], $profileUser['student_level'] ?? null) ?></span>
                                <?php if ($profileUser['department']): ?>
                                <span class="badge bg-secondary me-1"><?= sanitize($profileUser['department']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($profileUser['level_term']) && $profileUser['level_term'] !== 'N/A'): ?>
                                <span class="badge bg-info text-dark"><?= sanitize($profileUser['level_term']) ?></span>
                                <?php endif; ?>
                            </p>
                            <?php if ($profileUser['student_id']): ?>
                            <small class="text-muted d-block mb-1">
                                <i class="fas fa-id-card me-1"></i>UID: <?= sanitize($profileUser['student_id']) ?>
                            </small>
                            <?php endif; ?>
                            <small class="text-muted">
                                <i class="fas fa-envelope me-1"></i><?= sanitize($profileUser['email']) ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Stats -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-primary mb-1"><?= $listingsCount ?></h3>
                            <small class="text-muted">Active Listings</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-success mb-1"><?= $completedExchanges ?></h3>
                            <small class="text-muted">Completed Exchanges</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-info mb-1"><?= timeAgo($profileUser['created_at']) ?></h3>
                            <small class="text-muted">Member Since</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if ($isOwnProfile): ?>
            <!-- Edit Profile -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-user-edit me-2"></i>Edit Profile Details</h6>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?= csrfField() ?>
                        <input type="hidden" name="update_profile_details" value="1">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Department</label>
                                <input type="text" class="form-control" name="department" maxlength="255"
                                       value="<?= sanitize($profileUser['department']) ?>" placeholder="e.g. CSE">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Student UID</label>
                                <input type="text" class="form-control" name="student_id" maxlength="50"
                                       value="<?= sanitize($profileUser['student_id']) ?>" placeholder="e.g. 0802420405101139">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Level & Term</label>
                                <select class="form-select" name="level_term">
                                    <option value="">Select Level & Term</option>
                                    <option value="Level 1 Term I" <?= $profileUser['level_term'] === 'Level 1 Term I' ? 'selected' : '' ?>>Level 1 Term I</option>
                                    <option value="Level 1 Term II" <?= $profileUser['level_term'] === 'Level 1 Term II' ? 'selected' : '' ?>>Level 1 Term II</option>
                                    <option value="Level 2 Term I" <?= $profileUser['level_term'] === 'Level 2 Term I' ? 'selected' : '' ?>>Level 2 Term I</option>
                                    <option value="Level 2 Term II" <?= $profileUser['level_term'] === 'Level 2 Term II' ? 'selected' : '' ?>>Level 2 Term II</option>
                                    <option value="Level 3 Term I" <?= $profileUser['level_term'] === 'Level 3 Term I' ? 'selected' : '' ?>>Level 3 Term I</option>
                                    <option value="Level 3 Term II" <?= $profileUser['level_term'] === 'Level 3 Term II' ? 'selected' : '' ?>>Level 3 Term II</option>
                                    <option value="Level 4 Term I" <?= $profileUser['level_term'] === 'Level 4 Term I' ? 'selected' : '' ?>>Level 4 Term I</option>
                                    <option value="Level 4 Term II" <?= $profileUser['level_term'] === 'Level 4 Term II' ? 'selected' : '' ?>>Level 4 Term II</option>
                                    <option value="N/A" <?= $profileUser['level_term'] === 'N/A' ? 'selected' : '' ?>>N/A (Faculty)</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone Number</label>
                                <input type="text" class="form-control" name="phone" maxlength="20"
                                       value="<?= sanitize($profileUser['phone']) ?>" placeholder="01700000000">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Save Details
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- User Listings -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><?= $isOwnProfile ? 'My' : sanitize($profileUser['name']) ?>'s Listings</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($userListings)): ?>
                    <div class="empty-state">
                        <i class="fas fa-store"></i>
                        <p>No active listings yet.</p>
                    </div>
                    <?php else: ?>
                    <div class="row">
                        <?php foreach ($userListings as $listing): ?>
                        <div class="col-md-4 mb-3">
                            <div class="card listing-card h-100">
                                <img src="<?= $listing['image'] ? '/BaustExchange/uploads/items/' . $listing['image'] : '/BaustExchange/assets/images/no-image.png' ?>" 
                                     class="card-img-top" alt="<?= sanitize($listing['title']) ?>">
                                <div class="card-body">
                                    <h6 class="card-title"><?= sanitize($listing['title']) ?></h6>
                                    <p class="listing-price mb-2"><?= formatPrice($listing['price'], $listing['transaction_type'], $listing['rent_price'] ?? null, $listing['rent_period'] ?? null) ?></p>
                                    <a href="/BaustExchange/item.php?id=<?= $listing['id'] ?>" class="btn btn-outline-primary btn-sm w-100">View</a>
                                </div>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const avatarWrapper = document.getElementById('profileAvatarWrapper');
    const autoInput = document.getElementById('autoAvatarInput');
    const autoForm = document.getElementById('autoAvatarForm');

    if (avatarWrapper && autoInput && autoForm) {
        avatarWrapper.addEventListener('click', function(e) {
            e.preventDefault();
            autoInput.click();
        });

        autoInput.addEventListener('change', function() {
            if (autoInput.files && autoInput.files.length > 0) {
                autoForm.submit();
            }
        });
    }
});
</script>
