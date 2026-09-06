<?php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<nav class="sidebar-nav">
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>" href="/BaustExchange/admin/dashboard.php">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'users' ? 'active' : '' ?>" href="/BaustExchange/admin/users.php">
                <i class="fas fa-users"></i> Users
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'listings' ? 'active' : '' ?>" href="/BaustExchange/admin/listings.php">
                <i class="fas fa-store"></i> Listings
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'categories' ? 'active' : '' ?>" href="/BaustExchange/admin/categories.php">
                <i class="fas fa-tags"></i> Categories
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'requests' ? 'active' : '' ?>" href="/BaustExchange/admin/requests.php">
                <i class="fas fa-exchange-alt"></i> Exchange Requests
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'rentals-shares' ? 'active' : '' ?>" href="/BaustExchange/admin/rentals-shares.php">
                <i class="fas fa-handshake"></i> Rentals & Shares
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'reports' ? 'active' : '' ?>" href="/BaustExchange/admin/reports.php">
                <i class="fas fa-flag"></i> Reports
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'activities' ? 'active' : '' ?>" href="/BaustExchange/admin/activities.php">
                <i class="fas fa-history"></i> Activities
            </a>
        </li>
        <li class="nav-item mt-3">
            <a class="nav-link" href="/BaustExchange/dashboard.php">
                <i class="fas fa-arrow-left"></i> Back to Site
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-danger" href="/BaustExchange/logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </li>
    </ul>
</nav>
