<?php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarOffcanvas">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title"><i class="fas fa-exchange-alt text-primary me-2"></i>BAUST Exchange</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0">
        <?php include __DIR__ . '/sidebar-menu.php'; ?>
    </div>
</div>

<div class="d-none d-lg-block sidebar-container">
    <?php include __DIR__ . '/sidebar-menu.php'; ?>
</div>
