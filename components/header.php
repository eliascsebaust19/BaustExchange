<?php
$currentUser = currentUser();
$unreadNotifications = isLoggedIn() ? getUnreadNotificationCount($_SESSION['user_id']) : 0;
$unreadMessages = isLoggedIn() ? getUnreadMessageCount($_SESSION['user_id']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="/BaustExchange/assets/css/style.css" rel="stylesheet">
</head>
<body class="<?= (isset($_COOKIE['sidebar_toggled']) && $_COOKIE['sidebar_toggled'] === '1') ? 'sidebar-toggled' : '' ?>">
    <script>
        if (localStorage.getItem('sidebar_toggled') === '1') {
            document.body.classList.add('sidebar-toggled');
        }
    </script>
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top">
        <div class="container-fluid">
            <button class="btn btn-link text-dark me-2 p-1" id="sidebarToggleBtn" type="button" title="Toggle Sidebar">
                <i class="fas fa-bars fa-lg"></i>
            </button>
            
            <a class="navbar-brand fw-bold text-primary" href="/BaustExchange/dashboard.php">
                <i class="fas fa-exchange-alt me-2"></i>BAUST Exchange
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="d-none d-lg-block mx-auto" style="width: 40%;">
                <form action="/BaustExchange/marketplace.php" method="GET" class="d-flex">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" placeholder="Search items..." value="<?= sanitize($_GET['search'] ?? '') ?>">
                        <button class="btn btn-outline-primary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="d-flex align-items-center">
                <?php if (isLoggedIn()): ?>
                <a href="/BaustExchange/notifications.php" class="btn btn-link position-relative me-2">
                    <i class="fas fa-bell"></i>
                    <?php if ($unreadNotifications > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        <?= $unreadNotifications > 99 ? '99+' : $unreadNotifications ?>
                    </span>
                    <?php endif; ?>
                </a>
                
                <a href="/BaustExchange/messages.php" class="btn btn-link position-relative me-2">
                    <i class="fas fa-envelope"></i>
                    <?php if ($unreadMessages > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        <?= $unreadMessages > 99 ? '99+' : $unreadMessages ?>
                    </span>
                    <?php endif; ?>
                </a>

                <div class="dropdown me-2">
                    <button class="btn btn-link dropdown-toggle d-flex align-items-center text-decoration-none text-dark" data-bs-toggle="dropdown">
                        <img src="<?= getProfileImageUrl($currentUser['profile_image'] ?? null) ?>" 
                             alt="Profile" class="rounded-circle me-2" width="34" height="34" style="object-fit: cover; border: 2px solid #059669;">
                        <span class="d-none d-lg-inline fw-semibold"><?= sanitize($currentUser['name'] ?? 'User') ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="/BaustExchange/profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                        <li><a class="dropdown-item" href="/BaustExchange/my-listings.php"><i class="fas fa-list me-2"></i>My Listings</a></li>
                        <li><a class="dropdown-item" href="/BaustExchange/requests.php"><i class="fas fa-exchange-alt me-2"></i>Requests</a></li>
                        <li><a class="dropdown-item" href="/BaustExchange/settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
                        <?php if (isAdmin()): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/BaustExchange/admin/dashboard.php"><i class="fas fa-admin me-2"></i>Admin Panel</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="/BaustExchange/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>

                <button class="btn btn-link text-dark ms-1 d-none d-lg-inline-block p-1" id="headerRightSidebarToggleBtn" type="button" title="Toggle Right Panel">
                    <i class="fas fa-sliders-h fa-lg"></i>
                </button>
                <?php endif; ?>
            </div>
        </div>
    </nav>
