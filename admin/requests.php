<?php
$pageTitle = 'Manage Exchange Requests - BAUST Exchange';
require_once __DIR__ . '/../components/auth-check.php';

if (!isAdmin()) redirect('/BaustExchange/dashboard.php');

$requests = fetchAll(
    "SELECT er.*, l.title as listing_title,
     s.name as sender_name, s.email as sender_email,
     r.name as receiver_name, r.email as receiver_email
     FROM exchange_requests er 
     JOIN listings l ON er.listing_id = l.id 
     JOIN users s ON er.sender_id = s.id 
     JOIN users r ON er.receiver_id = r.id 
     ORDER BY er.created_at DESC"
);

include __DIR__ . '/../components/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-2 d-none d-lg-block">
            <?php include __DIR__ . '/../components/admin/sidebar.php'; ?>
        </div>
        
        <div class="col-lg-10 col-md-12">
            <h4 class="mb-4">Exchange Requests</h4>
            
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Listing</th>
                                    <th>Sender</th>
                                    <th>Receiver</th>
                                    <th>Offered Item</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requests as $req): ?>
                                <tr>
                                    <td>
                                        <a href="/BaustExchange/item.php?id=<?= $req['listing_id'] ?>" class="text-decoration-none">
                                            <?= sanitize($req['listing_title']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <small>
                                            <?= sanitize($req['sender_name']) ?>
                                            <br><span class="text-muted"><?= sanitize($req['sender_email']) ?></span>
                                        </small>
                                    </td>
                                    <td>
                                        <small>
                                            <?= sanitize($req['receiver_name']) ?>
                                            <br><span class="text-muted"><?= sanitize($req['receiver_email']) ?></span>
                                        </small>
                                    </td>
                                    <td><small><?= sanitize($req['offered_item'] ?? '-') ?></small></td>
                                    <td><span class="badge <?= getStatusBadgeClass($req['status']) ?>"><?= ucfirst($req['status']) ?></span></td>
                                    <td><small class="text-muted"><?= timeAgo($req['created_at']) ?></small></td>
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

<?php include __DIR__ . '/../components/footer.php'; ?>
