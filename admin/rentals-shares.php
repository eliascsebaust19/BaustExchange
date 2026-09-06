<?php
$pageTitle = 'Manage Rentals & Shares - BAUST Exchange';
require_once __DIR__ . '/../components/auth-check.php';

if (!isAdmin()) redirect('/BaustExchange/dashboard.php');

$tab = $_GET['tab'] ?? 'rentals';

$rentals = fetchAll(
    "SELECT r.*, l.title as listing_title,
     u1.name as renter_name, u1.email as renter_email,
     u2.name as owner_name, u2.email as owner_email
     FROM rentals r 
     JOIN listings l ON r.listing_id = l.id 
     JOIN users u1 ON r.renter_id = u1.id 
     JOIN users u2 ON r.owner_id = u2.id 
     ORDER BY r.created_at DESC"
);

$shares = fetchAll(
    "SELECT s.*, l.title as listing_title,
     u1.name as requester_name, u1.email as requester_email,
     u2.name as owner_name, u2.email as owner_email
     FROM shares s 
     JOIN listings l ON s.listing_id = l.id 
     JOIN users u1 ON s.requester_id = u1.id 
     JOIN users u2 ON s.owner_id = u2.id 
     ORDER BY s.created_at DESC"
);

include __DIR__ . '/../components/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-2 d-none d-lg-block">
            <?php include __DIR__ . '/../components/admin/sidebar.php'; ?>
        </div>
        
        <div class="col-lg-10 col-md-12">
            <h4 class="mb-4">Rentals & Shares</h4>
            
            <ul class="nav nav-tabs mb-4">
                <li class="nav-item">
                    <a class="nav-link <?= $tab === 'rentals' ? 'active' : '' ?>" href="?tab=rentals">
                        Rentals <span class="badge bg-primary ms-1"><?= count($rentals) ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $tab === 'shares' ? 'active' : '' ?>" href="?tab=shares">
                        Shares <span class="badge bg-info ms-1"><?= count($shares) ?></span>
                    </a>
                </li>
            </ul>
            
            <?php if ($tab === 'rentals'): ?>
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Listing</th>
                                    <th>Renter</th>
                                    <th>Owner</th>
                                    <th>Rent</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($rentals)): ?>
                                <tr><td colspan="6" class="text-center text-muted py-4">No rental requests yet.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($rentals as $r): ?>
                                <tr>
                                    <td>
                                        <a href="/BaustExchange/item.php?id=<?= $r['listing_id'] ?>" class="text-decoration-none">
                                            <?= sanitize($r['listing_title']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <small>
                                            <?= sanitize($r['renter_name']) ?>
                                            <br><span class="text-muted"><?= sanitize($r['renter_email']) ?></span>
                                        </small>
                                    </td>
                                    <td>
                                        <small>
                                            <?= sanitize($r['owner_name']) ?>
                                            <br><span class="text-muted"><?= sanitize($r['owner_email']) ?></span>
                                        </small>
                                    </td>
                                    <td><small>৳<?= number_format($r['rent_price']) ?>/<?= ucfirst($r['rent_period']) ?></small></td>
                                    <td><span class="badge <?= getStatusBadgeClass($r['status']) ?>"><?= ucfirst($r['status']) ?></span></td>
                                    <td><small class="text-muted"><?= timeAgo($r['created_at']) ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <?php else: ?>
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Listing</th>
                                    <th>Requester</th>
                                    <th>Owner</th>
                                    <th>Duration</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($shares)): ?>
                                <tr><td colspan="6" class="text-center text-muted py-4">No share requests yet.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($shares as $s): ?>
                                <tr>
                                    <td>
                                        <a href="/BaustExchange/item.php?id=<?= $s['listing_id'] ?>" class="text-decoration-none">
                                            <?= sanitize($s['listing_title']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <small>
                                            <?= sanitize($s['requester_name']) ?>
                                            <br><span class="text-muted"><?= sanitize($s['requester_email']) ?></span>
                                        </small>
                                    </td>
                                    <td>
                                        <small>
                                            <?= sanitize($s['owner_name']) ?>
                                            <br><span class="text-muted"><?= sanitize($s['owner_email']) ?></span>
                                        </small>
                                    </td>
                                    <td><small><?= sanitize($s['duration'] ?? '-') ?></small></td>
                                    <td><span class="badge <?= getStatusBadgeClass($s['status']) ?>"><?= ucfirst($s['status']) ?></span></td>
                                    <td><small class="text-muted"><?= timeAgo($s['created_at']) ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../components/footer.php'; ?>