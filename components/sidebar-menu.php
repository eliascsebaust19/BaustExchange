<nav class="sidebar-nav">
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>" href="/BaustExchange/dashboard.php">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'marketplace' ? 'active' : '' ?>" href="/BaustExchange/marketplace.php">
                <i class="fas fa-store"></i> Marketplace
            </a>
            <ul class="nav flex-column ms-3">
                <li class="nav-item">
                    <a class="nav-link small" href="/BaustExchange/marketplace.php">All Items</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link small" href="/BaustExchange/marketplace.php?category=books">Books</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link small" href="/BaustExchange/marketplace.php?category=furniture">Furniture</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link small" href="/BaustExchange/marketplace.php?category=electronics">Electronics</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link small" href="/BaustExchange/marketplace.php?category=clothing">Clothing</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link small" href="/BaustExchange/marketplace.php?category=stationery">Stationery</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link small" href="/BaustExchange/marketplace.php?category=academic-materials">Academic Materials</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link small" href="/BaustExchange/marketplace.php?category=others">Others</a>
                </li>
            </ul>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'post-item' ? 'active' : '' ?>" href="/BaustExchange/post-item.php">
                <i class="fas fa-plus-circle"></i> Post Item
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'my-listings' ? 'active' : '' ?>" href="/BaustExchange/my-listings.php">
                <i class="fas fa-list"></i> My Listings
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'requests' ? 'active' : '' ?>" href="/BaustExchange/requests.php">
                <i class="fas fa-exchange-alt"></i> Exchange Requests
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'rentals' ? 'active' : '' ?>" href="/BaustExchange/rentals.php">
                <i class="fas fa-hand-holding-usd"></i> Rentals
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'messages' || $currentPage === 'chat' ? 'active' : '' ?>" href="/BaustExchange/messages.php">
                <i class="fas fa-envelope"></i> Messages
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'wanted' ? 'active' : '' ?>" href="/BaustExchange/wanted.php">
                <i class="fas fa-search-dollar"></i> Wanted Items
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'notifications' ? 'active' : '' ?>" href="/BaustExchange/notifications.php">
                <i class="fas fa-bell"></i> Notifications
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'profile' ? 'active' : '' ?>" href="/BaustExchange/profile.php">
                <i class="fas fa-user"></i> Profile
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'settings' ? 'active' : '' ?>" href="/BaustExchange/settings.php">
                <i class="fas fa-cog"></i> Settings
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-danger" href="/BaustExchange/logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </li>
    </ul>
</nav>
